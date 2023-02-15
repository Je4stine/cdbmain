<?php

namespace app\cloud\controller;

use think\Cache;
use think\Controller;
use think\Db;
use think\Request;

//用于接收相关数据，
class Reception extends Controller
{
    public $storage;//缓存介质

    public $db;//数据库

    public $listHandler;//心跳队列

    public function _initialize()
    {
        if (!Request::instance()->isPost()) {
            echo json_encode(['status' => 0, 'msg' => lang('非法请求')], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $key = config('headerkey');
        $requestkey = Request::instance()->header('key');
        $time = Request::instance()->header('time');
        $key = md5($key . $time);

        $difference = time() - (int)$time;
        if ($requestkey != $key || ($difference && $difference > 180) || ($difference && $difference < -180)) {
            $data = array(
                'mykey' => $key,
                'mytime' => time(),
                'requestkey' => $requestkey,
                'requesttime' => $time,
            );
            echo json_encode(['status' => 0, 'msg' => lang('非法请求')], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $this->storage = \think\Loader::model('Storage', 'service');
        $this->listHandler = Cache::store('redis')->handler();
    }


    /**
     *
     * 登陆信息
     * 1 登陆成功； 2 失败
     */
    public function login()
    {
        $cabinet_id = input('post.cabinet_id'); //机柜ID
        $ip = input('post.ip'); //客户端IP地址
        $client_id = input('post.client_id'); //连接ID
        $hex = input('post.hex'); //十六进制信息,部分厂家有
        $details = input('post.details'); //库存,部分厂家有
        $details = json_decode($details, true);
        $soft_version = input('post.soft_version'); //soft_version
        $hard_version = input('post.hard_version'); //hard_version
        $ccid = input('post.ccid'); //hard_version
        $signal = input('post.signal', 0, 'intval');
        $type = input('post.type'); //type
        $bn = input('post.bn', 0, 'intval');
        $bn < 1 && $bn = 1; //主板数量

        !is_array($details) && $details = [];

        $check = $this->dispatch();
        $result = json_decode($check, true);
        if (1 != $result['status']) {
            return $check;
        }
        $code = $result['data']['operator_code'];
        $this->_getDb($code);

        $ret = ['status' => 1, 'ocode' => $code];

        if ($type == 'zd') {
            $ver = str_replace('FW_V', '', $soft_version);
            $ver = intval($ver);
            save_log('login', $ver . '-' . $cabinet_id);
        }


        $cabinet = $this->db->name('charecabinet')
            ->where(['cabinet_id' => $cabinet_id, 'not_delete' => 1])
            ->find();

        if (!$cabinet) {
            //return json_encode(['status' => 2, 'msg' => '设备不存在'], JSON_UNESCAPED_UNICODE);//登陆失败
        }

        $cache = [
            'type' => $type,
            'register' => time(),
            'heart_time' => time(),
            'oCode' => $code,
            'ip' => $ip,
            'client_id' => $client_id,
            'stock_num' => count($details),
            'is_busy' => false,
            'is_online' => true,
            'hex' => $hex,
//            'open_status' => [],//锁孔开锁状态
            'details' => [],//电池详情
            'soft_version' => $soft_version,
            'hard_version' => $hard_version,
            'ccid' => $ccid,
            'signal' => $signal,
            'bn' => $bn,
        ];
        $login_cache = cache("dev-lid:{$cabinet_id}");

        $login_num = 1;
        if ($login_cache) {
            if ($login_cache['date'] == date("Y-m-d")) {//当天
                $login_num = $login_cache['num'] + 1;
            }
            cache("dev-lid:{$cabinet_id}", ['date' => date("Y-m-d"), 'num' => $login_num], 86400 * 2);
        }
        $cache['login_num'] = $login_num;

        //将登陆信息写入缓存
        $this->storage->setEquipment($cabinet_id, $cache);
        $this->storage->setClient($client_id, $cabinet_id);
        $this->_updateStock($cabinet_id, $details);
        $this->_getDb();
        $this->db->name('charecabinet')->where(['id' => $cabinet['id']])->update(['heart_time' => time(), 'is_online' => 1, 'network_card' => $ccid]);
        return json_encode($ret);
    }

    public function dispatch()
    {
        return json_encode(['status' => 1], JSON_UNESCAPED_UNICODE);
    }

    //分发设备

    private function _getDb($code = '')
    {
        $this->db = Db::connect();
    }

    /**
     * 更新库存
     */
    private function _updateStock($cabinet_id, $details, $aims = 1)
    {
        if (!is_array($details)) {
            return;
        }
        $aims < 1 && $aims = 1;//主板序列

        $data = [];
        $battery_ids = [];//电池
        foreach ($details as $k => $v) {
            $data[$k] = [
                'bid' => $v['battery_id'],
                'power' => $v['power'],
                'lock' => $k,
                'fault' => false,
            ];
            $battery_ids[] = $v['battery_id'];
            \think\Loader::model('BatteryLog', 'logic')->deviceLog($cabinet_id, $v['battery_id']);
        }


        //更新缓存
        $cache = $this->storage->getEquipment($cabinet_id);
        if ($cache['bn'] > 1) {//多主板
            $index = ($aims - 1) * 12;
            for ($i = 1; $i < 13; $i++) {
                $key = $index + $i;
                unset($cache['details'][$key]);
            }
            foreach ($data as $k => $v) {
                $key = $index + $k;
                $cache['details'][$key] = $v;
            }
        } else {
            $cache['details'] = $data;
        }
        $cache['stock_num'] = count($cache['details']);
        $this->storage->setEquipment($cabinet_id, $cache);
        return $battery_ids;
    }

    /**
     * 心跳
     */
    public function heartbeat()
    {
        $cabinet_id = input('post.cabinet_id'); //机柜ID
        $ip = input('post.ip'); //客户端IP地址
        $client_id = input('post.client_id'); //连接ID
        $hex = input('post.hex'); //十六进制信息,部分厂家有
        $details = input('post.details');
        $details = json_decode($details, true);
        $signal = input('post.signal', 0, 'intval');

        $cache = $this->storage->getEquipment($cabinet_id);
        if ($cache) {
            $cache['is_online'] = true;
            $cache['heart_time'] = time();
            $cache['signal'] = $signal;
            $this->storage->setEquipment($cabinet_id, $cache);
            $this->listHandler->rPush('heart', $cabinet_id);
            $this->_getDb();
            $this->db->name('charecabinet')
                ->where('cabinet_id', $cabinet_id)
                ->update(['heart_time' => time(), 'is_online' => 1]);

            return json_encode(['status' => 1]);
        }
        return json_encode(['status' => 2]);
    }


    //借出充电宝返回状态

    /**
     * 库存明细
     */
    public function stockDetail()
    {
        $cabinet_id = input('post.cabinet_id'); //机柜ID
        $details = input('post.details'); //客户端IP地址
        $oid = input('post.oid'); //流水ID
        $hex = input('post.hex'); //十六进制信息,部分厂家有
        $aims = input('post.aims', 1, 'intval'); //主板序列
        $aims < 1 && $aims = 1;

        $details = json_decode($details, true);
        empty($details) && $details = [];

        $data = [];
        $battery_ids = [];//电池
        foreach ($details as $k => $v) {
            $data[$k] = [
                'bid' => $v['battery_id'],
                'power' => $v['power'],
                'lock' => $k,
                'fault' => false,
                'is_fault' => !empty($v['fault']) ? true : false,
            ];
            if (!empty($v['fault'])) {
                save_log('fault_battery', $v['battery_id']);
            }
            $battery_ids[] = $v['battery_id'];
            \think\Loader::model('BatteryLog', 'logic')->deviceLog($cabinet_id, $v['battery_id']);
        }
        $this->_getDb();
        if ($battery_ids) {
            $logic = \think\Loader::model('Lease', 'logic');
            //检查下是否有未结束的异常订单
            $orders = $this->db->name('order_active')
                ->field('id')
                ->where(['battery_id' => ['IN', $battery_ids], 'status' => ['IN', [1, 4]], 'type' => 1])
                ->select();

            foreach ($orders as $order) {
                $logic->endLease($order['id'], $cabinet_id);
            }

            //注册电池编号
            $equipment = $this->db->name('charecabinet')
                ->where(['cabinet_id' => $cabinet_id, 'not_delete' => 1])
                ->find();
            if ($equipment) {
                $batteries = $this->db->name('battery')
                    ->where(['device_id' => ['IN', $battery_ids], 'not_delete' => 1])
                    ->column('device_id');
                $battery_ids = array_diff($battery_ids, $batteries);
                foreach ($battery_ids as $id) {
                    $this->db->name('battery')
                        ->insert(['device_id' => $id, 'not_delete' => 1, 'num' => 0, 'create_time' => time()]);
                }
            }
        }
        //更新缓存
        $cache = $this->storage->getEquipment($cabinet_id);

        if ($cache['bn'] > 1) {//多主板
            $index = ($aims - 1) * 12;
            for ($i = 1; $i < 13; $i++) {
                $key = $index + $i;
                unset($cache['details'][$key]);
            }
            foreach ($data as $k => $v) {
                $key = $index + $k;
                $cache['details'][$key] = $v;
            }
        } else {
            $cache['details'] = $data;
        }


        //isset($cache['ope'][$oid]) && $cache['ope'][$oid] = ['status' => 1, 'hex' => $hex]; //更新回调
        $cache['ope_stockDetail']['stock_num'] = count($cache['details']);
        $cache['stock_num'] = count($cache['details']);
        $cache['ope_stockDetail']['status'] = 1;
        $cache['ope_stockDetail']['ope_hex'] = $hex;
        $cache['is_online'] = true;
        $cache['heart_time'] = time();
        $this->storage->setEquipment($cabinet_id, $cache);

        $this->db->name('charecabinet')
            ->where('cabinet_id', $cabinet_id)
            ->update(['heart_time' => time()]);
        return json_encode(['status' => 1, 'msg' => '更新库存' . $cabinet_id], JSON_UNESCAPED_UNICODE);
    }

    public function borrowDevice()
    {
        $cabinet_id = input('post.cabinet_id'); //机柜ID
        $lock_id = input('post.lock_id', 0, 'intval'); //锁ID
        $finally = input('post.finally'); //状态
        $battery_id = input('post.battery_id'); //充电宝ID
        $hex = input('post.hex'); //十六进制信息,部分厂家有
        $error = input('post.error'); //错误
        $aims = input('post.aims', 1, 'intval'); //主板序列
        $details = input('post.details'); //库存,部分厂家有
        $details = json_decode($details, true);
        !is_array($details) && $details = [];


        $cache = $this->storage->getEquipment($cabinet_id);
        if (!$lock_id) {//部分厂家只有电池编号
            foreach ($cache['details'] as $key => $val) {
                if ($battery_id == $val['bid']) {
                    $lock_id = $val['lock'];
                    break;
                }
            }
        }
        empty($battery_id) && $battery_id = $cache['details'][$lock_id]['bid'];
        $status = (1 == $finally) ? 1 : 2; //开锁状态
        $cache['lock_status'][$lock_id]['status'] = $status;
        $cache['lock_status'][$lock_id]['hex'] = $hex;
        $cache['lock_status'][$lock_id]['error'] = $error;
        $cache['is_online'] = true;
        $cache['heart_time'] = time();
        $this->storage->setEquipment($cabinet_id, $cache);
        \think\Loader::model('BatteryLog', 'logic')->openLog($cabinet_id, $battery_id, $lock_id);

        $this->_updateStock($cabinet_id, $details, $aims);
        return json_encode(['status' => 1, 'msg' => '借出充电宝'], JSON_UNESCAPED_UNICODE);
    }

    //归还充电宝

    public function returnBattery()
    {
        $cabinet_id = input('post.cabinet_id'); //机柜ID
        $lock_id = input('post.lock_id', 0, 'intval'); //锁ID
        $battery_id = input('post.battery_id'); //充电宝ID
        $aims = input('post.aims', 1, 'intval'); //主板序列
        $details = input('post.details');
        $details = json_decode($details, true);

        $this->_getDb();
        $this->db->name('charecabinet')
            ->where('cabinet_id', $cabinet_id)
            ->update(['heart_time' => time(), 'is_online' => 1]);

        //查询数据库充电宝记录表，判断是否有该id记录
        $battery = $this->db->name('battery')
            ->where(['device_id' => $battery_id, 'not_delete' => 1])
            ->find();


        //禁止锁孔
        $device = $this->db->name('charecabinet')
            ->where(['cabinet_id' => $cabinet_id, 'not_delete' => 1])
            ->find();
        $forbid_locks = empty($device['forbid_locks']) ? [] : explode(",", $device['forbid_locks']);


        //充电宝不存在,强制弹出
        if (!$battery || (!empty($forbid_locks) && in_array($lock_id, $forbid_locks))) {
            $producer = input('post.producer');
            if ('zd' == $producer) {
                return json_encode(['status' => 2, 'msg' => '充电宝不存在'], JSON_UNESCAPED_UNICODE);
            }
            $params = [
                'command' => 250,//指令
                'equipment_id' => $cabinet_id,
                'battery_id' => $battery_id,
                'lock_id' => $lock_id,
            ];
            \think\Loader::model('Command', 'service')
                ->initData($cabinet_id)
                ->send($params);
            return json_encode(['status' => 2, 'msg' => '充电宝不存在'], JSON_UNESCAPED_UNICODE);
        }
        $cache = $this->storage->getEquipment($cabinet_id);
        $cache['open_status'][$lock_id] = ['bid' => $battery_id, 'time' => time()];
        $cache['is_online'] = true;
        $cache['heart_time'] = time();
        $this->storage->setEquipment($cabinet_id, $cache);
        $this->_updateStock($cabinet_id, $details, $aims);
        //结束订单
        $order_id = $this->db->name('order_active')
            ->where(['battery_id' => $battery_id, 'status' => 1, 'type' => 1])
            ->value('id');
        if (!$order_id) {
            return json_encode(['status' => 1, 'msg' => '没有订单'], JSON_UNESCAPED_UNICODE);
        }
        \think\Loader::model('Lease', 'logic')->endLease($order_id, $cabinet_id);
        return json_encode(['status' => 1, 'msg' => '结束订单'], JSON_UNESCAPED_UNICODE);

    }

    /**
     * 检修
     */
    public function maintain()
    {
        $cabinet_id = input('post.cabinet_id'); //机柜ID
        $lock_id = input('post.lock_id', 0, 'intval'); //锁ID
        $finally = input('post.finally'); //状态
        $error = input('post.error'); //错误
        $hex = input('post.hex'); //十六进制信息,部分厂家有
        $battery_id = input('post.battery_id'); //充电宝ID
        $aims = input('post.aims', 1, 'intval'); //主板序列
        $details = input('post.details');
        $details = json_decode($details, true);

        $cache = $this->storage->getEquipment($cabinet_id);
        if (!$lock_id) {//部分厂家只有电池编号
            foreach ($cache['details'] as $key => $val) {
                if ($battery_id == $val['bid']) {
                    $lock_id = $val['lock'];
                    break;
                }
            }
        }
        empty($battery_id) && $battery_id = $cache['details'][$lock_id]['bid'];
        $status = (1 == $finally) ? 1 : 2; //开锁状态

        $cache['lock_status'][$lock_id]['status'] = $status;
        $cache['lock_status'][$lock_id]['hex'] = $hex;
        $cache['lock_status'][$lock_id]['error'] = $error;
        $cache['is_online'] = true;
        $cache['heart_time'] = time();
        $this->storage->setEquipment($cabinet_id, $cache);
        \think\Loader::model('BatteryLog', 'logic')->openLog($cabinet_id, $battery_id, $lock_id);

        $this->_updateStock($cabinet_id, $details, $aims);
        return json_encode(['status' => 1]);
    }


    //注销登陆
    public function logout()
    {
        $client_id = Request::instance()->post('client_id'); //连接ID
        $cabinet_id = $this->storage->getClient($client_id);

        if ($cabinet_id) {
            $cache = $this->storage->getEquipment($cabinet_id);
            if ($cache && $cache['client_id'] == $client_id) {
                $cache['is_online'] = false;
                $this->storage->setEquipment($cabinet_id, $cache);
            }
        }
        $this->storage->removeClient($client_id);
        return json_encode(['status' => 1]);
    }


    /**
     * 返回重启状态
     */
    public function restart()
    {
        $cabinet_id = input('post.cabinet_id'); //机柜ID
        $status = input('post.status', 0, 'intval'); //更新状态
        $hex = input('post.hex'); //十六进制信息
        if (1 == $status) {
            $cache = $this->storage->getEquipment($cabinet_id);
            $cache['ope_restart'] = ['status' => $status, 'hex' => $hex];
            $this->storage->setEquipment($cabinet_id, $cache);
        }
    }


    /**
     *
     * 屏幕登陆信息
     * 1 登陆成功； 2 失败
     */
    public function screenLogin()
    {
        $cabinet_id = input('post.cabinet_id'); //机柜ID
        $client_id = input('post.client_id'); //连接ID
        $version = input('post.version');
        $this->_getDb();
        $cabinet = $this->db->name('charecabinet')
            ->where(['cabinet_id' => $cabinet_id, 'not_delete' => 1])
            ->find();

        if (!$cabinet) {
            $cabinet = ['qrcode' => $cabinet_id];
            //return json_encode(['status' => 2, 'msg' => '设备不存在'], JSON_UNESCAPED_UNICODE);//登陆失败
        }
        $model = 'vertical';
        $fix = substr($cabinet_id, 0, 5);
        if ($fix == 'CT054') {
            $model = 'cross';
        } else if ($fix == 'CT053') {
            $model = 'cross_touch';
        }

        $params = ['status' => 1,
            'qrcode' => config('qrcodeurl') . "/Lease?o=l54=&&t=" . $cabinet['qrcode'],
            'lang' => 'zh',
            'menu' => '扫码租借',
            'volume' => 90,
            'model' => $model,
            'intro' => '请扫描屏幕上的二维码进行租借充电宝',
        ];
        return json_encode($params, JSON_UNESCAPED_UNICODE);
    }
}

