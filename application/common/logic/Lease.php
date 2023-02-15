<?php

namespace app\common\logic;

use think\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


/**
 * 租借
 * Class Lease
 * @package app\common\logic
 */
class Lease extends Common
{

    var $pay_auth_id = 0;
    var $pay_auth_no = 0;

    /**
     * 租借充电宝
     * @param $user
     * @param $device_code
     */
    public function deviceLease($user, $device_code, $payment_no)
    {
        set_time_limit(60);
        $user_lock = "lease:{$this->oid}:{$user['id']}";
        $lock = cache($user_lock);
        if ($lock) {
            return ['code' => 0, 'msg' => lang('正在处理中，请稍后')];
        }
        cache($user_lock, 1, 45);

        $info = $this->getDeviceInfo($device_code);
        if (1 != $info['code']) {
            cache($user_lock, null);
            return $info;
        }

        $device = $info['device'];
        if (!empty($device['is_fault'])) {
            cache($user_lock, null);
            return ['code' => 0, 'msg' => lang('设备维护中，请扫码其他设备')];
        }

        $operator = $info['data'];
        $seller = $info['seller'];

        //判断机柜是否在线
        $cache = $this->getEquipmentCache($device['cabinet_id']);
        if (!$cache || time() - $cache['heart_time'] > config('online_time')) {
            cache($user_lock, null);
            return ['code' => 0, 'msg' => lang('机柜不在线')];
        }

//        //查询当前是否未归还订单存在
//        $order = $this->checkUserOrder($user['id']);
//        if ($order) {
//            cache($user_lock, null);
//            return ['code' => 2, 'msg' => lang('当前有正在租赁的订单'), 'data' => ['order_no' => $order['order_no'], 'order_type' => $order['type']]];
//        }

        $recharge = $this->getRechargeAmount($user, $operator);
        if ($recharge > 0) {
            cache($user_lock, null);
            return ['code' => 0, 'msg' => lang('余额不足,请充值'), 'data' => ['amount' => $recharge, 'device_code' => $device_code]];
        }


        //执行弹出充电宝指令
        try {
            $battery_power = $this->getOperatorConfig('battery_power');
            $service = \think\Loader::model('Command', 'service')->initData($device['cabinet_id']);
            $service->setLowPower($battery_power);

            //获取库存
            $stock = $service->stockDetail();
            if ($stock['status'] != 1) {
                cache($user_lock, null);
                save_log('lease_offline', $device['cabinet_id'] . ":{$this->oCode}:{$user['id']}");
                return ['code' => 0, 'msg' => lang('机柜不在线')];
            }
        } catch (\Exception $e) {
            Log::record('开始租赁接口报错：' . $e->getMessage(), 'error');
            cache($user_lock, null);
            return ['code' => 0, 'msg' => lang('弹出失败，请重新扫码')];
        }

        unset($operator['recharge_amount'], $operator['wired_amount'], $operator['device_code']);

        $brokerage = $this->_getBrokerage($device, $seller);
        $order_no = date('YmdHis') . rand(1000, 9999);
        $month = date("Ym");
        $params = array(
            'order_no' => $order_no,
            'type' => 1,
            'status' => 1,
            'uid' => $user['id'],
            'sid' => (int)$device['sid'],
            'employee_id' => (int)$device['employee_id'],
            'agency_id' => (int)$device['agency_id'],
            'app_type' => $user['app_type'],
            'deposit' => $operator['deposit'],
            'start_time' => time(),
            'expire_time' => $this->getExpireTime($operator),
            'device_id' => $device['id'],
            'device_code' => $device['cabinet_id'],
            'billing_data' => json_encode($operator, JSON_UNESCAPED_UNICODE),
            'brokerage_data' => json_encode($brokerage, JSON_UNESCAPED_UNICODE),
            'lease_address' => $seller['address'],
            'payment_no' =>$payment_no,
            'create_time' => time(),
        );

        //用户订单
        $user_params = array(
            'uid' => $user['id'],
            'order_no' => $order_no,
            'type' => 1,
            'status' => 1,
            'start_time' => time(),
            'expire_time' => $params['expire_time'],
        );
        $agency_ids = array_column($brokerage, 'id', 'id');
        $this->db->startTrans();
        try {
            $order_table = getTableNo('lease_order', 'date', $month);
            $order_id = $this->db->name($order_table)->insertGetId($params);
            //用户订单表
            $user_table = getTableNo('order_user', 'hash', 16, $user['id']);
            $this->db->name($user_table)->insert($user_params);
            $sql = " UPDATE user SET deposit = deposit + '{$operator['deposit']}',
                        balance = balance-'{$operator['deposit']}'
                        WHERE id = '{$user['id']}'";
            save_log('balance', "{$user['id']}扣除押金{$operator['deposit']}");
            $this->db->execute($sql);
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            save_log('error', '租借出错' . $e->getMessage());
            save_log('error', $params);
            cache($user_lock, null);
            return ['code' => 0, 'msg' => lang('生成订单失败，请联系客服')];
        }

        try {
            $result = $service->borrowDevice();
        } catch (\Exception $e) {
            cache($user_lock, null);
            Log::record('开始租赁接口报错：' . $e->getMessage(), 'error');
        }
    
        if (1 == $result['status']) { //出借成功
            $lock_id = $result['lock_id'];
            $params['battery_id'] = $result['battery_id'];
            $params['start_time'] = time();
            $params['expire_time'] = $this->getExpireTime($operator);
            $params['create_time'] = time();

            //订单月表
            $now_month = date("Ym");
            if ($now_month == $month) {
                $this->db->name($order_table)
                    ->where(['id' => $order_id])
                    ->update(['battery_id' => $params['battery_id'], 'start_time' => $params['start_time'], 'expire_time' => $params['expire_time']]);
            } else { //跨月数据
                $params['order_no'] = date('YmdHis') . rand(1000, 9999);
                $this->db->name($order_table)->where(['id' => $order_id])->delete(); //删除原有的
                $order_table = getTableNo('lease_order', 'date', $now_month);
                $this->db->name($order_table)->insertGetId($params);
            }
            //用户表
            $this->db->name($user_table)->where(['order_no' => $params['order_no']])->update(['start_time' => $params['start_time'], 'expire_time' => $params['expire_time']]);
            //进行中的订单
            $order_id = $this->db->name('order_active')->insertGetId($params);

            //代理商
            $agency_params = array(
                'order_no' => $params['order_no'],
                'type' => 1,
                'relation_id' => 0,
                'sid' => (int)$device['sid'],
                'uid' => $user['id'],
                'agency_id' => $params['agency_id'],
                'employee_id' => $params['employee_id'],
                'app_type' => $user['app_type'],
                'start_time' => $params['start_time'],
                'status' => 1,
                'device_id' => $params['device_id'],
                'battery_id' => $params['battery_id'],
                'is_self' => 1,
                'is_credit' => empty($params['pay_auth_id']) ? 0 : 1,
            );
            $agency_table = getTableNo('order_agency', 'date', $now_month);
            $agency_data = [];
            foreach ($brokerage as $v) {
                $v['id'] = intval($v['id']);
                $agency_params['relation_id'] = $v['id'];
                $agency_params['is_self'] = 1;
                if ($v['type'] == 1 && $agency_params['relation_id'] !== $params['agency_id']) {
                    $agency_params['is_self'] = 0;
                }
                $agency_data[] = $agency_params;
            }
            $agency_data && $this->db->name($agency_table)->insertAll($agency_data);

            $this->db->name('battery_order')->insertGetId([
                'battery_id' => $result['battery_id'],
                'order_id' => $order_id,
                'create_time' => time(),
            ]);


            $this->db->name('charecabinet')->where(['id' => $params['device_id']])->setInc('total_num');
            !empty($device['sid']) && $this->db->name('seller')->where(['id' => $device['sid']])->setInc('total_num');

            //用户统计
            $this->db->name('user')->where(['id' => $user['id']])
                ->update([
                    'battery_num' => ['exp', 'battery_num+1'],
                    'order_num' => ['exp', 'order_num+1'],
                    'last_order' => time(),
                ]);

            //统计客户端
            $app_type = array_flip(config('app_type'));
            $client = $app_type[$user['app_type']];
        
            //电池日志
            \think\Loader::model('BatteryLog', 'logic')->leaseLog($params['device_code'], $result['battery_id'], $user['id'], $params['order_no']);

            //电池租借次数
            $this->db->name('battery')->where(['device_id' => $result['battery_id'], 'not_delete' => 1])->setInc('num');

            //统计
            $stat_text = [];
            $stat_text[] = statSql('stat_operator', ['date' => date("Y-m-d")], ['battery_num' => 1, 'total_num' => 1]); //平台统计
            $stat_text[] = statSql('stat_seller', ['date' => date("Y-m-d"), 'sid' => $params['sid']], ['battery_num' => 1, 'total_num' => 1]); //店铺统计
            $stat_text[] = statSql('stat_device', ['date' => date("Y-m-d"), 'device_id' => $params['device_code']], ['create_num' => 1]); //设备统计
            foreach ($brokerage as $adata) {
                $stat_text[] = statSql('stat_agency', ['date' => date("Y-m-d"), 'agency_id' => $adata['id']], ['battery_num' => 1, 'total_num' => 1]);
            }

            foreach ($agency_ids as $aid) {
                $stat_text[] = statSql('stat_agency_seller', ['date' => date("Y-m-d"), 'sid' => $params['sid'], 'relation_id' => $aid], ['order_num' => 1]); //平台统计
                $stat_text[] = statSql('stat_agency_device', ['date' => date("Y-m-d"), 'device_id' => $params['device_code'], 'relation_id' => $aid], ['order_num' => 1]); //平台统计
            }
            cache($user_lock, null);
            $res = ['code' => 1, 'data' => ['order_no' => $params['order_no'], 'device_num' => $device['device_num'], 'lock_id' => $lock_id]];
            statText($this->oCode, $stat_text);
            return $res;
        }


        $error_msg = lang('此机柜没有网络信号，请重新开机，然后扫二维码');
        (2 == $result['status']) && $error_msg = lang('弹出失败，请重新扫码');
        (0 === $result['status']) && $error_msg = $result['msg'];
        $this->invalidOrder($order_id, $params, $error_msg,$operator,$user);
        cache($user_lock, null);
        return ['code' => 0, 'msg' => $error_msg];
    }

    /**
     * 获取充电柜信息
     * @param $code
     */
    public function getDeviceInfo($code)
    {
        $device = $this->db->name('charecabinet')
            ->where(['qrcode' => $code, 'not_delete' => 1])
            ->find();
        if (!$device) {
            return ['code' => 0, 'msg' => lang('设备信息不存在'),'data'=> ['ResultCode' => 'C2B00012', 'ResultDesc' => 'Invalid Account Number'] ];
        }
        $ret = $this->getPrice($device['sid']);
        if (1 != $ret['code']) {
            return ['code' => 0, 'msg' => lang('设备信息不存在'),'data'=>['ResultCode' => 'C2B00012', 'ResultDesc' => 'Invalid Account Number']];
        }
        $info = $ret['data'];
        $info['device_code'] = $code;
        return ['code' => 1, 'data' => $info, 'device' => $device, 'seller' => $ret['seller']];
    }

    /**
     * 获取定价
     * @param $sid
     * @return array
     */
    public function getPrice($sid)
    {
        $operator = $this->getOperatorConfig('charge_info');
        $operator = json_decode($operator, true);
        if (!$operator) {
            return ['code' => 0, 'msg' => lang('价格信息异常')];
        }
        $sid = intval($sid);
        $seller = [];
        if ($sid) { //有商户则取商户信息
            $seller = $this->db->name('seller')
                ->where(['id' => $sid, 'not_delete' => 1])
                ->find();
            unset($seller['password'], $seller['create_time'], $seller['update_time']);
            if ($seller && 2 == $seller['billing_type']) {
                $set = json_decode($seller['billing_set'], true);
                $operator = array_merge($operator, $set);
            }
        }
        return ['code' => 1, 'data' => $operator, 'seller' => $seller];
    }

   

    //获取需要充值金额

    /**
     * 检测用户有未归还订单
     * @param $uid
     */
    public function checkUserOrder($uid)
    {
        $order = $this->db->name('order_active')
            ->where(['uid' => $uid, 'status' => ['IN', [1, 4]], 'type' => 1])
            ->find();
        return $order;
    }

    /**
     * 检测用户有未归还订单
     * @param $uid
     */
    public function checkUserAmount($uid)
    {
        $user = $this->db->name('user')
            ->where(['id' => $uid])
            ->find();
        $amount = bcadd($user['balance'],$user['deposit'],2);
        return $amount;
    }


    /**
     * 获取分成比例
     * @param $device
     * @param $seller
     */
    function _getBrokerage($device, $seller)
    {
        $identity = config('user_type');
        $params = [
            'type' => $identity['seller'],
            'id' => (int)$seller['manager_id'],
            'ratio' => (int)$seller['brokerage'],
        ];
        //如果有归属店铺，则按店铺所设分成比，否则按用户分成比
        $employee_id = $device['employee_id'];
        $agency_id = $device['agency_id'];
        $brokerage = 0;
        $data = [];
        $data2 = [];

        if ($seller) { //有商家
            if (!empty($seller['manager_id'])) { //商家提成
                $data[] = $params;
                $seller['brokerage'] > 0 && $brokerage += $seller['brokerage'];
            }

            if ($seller['employee_id']) { //业务提成
                $employee = $this->db->name('agency')
                    ->where(['id' => $seller['employee_id'], 'not_delete' => 1, 'type' => 2])
                    ->find();
                if ($employee && $employee['status'] == 1) {
                    $params['type'] = $identity['employee'];
                    $params['id'] = $seller['employee_id'];
                    $params['ratio'] = $seller['employee_brokerage'];
                    $data[] = $params;
                    $seller['employee_brokerage'] > 0 && $brokerage += $seller['employee_brokerage'];
                }
            }
            $employee_id = 0;
        }


        if (!empty($employee_id)) { //没有商家则获取设备所属业务员
            $employee = $this->db->name('agency')
                ->where(['id' => $employee_id, 'not_delete' => 1, 'type' => 2])
                ->find();
            if ($employee && $employee['status'] == 1) {
                $params['type'] = $identity['employee'];
                $params['id'] = $employee_id;
                $params['ratio'] = $employee['brokerage'];
                $data[] = $params;
                $employee['brokerage'] > 0 && $brokerage += $employee['brokerage'];
            }
        }


        !empty($agency_id) && $agency = $this->db->name('agency')
            ->where(['id' => $agency_id, 'not_delete' => 1, 'type' => 1])
            ->find();

        if ($agency) { //底级代理商
            $params['type'] = $identity['agency'];
            $params['id'] = $agency['id'];
            $params['ratio'] = $agency['brokerage'];
            $data2[] = $params;
        }


        if ($agency['parents']) { //所有父级代理

            $parentAgency = $this->db->name('agency')
                ->field('id,name,parent_id,brokerage')
                ->where(['not_delete' => 1, 'type' => 1, 'status' => 1, 'id' => ['IN', $agency['parents']]])
                ->order(' FIELD (id,' . $agency['parents'] . ') ')
                ->select();
            $parentAgency = array_reverse($parentAgency);


            //echo $this->db->name('agency')->getLastSql();exit;
            foreach ($parentAgency as $v) {
                $params['type'] = $identity['agency'];
                $params['id'] = $v['id'];
                $params['ratio'] = $v['brokerage'];
                $data2[] = $params;
            }
        }


        //计算最终分成比
        foreach ($data2 as $k => $v) {
            $ratio = $brokerage;
            $brokerage = $v['ratio'];
            $v['ratio'] = $v['ratio'] - $ratio;
            if (empty($v['ratio'])) {
                $data[] = [
                    'id' => $v['id'],
                    'type' => $v['type'],
                    'ratio' => 0,
                ];
                continue;
            }
            if ($v['ratio'] < 0) {
                save_log('error', "分成比例错误");
                save_log('error', $device);
                save_log('error', $v);
                return [];
                exit; //调试期间直接报错
            }
            $data[] = [
                'id' => $v['id'],
                'type' => $v['type'],
                'ratio' => $v['ratio'],
            ];
        }
        $sum = array_sum(array_map(function ($val) {
            return $val['ratio'];
        }, $data));
        if ($sum > 100) {
            save_log('error', "分成比例错误");
            save_log('error', $device);
            return [];
            exit; //调试期间直接报错
        }

        return $data;
    }

    //获取超时时间
    function getExpireTime($set)
    {
        $deposit = $set['deposit']; //押金
        $day = 0; //天
        $minute = intval($set['freetime']); //免费分钟
        $set['billingtime'] = trim($set['billingtime']);
        $set['billingtime'] = intval($set['billingtime']);
        $set['billingtime'] < 1 && $set['billingtime'] = 1;
        //每天封顶
        if (1 == $set['billingunit']) { //小时
            $day_amount = ceil(24 / $set['billingtime']) * $set['amount'];
        } else { //分钟
            $day_amount = ceil(1440 / $set['billingtime']) * $set['amount'];
        }
        $set['ceiling'] = floatval($set['ceiling']);
        $set['ceiling'] > $day_amount && $set['ceiling'] = $day_amount;
        if (!empty($set['ceiling'])) { //每天有封顶
            $day = floor($deposit / $set['ceiling']); //多少天
            $deposit = ($deposit * 100) % ($set['ceiling'] * 100);
            $deposit = priceFormat($deposit / 100);
        }
        //多少个单位
        $unit = ceil($deposit / $set['amount']);
        $unit = $unit * $set['billingtime'];
        if (1 == $set['billingunit']) { //小时
            $minute += $unit * 60;
        } else {
            $minute += $unit;
        }
        $minute += $day * 24 * 60;
        $time = $minute * 60;

        //        echo "day:$day ,";
        //        echo "deposit:$deposit ,";
        //        echo "unit:$unit ,";
        //        echo "minute:$minute ,";
        //        echo date("Y-m-d H:i:s",$time + time());
        return $time + time();
    }


    //取消订单
    function invalidOrder($order_id, $order, $remark = '',$operator,$user)
    {
        $user_table = getTableNo('order_user', 'hash', 16, $order['uid']);
        $this->db->name($user_table)->where(['order_no' => $order['order_no']])->update(['status' => 3, 'end_time' => time()]);

        $order_table = getTableNo('lease_order', 'date', substr($order['order_no'], 0, 6));
        $this->db->name($order_table)->where(['order_no' => $order['order_no']])->update(['status' => 3, 'end_time' => time(), 'remark' => $remark, 'update_time' => time()]);
        //退押金回余额
        $this->db->name('user')->where(['id' => $user['id']])->update([
            'balance' => ['exp', 'balance+' . $operator['deposit']],
            'deposit' => ['exp', 'deposit-' . $operator['deposit']]
        ]);
        save_log('balance', "{$user['id']}租借失败退押金{$operator['deposit']}");
    }


    /**
     * 检测用户有未支付订单
     * @param $uid
     */
    public function checkUserUnpaidOrder($uid)
    {
        $user_table = getTableNo('order_user', 'hash', 16, $uid);
        $order = $this->db->name($user_table)
            ->where(['uid' => $uid, 'status' => 2, 'is_pay' => 0])
            ->find();
        return $order;
    }

    /**
     * 计算时间差
     * @param int $start 开始
     * @param int $end 结束
     * @return array
     */
    function calLeaseTime($start, $end)
    {
        if ($end <= $start) {
            return '0' . lang('秒');
        }
        $timediff = $end - $start;
        $text = [];
        if ($timediff >= 3600) {
            $hours = floor($timediff / 3600);
            $text[] = $hours > 1 ? "   {$hours} " . lang('小时') : "   {$hours} " . lang('小时');
        }
        $remain = $timediff % 3600;
        $mins = floor($remain / 60);
        $text[] = $mins > 1 ? "   {$mins} " . lang('分') : "   {$mins} " . lang('分');
        $secs = $remain % 60;
        $text[] = $secs > 1 ? "   {$secs} " . lang('秒') : "   {$secs} " . lang('秒');

        return implode("", $text);
    }

    //格式化时间
    function formatTime($time)
    {
        return date("Y/m/d H:i:s", $time);
    }


    //收费信息
    function showBilling($billing_data)
    {
        $info = json_decode($billing_data, true);
        $unit = (1 == $info['billingunit']) ? lang('小时') : lang('分钟');
        if ( $info['billingtime'] > 1 ) {
            $unit = (1 == $info['billingunit']) ? lang('小时s') : lang('分钟s');
        }
        $billing = lang('元') . $info['amount'] .  "/" . ($info['billingtime'] == 1 ? '' : $info['billingtime']) . $unit;
        return $billing;
    }

    //订单分成数据
    function calBrokerage($order)
    {
        $brokerage_data = json_decode($order['brokerage_data'], true);
        $amount = $order['amount'];
        isset($order['brokerage_amount']) && $amount = $order['brokerage_amount'];
        $extend = [];
        $agency_ids = array_column($brokerage_data, 'id', 'id');
        !isset($order['id']) && $order['id'] = 0; //密码线租借前没有订单id
        $brokerage_params = [];
        $total_brokerage = 0;
        if ($amount > 0.01) {
            $identity = config('user_type');
            foreach ($brokerage_data as $v) {
                $brokerage = $v['ratio'] * $amount / 100;
                if ($brokerage < 0.01) {
                    $brokerage = 0;
                }
                $total_brokerage = bcadd($total_brokerage, $brokerage, 2);
                $brokerage_params[] = [
                    //'order_id' => $order['id'],
                    'order_no' => $order['order_no'],
                    'user_type' => $v['type'],
                    'relation_id' => $v['id'],
                    'ratio' => $v['ratio'],
                    'amount' => $brokerage,
                    'create_time' => time()
                ];
            }
        }
        return ['brokerage' => $brokerage_params, 'extend' => $extend, 'agency_ids' => $agency_ids, 'amount' => $total_brokerage];
    }

    /**
     * 计算借出电池锁孔
     */
    public function borrowBattery($device_ids)
    {
        $query = $this->db->name('battery')->where(['device_id' => ['IN', $device_ids]])->select();
        $uses = [];
        foreach ($query as $k => $v) {
            $uses[$v['device_id']] = $v['num'];
        }
        $device_id = array_search(min($uses), $uses);
        $lock_id = array_search($device_id, $device_ids);
        return $lock_id;
    }

    /**
     * 申诉
     * @param $uid
     * @param $device_code
     * @return array
     */
    public function returnCheck($uid, $device_code)
    {
        set_time_limit(60);
        $info = $this->getDeviceInfo($device_code);
        if (1 != $info['code']) {
            return $this->errorResponse($info['code'], $info['msg']);
        }

        $battery_ids = $this->db->name('order_active')
            ->where(['uid' => $uid, 'type' => 1, 'status' => 1])
            ->column('battery_id');

        if (empty($battery_ids)) {
            return $this->errorResponse(0, lang('没有待归还的订单'));
        }

        //防止多次点击查询，消耗资源
        $lock = cache("return:" . $uid);
        cache("return:" . $uid, time(), 20);
        if ($lock) {
            sleep(10);
        }

        $device = $info['device'];
        $service = \think\Loader::model('Command', 'service')->initData($device['cabinet_id']);
        $result = $service->stockDetail();
        if ($result['status'] != 1) {
            return $this->errorResponse(0, lang('机柜不在线'));
        }
        $return = 0;
        $cache = $this->getEquipmentCache($device['cabinet_id']);
        empty($cache['details']) && $cache['details'] = [];
        foreach ($cache['details'] as $item) {
            if (in_array($item['bid'], $battery_ids)) {
                //结束订单
                $order_id = $this->db->name('order_active')
                    ->where(['battery_id' => $item['bid'], 'status' => 1, 'type' => 1])
                    ->value('id');
                $this->endLease($order_id, $device['cabinet_id']);
                $return++;
            }
        }
        if ($return > 0) {
            return $this->successResponse([], lang("已检测到") . $return . lang("个充电宝已归还，请返回订单列表查看"));
        }
        return $this->successResponse([], lang("提交成功，如还有其它问题请拨打客服电话"));
    }

    //结束租借
    public function endLease($order_id, $device_id = '', $end_time = '', $amount = null, $is_lose = 0)
    {

        //防止并发产生多条分成
        $lock = cache("order:" . $order_id);
        cache("order:" . $order_id, time(), 2);
        if ($lock) {
            sleep(2);
        }

        $order = $this->db->name("order_active")->where(['id' => $order_id])->find();
        if (!$order) {
            return ['code' => 0, 'msg' => lang('订单不存在')];
        }
        if (2 == $order['status']) {
            return ['code' => 0, 'msg' => lang('订单已结束')];
        }
        unset($order['is_late']);
        $order['end_time'] = time();
        !empty($end_time) && $order['end_time'] = $end_time; //指定归还时间

        $free_time = 0;
        $special_user = $this->db->name('special_user')->where(['uid' => $order['uid'], 'sid' => $order['sid']])->find();
        if ($special_user) { //是特殊用户,计算免费时长
            $free_time = $special_user['battery_free'] - $special_user['battery_use'];
            $free_time < 1 && $free_time = 0;
        }

        $billing = $this->calOrderPrice($order, $free_time);

        $use_free = $billing['use_free'];
        $use_point = $billing['use_point'];
        $order['amount'] = $billing['price'];
        if ($amount !== null) {
            $order['amount'] = $amount;
        }

        //查询用户账户信息
        $user = $this->db->name('user')->field('password', true)->where(['id' => $order['uid']])->find();
        //如果订单押金超出用户账户押金余额，则取账户押金
        $order['deposit'] > $user['deposit'] && $order['deposit'] = $user['deposit'];

        //如果订单金额大于订单押金，则取押金金额
        if (bcsub($order['deposit'], $order['amount'], 2) < 0.01) {
            $order['amount'] = $order['deposit'];
        }
        if (bcsub($order['deposit'], $billing['price'], 2) < 0.01) {
            $billing['price'] = $order['deposit'];
        }
        //订单剩余押金
        $deposit = bcsub($order['deposit'], $order['amount'], 2); //押金剩余
        $deposit < 0 && $deposit = 0;
        //修改账户信息,先将押金退至余额
        $user['balance'] = bcadd($user['balance'], $deposit, 2);
        $user['deposit'] = bcsub($user['deposit'], $order['deposit'], 2);
        $user['deposit'] < 0 && $user['deposit'] = 0;
        //$user['deposit'] = 0;
        $params = [
            'end_time' => $order['end_time'],
            'billing_unit' => $billing['unit'],
            'amount' => $order['amount'],
            'original_amount' => $billing['price'] < 0.01 ? 0 : $billing['price'],
            'status' => 2,
            'free_time' => $billing['free_time'],
            'update_time' => time()
        ];
        $remark = input('remark', '', 'trim'); //手动结束
        '' != $remark && $params['remark'] = $remark;


        if ($is_lose > 0) { //丢失了
            $config = $this->getOperatorConfig('charge_info');
            $config = json_decode($config, true);
            $config['device_price'] = priceFormat($config['device_price']);
            $order['brokerage_amount'] = bcsub($order['deposit'], $config['device_price'], 2);
            $order['brokerage_amount'] < 0.01 && $order['brokerage_amount'] = 0;
            $params['is_lose'] = 1;
        }

        $result = $this->calBrokerage($order); //分成
        $brokerage = $result['brokerage'];
        $agency_ids = $result['agency_ids'];
        $brokerage_amount = priceFormat($result['amount']);

        //归还商家
        $device = $this->db->name('charecabinet')
            ->where(['cabinet_id' => $device_id, 'not_delete' => 1])
            ->find();
        if ($device) {
            $params['return_sid'] = $device['sid'];
            $params['return_device_id'] = $device_id;
            $params['return_address'] = $this->db->name('seller')->where(['id' => $device['sid']])->value('address');
        }

        // 启动事务
        $this->db->startTrans();
        try {
            $params['is_pay'] = 1;
            $params['payment_time'] = time();
            $params['payment_amount'] = $params['amount'];
            $order['brokerage_total'] = $params['brokerage_total'] = $brokerage_amount;

            save_log('balance', "{$order['uid']},订单{$order['id']}，押金{$user['deposit']}, 余额{$user['balance']}");
            $this->db->name('user')->where('id', $order['uid'])->update(['deposit' => $user['deposit'], 'balance' => $user['balance']]);

            if ($brokerage) {
                foreach ($brokerage as $k => $v) {
                    $brokerage[$k]['create_time'] = $params['payment_time'];
                }
                $this->db->name('order_brokerage')->insertAll($brokerage);
                $this->db->name('order_brokerage_' . date("Ym", $params['payment_time']))->insertAll($brokerage);
            }

            $month = substr($order['order_no'], 0, 6); //月表
            //订单状态
            $order_table = getTableNo('lease_order', 'date', $month);
            $this->db->name($order_table)->where('order_no', $order['order_no'])->update($params);
            //用户订单表
            $user_table = getTableNo('order_user', 'hash', 16, $order['uid']);
            $user_params = [
                'status' => $params['status'],
                'end_time' => $params['end_time'],
                'amount' => $params['amount'],
            ];
            isset($params['is_pay']) && $user_params['is_pay'] = $params['is_pay'];
            $this->db->name($user_table)->where('order_no', $order['order_no'])->update($user_params);
            //代理商订单表
            $agency_table = getTableNo('order_agency', 'date', $month);
            $agency_params = $user_params;
            isset($params['is_lose']) && $agency_params['is_lose'] = $params['is_lose'];
            $this->db->name($agency_table)->where('order_no', $order['order_no'])->update($agency_params);
            //进行中订单
            $this->db->name('order_active')->where('id', $order['id'])->update($params);
            //丢失订单
            if ($is_lose > 0) {
                $lose_params = $order;
                unset($lose_params['id'], $lose_params['brokerage_amount']);
                $lose_params = array_merge($lose_params, $params);
                $this->db->name('order_lose')->insert($lose_params);
            }

            $this->db->name('battery_order')->where('order_id', $order['id'])->update(['not_delete' => 0]);
            if ($use_free > 0) { //免费试用时长
                $this->db->name('special_user')->where(['id' => $special_user['id']])->setInc('battery_use', $use_free);
            }
            if ($use_point > 0)  {//免费积分时长
                $this->db->name('user')->where(['id' => $order['uid']])->setInc('use_free_time', $use_point);
            }
            if ($params['is_pay'] == 1) { //订单已完成
                $this->db->name('order_active')->where('order_no', $order['order_no'])->delete();
            }
            $this->db->commit();
        } catch (\Exception $e) {
            save_log('error', lang('归还出错') . $e->getMessage());
            save_log('error', $params);
            $this->db->rollback();
            return ['code' => 0, 'msg' => lang('系统繁忙')];
        }

        if ($order['amount'] < 0.01) { //归还统计
            $order['brokerage_total'] = 0;
        }

        $this->deviceStat($order, $agency_ids, $brokerage, 'complete');
        if ( $user['balance'] > 0 ) {//退款
            \think\Loader::model('Payment', 'logic')->refund($user['id'],$user['balance'],'Deposit refund');
        }
        return ['code' => 1, 'msg' => lang('订单已结束')];



    }

    /**
     * 计算订单价格
     * @param $order
     * @return array
     */
    function calOrderPrice($order, $free_time = 0, $point_time = 0)
    {
        $set = json_decode($order['billing_data'], true);
        $end_time = $order['end_time'];
        $time = $end_time - $order['start_time'];
        if ($set['freetime'] != 0) { //判断是否启用免费时长
            $time = $time - $set['freetime'] * 60;
        }

        $minute = ceil($time / 60);
        $minute < 1 && $minute = 0;
        if ($free_time > 0 && $minute > 0) { //特殊用户免费时长
            $free_time = $minute > $free_time ? $free_time : $minute;
        } else {
            $free_time = 0;
        }

        //积分抵扣
        $free = $free_time;
        $point = 0;
        if ( $point_time > 0 && $minute > $free_time ) {
            $m = $minute - $free_time;//剩余收费时长
            $point = $m > $point_time ? $point_time : $m;//积分时长抵扣

            $free_time = $free_time + $point;//合并抵扣
        }

        $time = $time - $free_time * 60;

        $base = ($set['billingunit'] == 1) ? 3600 : 60; //计算基数
        $billingunit = 0; //计费单位
        $amount = 0; //总金额
        if ($time > 0) {
            $onedayamount = (86400 / ($base * $set['billingtime'])) * $set['amount']; //按标准价格一整天的金额
            if ($set['ceiling'] > 0 && $onedayamount > $set['ceiling']) { //一整天的标准价 > 设置的封顶价
                if ($time > 86400) {
                    //除去整天，剩余的计费时长
                    $billingseconds = $time % 86400;
                    $billingunit = ceil($billingseconds / ($base * $set['billingtime']));
                    $amountseconds = $billingunit * $set['amount'];
                    if ($amountseconds > $set['ceiling']) {
                        $amountseconds = $set['ceiling'];
                    }
                    //计费天数
                    $dayamount = (($time - $billingseconds) / 86400) * $set['ceiling']; //按每天封顶金额计算的价格
                    $amount = $dayamount + $amountseconds;
                } else {
                    $billingunit = ceil($time / ($base * $set['billingtime']));
                    $amount = $billingunit * $set['amount'];
                    if ($amount > $set['ceiling']) {
                        $amount = $set['ceiling'];
                    }
                }
            } else { //一整天的标准价 <= 设置的封顶价
                $billingunit = ceil($time / ($base * $set['billingtime']));
                $amount = $billingunit * $set['amount'];
            }
        }
        if (isset($set['deposit']) && $amount > $set['deposit']) {
            $amount = $set['deposit'];
        }

        $amount < 0.01 && $amount = 0;

        return ['unit' => $billingunit, 'price' => floatval($amount), 'free_time' => $set['freetime'] + $free_time, 'use_free' => $free, 'use_point' => $point];
    }

    /**
     * 设备统计
     * @param array $order
     * @param array $agency_ids
     * @param array $brokerage
     * @param string $type
     */
    function deviceStat($order, $agency_ids = [], $brokerage = [], $type = 'complete')
    {
        $stat_text = [];
        if ('complete' == $type) { //完结
            $params = ['battery_amount' => $order['amount'], 'battery_pay_amount' => $order['amount'], 'battery_pay_num' => 1, 'total_amount' => $order['amount'], 'total_pay_amount' => $order['amount'], 'total_pay_num' => 1, 'brokerage_amount' => $order['brokerage_total']];
            $stat_device_params = ['settle_num' => 1, 'amount' => $order['amount'], 'pay_amount' => $order['amount']];
            $relation_params = ['order_amount' => $order['amount'], 'pay_amount' => $order['amount']];
        } else if ('pay' == $type) { //付款
            $params = ['battery_pay_amount' => $order['amount'], 'battery_pay_num' => 1, 'total_pay_amount' => $order['amount'], 'total_pay_num' => 1, 'brokerage_amount' => $order['brokerage_total']];
            $stat_device_params = ['settle_num' => 1, 'pay_amount' => $order['amount']];
            $relation_params = ['pay_amount' => $order['amount']];
        } else if ('back' == $type) { //归还
            $params = ['battery_amount' => $order['amount'], 'total_amount' => $order['amount']];
            $stat_device_params = ['amount' => $order['amount']];
            $relation_params = ['order_amount' => $order['amount']];
        }

        //平台统计
        $stat_text[] = statSql('stat_operator', ['date' => date("Y-m-d")], $params);
        //店铺统计
        $seller_params = $params;
        unset($seller_params['brokerage_amount'], $seller_params['brokerage_cancel'], $seller_params['brokerage_pay_cancel'], $seller_params['brokerage_settle']);
        $stat_text[] = statSql('stat_seller', ['date' => date("Y-m-d"), 'sid' => $order['sid']], $seller_params);
        //设备统计
        $stat_text[] = statSql('stat_device', ['date' => date("Y-m-d"), 'device_id' => $order['device_code']], $stat_device_params);

        //代理统计
        $agency_params = $params;
        unset($agency_params['brokerage_amount']);
        foreach ($brokerage as $adata) {
            ('back' != $type) && $agency_params['brokerage_amount'] = $adata['amount'];
            $stat_text[] = statSql('stat_agency', ['date' => date("Y-m-d"), 'agency_id' => $adata['relation_id']], $agency_params);
        }

        //代理关联统计
        foreach ($agency_ids as $aid) {
            $stat_text[] = statSql('stat_agency_seller', ['date' => date("Y-m-d"), 'sid' => $order['sid'], 'relation_id' => $aid], $relation_params); //商户
            $stat_text[] = statSql('stat_agency_device', ['date' => date("Y-m-d"), 'device_id' => $order['device_code'], 'relation_id' => $aid], $relation_params); //设备
        }
        statText($this->oCode, $stat_text);
    }

    function getRechargeAmount($user, $operate)
    {
        $balance = $this->db->name('user')->where(['id' => $user['id']])->value('balance');
        $balance = priceFormat($balance);
        $deposit = priceFormat(bcsub($operate['deposit'], $balance, 2));
        $deposit < 0 && $deposit = 0;
        return $deposit;
    }
}
