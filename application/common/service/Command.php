<?php

namespace app\common\service;


use think\Model;
use think\Request;

/**
 * 指令
 * @package app\common\service
 */
class Command extends Model
{
    public $sleepTime = 150000;  //循环间隔（单位微秒）

    public $retryCount = 200;  //循环次数

    public $serviceUrl = '';  //发送命令地址

    public $secretKey = '123456';   //头部加密信息

    public $equipmentCache = null;  //机柜缓存

    public $online = false;  //机柜是否在线

    public $equipmentId = 0;  //机柜ID

    protected $isBusy = false; //是否正在操作

    protected $lowPowerLimit = 1; //低于此数值，即判断为电量不足，无法借出

    public $storage; //缓存介质

    public $user_info = null; //用户信息

    public $is_debug = false; //缓存介质


    public function __construct($data = [])
    {
    }

    public function initData($id)
    {
        $this->equipmentId = $id;
        $this->serviceUrl = config('tcpintraneturl');
        $this->secretKey = config('headerkey');
        $this->storage = \think\Loader::model('Storage', 'service');
        $this->equipmentCache = $this->storage->getEquipment($id);
        if ($this->equipmentCache) {
            $time = time() - $this->equipmentCache['heart_time'];
            if ($time < config('online_time') && $this->equipmentCache['is_online'] == 'true') {
                $this->online = true;
            }
        }
        $this->is_debug = config('is_debug');
        $this->is_debug && $this->online = true;

        return $this;
    }

    function setUserInfo($info)
    {
        $this->user_info = $info;
    }

    function setLowPower($power)
    {
        $this->lowPowerLimit = $power;
    }

    //获取机柜信息
    function getEquipment()
    {
        $info = Cache::store('redis')->get($this->equipmentId);
        $this->equipment = $info;
        return $info;
    }

    /**
     * 发送指令
     * @param $data
     * @return bool
     */
    public function send($data)
    {
        $client = stream_socket_client($this->serviceUrl);
        if (!$client) {
            return false;
        }
        $time = time();
        $params = [
            'p1' => $data['command'], //指令
            'p2' => $time,
            'p3' => md5($this->secretKey . $time),
            'p4' => $data['equipment_id'],
            'p5' => $data['battery_id'],
            'p6' => $data['lock_id'],
        ];
        isset($data['order']) && $params['p7'] = $data['order']; //流水号，部分机型
        isset($data['aims']) && $params['aims'] = $data['aims']; //主板编号

        $command = 'CMD:' . json_encode($params);
        fwrite($client, $command);
        //记录指令日志
        $module = Request::instance()->module();
        return true;
    }


    //强制心跳
    public function triggerHeartbeat()
    {
        $order_key = time() . '_' . mt_rand(1000, 9999);

        $params = [
            'command' => '111',
            'equipment_id' => $this->equipmentId,
            'order' => $order_key,
        ];
        $res = $this->send($params);
        if (!$res) {
            return ['status' => 0, 'msg' => '系统繁忙，请稍后重试'];
        }
        return ['status' => 1, 'msg' => '发送成功', 'key' => $order_key];
    }

    //打开所有锁位
    public function openAllLock()
    {
        //检测机柜在线和是否繁忙
        $ret = $this->_checkEquipmentBusy();
        if (1 !== $ret['status']) {
            return $ret;
        }
        //获取库存
        /*        $stock = $this->stockDetail();
                if ($stock['status'] != 1) {
                    $this->storage->removeEquipmentLock($this->equipmentId);
                    return ['status' => 0, 'msg' => '机柜不在线'];
                }*/

        $cache = $this->storage->getEquipment($this->equipmentId);
        if (empty($cache['details'])) {
            return ['status' => 0, 'msg' => '没有充电宝'];
        }
        if ($cache['type'] == 'zd') {
            $params = [
                'command' => '250',
                'equipment_id' => $this->equipmentId,
                'battery_id' => 0,
                'lock_id' => (string)0,
                'order' => time() . "_" . mt_rand(1000, 9999),
                'aims' => 0,
            ];

            $res = $this->send($params);
            return ['status' => 1, 'msg' => '发送指令成功'];
        }
        foreach ($cache['details'] as $v) {
            //执行弹出指令
            $params = [
                'command' => '250',
                'equipment_id' => $this->equipmentId,
                'battery_id' => $v['bid'],
                'lock_id' => (string)$v['lock'],
                'order' => time() . "_" . mt_rand(1000, 9999),
            ];

            $res = $this->send($params);
            if ($res) {
                $key = $this->equipmentId . "_" . $v['lock'];
                !empty($this->user_info) && $this->storage->setOperate($key, $this->user_info);
            }
            usleep(1200000);
        }
        return ['status' => 1, 'msg' => '发送指令成功'];
    }

    /*
     * 打开指定锁位
     * @param $lock_id
     */
    public function openLock($lock_id)
    {
        //检测机柜在线和是否繁忙
        $ret = $this->_checkEquipmentBusy();
        if (1 !== $ret['status']) {
            return $ret;
        }

        $cache = $this->storage->getEquipment($this->equipmentId);
        $battery_id = $cache['details'][$lock_id]['bid'];
        if (!$battery_id && 'manlian' != $cache['type']) {
            return ['status' => 0, 'msg' => '没有电池'];
        }
        //更新锁孔状态
        $cache['lock_status'][$lock_id] = ['status' => 0, 'bid' => $battery_id, 'hex' => '', 'type' => 'openLock'];
        $this->storage->setEquipment($this->equipmentId, $cache);


        //执行弹出指令
        $params = [
            'command' => '250',
            'equipment_id' => $this->equipmentId,
            'battery_id' => $battery_id,
            'lock_id' => $lock_id,
            'order' => time() . "_" . mt_rand(1000, 9999),
        ];
        $bn = intval($cache['bn']);
        if ($bn > 1) { //多主板串联
            $params['aims'] = ceil($lock_id / 12);
            $params['lock_id'] = $lock_id - ($params['aims'] - 1) * 12;
        }

        $res = $this->send($params);
        if (!$res) {
            return ['status' => 0, 'msg' => '系统繁忙，请稍后重试'];
        }

        $info = ['status' => 0, 'msg' => '连接机柜失败'];
        sleep(1); //等待1秒,测试回调4秒左右

        $key = $this->equipmentId . "_" . $lock_id;
        !empty($this->user_info) && $this->storage->setOperate($key, $this->user_info);

        //$cache = $this->storage->getEquipment($this->equipmentId);
        $i = 0;
        while ($i < $this->retryCount) {
            usleep($this->sleepTime);
            $cache = $this->storage->getEquipment($this->equipmentId);
            $data = $cache['lock_status'][$lock_id];
            $i++;

            if (intval($data['status']) < 1) {
                continue;
            }
            $info['status'] = $data['status'];
            $info['hex'] = $data['hex'];
            if (1 == $data['status']) { //成功
                $info['msg'] = "锁孔{$lock_id}弹出成功";
            } else {
                $info['msg'] = "弹出失败:{$data['error']}";
            }
            break;
        }
        return $info;
    }

    /**
     * 重启
     */
    public function restart()
    {
        //检测机柜在线和是否繁忙
        $ret = $this->_checkEquipmentBusy();
        if (1 !== $ret['status']) {
            return $ret;
        }
        $cache = $this->equipmentCache;
        $this->storage->refreshEquipmentOpe($this->equipmentId, $cache, 'restart');
        $params = [
            'command' => '203',
            'equipment_id' => $this->equipmentId,
            'order' => time() . "_" . mt_rand(1000, 9999),
        ];
        $res = $this->send($params);
        if (!$res) {
            return ['status' => 0, 'msg' => '系统繁忙，请稍后重试'];
        }
        return ['status' => 1, 'msg' => '指令已发送'];
        //        sleep(1); //等待1秒
        //        $i = 0;
        //        while ($i < $this->retryCount) {
        //            usleep($this->sleepTime);
        //            $cache = $this->storage->getEquipment($this->equipmentId);
        //            $i++;
        //            if ($cache['ope_restart']['status'] > 0) {
        //                $info = ['status' => $cache['ope_restart']['status'], 'hex' => $cache['ope_restart']['hex']];
        //                return $info;
        //            }
        //        }
        //        return ['status' => 0, 'msg' => '连接机柜失败'];
    }

    public function setRetryCount($num)
    {
        $this->retryCount = $num;
    }

    /*
     * 库存明细
     */
    public function stockDetail()
    {
        if (!$this->online) {
            return ['status' => 0, 'msg' => "Cabinet is not online"];
        }
        if ($this->is_debug) {

            $info = ['status' => 1, 'hex' => '', 'data' => [1]];
            return $info;
        }

        $cache = $this->equipmentCache;
        $cache['details'] = [];
        $this->storage->refreshEquipmentOpe($this->equipmentId, $cache, 'stockDetail');

        $params = [
            'command' => '111',
            'equipment_id' => $this->equipmentId,
            'order' => time() . "_" . mt_rand(1000, 9999),
        ];

        $res = $this->send($params);
        if (!$res) {
            return ['status' => 0, 'msg' => "System is busy, please try again later"];
        }

        $info = ['status' => 0, 'msg' => 'Device networking failure'];
        //等待1秒后获取机器返回结果
        sleep(1);
        $cache = $this->storage->getEquipment($this->equipmentId);

        $i = 0;
        while ($i < $this->retryCount) {
            usleep($this->sleepTime);
            $cache = $this->storage->getEquipment($this->equipmentId);

            if ($cache['ope_stockDetail']['status'] == 1) {
                $bn = intval($cache['bn']);
                if ($bn > 1) {
                    $bn = $bn - 1;
                    sleep($bn);
                }
                $info = ['status' => 1, 'hex' => $cache['ope_stockDetail']['hex'], 'data' => $cache['details']];
                return $info;
            } else {
                $i++;
            }
        }
        return $info;
    }


    //借充电宝
    public function borrowDevice()
    {
        if ($this->is_debug) {
            $info['status'] = 1;
            $info['battery_id'] = 'CS1905210007';
            $info['lock_id'] = 1;
            $info['msg'] = '借出成功';
            return $info;
        }

        if (!$this->online) {
            return ['status' => 0, 'msg' => 'The cabinet is offline'];
        }

        $lock = $this->storage->getEquipmentLock($this->equipmentId);
        if ($lock) {
            return ['status' => 0, 'msg' => 'The device is busy. Please try again later'];
        }
        $this->storage->addEquipmentLock($this->equipmentId);

        $cache = $this->storage->getEquipment($this->equipmentId);

        $ret = $this->_getBorrowBattery($cache['details']);
        if (1 != $ret['status']) {
            $this->storage->removeEquipmentLock($this->equipmentId);
            return $ret;
        }
        $lock_id = $ret['lock_id']; //锁孔
        $battery_id = $ret['battery_id']; //电池
        //更新锁孔状态
        $cache['lock_status'][$lock_id] = ['status' => 0, 'bid' => $battery_id, 'hex' => '', 'type' => 'borrowDevice'];
        $this->storage->setEquipment($this->equipmentId, $cache);


        //执行借出指令
        $params = [
            'command' => '200',
            'equipment_id' => $this->equipmentId,
            'battery_id' => $battery_id,
            'lock_id' => $lock_id,
            'order' => time() . "_" . mt_rand(1000, 9999),
        ];
        $bn = intval($cache['bn']);
        if ($bn > 1) { //多主板串联
            $params['aims'] = ceil($lock_id / 12);
            $params['lock_id'] = $lock_id - ($params['aims'] - 1) * 12;
        }

        $res = $this->send($params);
        if (!$res) {
            $this->storage->removeEquipmentLock($this->equipmentId);
            return ['status' => 0, 'msg' => 'The system is busy. Try again later'];
        }
        sleep(1); //等待，测试时间为4秒
        $i = 0;
        $info = ['status' => 0, 'msg' => 'Device networking failure'];
        while ($i < $this->retryCount) {
            usleep($this->sleepTime);
            $i++;

            $cache = $this->storage->getEquipment($this->equipmentId);
            $data = $cache['lock_status'][$lock_id];
            if (intval($data['status']) < 1) {
                continue;
            }
            $info['status'] = $code = $data['status'];
            $info['hex'] = $data['hex'];
            if (1 == $data['status']) { //成功
                $info['battery_id'] = $battery_id;
                $info['lock_id'] = $lock_id;
                $info['msg'] = 'renting successfully';
            } else {
                $info['msg'] = 'renting failed';
            }
            unset($cache['lock_status'][$lock_id]);
            $this->storage->setEquipment($this->equipmentId, $cache);
            break;

        }
        $this->storage->removeEquipmentLock($this->equipmentId);
        return $info;
    }

    //判断机柜是否繁忙
    private function _checkEquipmentBusy()
    {
        if (!$this->online) {
            return ['status' => 0, 'msg' => 'Device networking failure'];
        }
        //机柜处理数据锁判断
        $lock = $this->storage->getEquipmentLock($this->equipmentId);
        if ($lock) {
            $time = time() - $lock + 1;
            $time < 1 && $time = 1;
            return ['status' => 0, 'msg' => "The device is busy. Please try again later"];
        }
        return ['status' => 1];
    }


    /**
     * 获取出借充电宝
     * @param $details
     * @return array
     */
    private function _getBorrowBattery($details)
    {
        if (!$details) {
            return ['status' => 0, 'msg' => 'No power bank available'];
        }

        $forbid_locks = \think\Loader::model('Equipment', 'logic')->forbidLocks($this->equipmentId);
        $power = [];
        foreach ($details as $k => $v) {
            if (!$v['fault'] && !in_array($k, $forbid_locks)) {
                $power[$k] = $v['power'];
            }
        }
        if (!$power) {
            return ['status' => 0, 'msg' => 'No power bank available'];
        }

        if (max($power) < $this->lowPowerLimit) {
            return ['status' => 0, 'msg' => 'The charger is low, please try again later'];
        }


        $maxpower = array_count_values($power); //获取每个电量值的数量
        $battery_ids = [];

        //如果电量最多的数量大于1，这则循环查出他们的使用次数
        if ($maxpower[max($power)] > 1) {
            foreach ($power as $key => $value) {
                if ($value == max($power)) {
                    $battery_ids[$key] = $details[$key]['bid'];
                }
            }
            $lock_id = \think\Loader::model('Lease', 'logic')->borrowBattery($battery_ids);
        } else {
            //如果电量最多的只有一个，则锁ID等于电量最多的
            $lock_id = array_search(max($power), $power);
        }
        return ['status' => 1, 'lock_id' => $lock_id, 'battery_id' => $details[$lock_id]['bid']];
    }

    function preBorrow()
    {
        $lock = $this->storage->getEquipmentLock($this->equipmentId);
        if ($lock) {
            return ['status' => 0, 'msg' => 'The device is busy. Please try again later'];
        }
        $this->storage->addEquipmentLock($this->equipmentId);
        $this->setRetryCount(30);
        $stock = $this->stockDetail();
        if ($stock['status'] != 1) {
            $this->storage->removeEquipmentLock($this->equipmentId);
            return $stock;
        }

        $cache = $this->storage->getEquipment($this->equipmentId);
        $ret = $this->_getBorrowBattery($cache['details']);
        $this->storage->removeEquipmentLock($this->equipmentId);
        if (1 != $ret['status']) {
            return $ret;
        }
        return ['status' => 1, 'battery_id' => $ret['battery_id'], 'lock_id' => $ret['lock_id']];
    }
}
