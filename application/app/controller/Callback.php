<?php

namespace app\app\controller;

use app\common\logic\Lease;
use app\common\logic\Payment;
use app\common\service\Command;
use app\common\service\Mpesa;
use think\Log;
use think\Request;


/**
 * 回调
 * Class Callback
 * @package app\app\controller
 */
class Callback extends Common
{

    public function test()
    {
        //$ret = (new Mpesa())->query('QKB2PDASWY');exit;
        $ret = (new Mpesa())->b2c('254758555738', 10, 'test');
        //$ret = (new Mpesa())->register();
        echo '<pre>';
        print_r($ret);
        exit;
    }

    /**
     * 支付验证
     */
    public function complatedValidation()
    {
        $data = file_get_contents("php://input");
        save_log('mpesa_validation', $data);

        $params = json_decode($data, true);

        if (empty($params) || !is_array($params)) {
            return ['ResultCode' => 'C2B00012', 'ResultDesc' => 'Invalid Account Number'];
        }
        if (!preg_match("/^\d{6}$/", $params['BillRefNumber'])) {
            return ['ResultCode' => 'C2B00012', 'ResultDesc' => 'Invalid Account Number'];
        }
        $user_lock = 1;
        $obj = new Lease();
        $info = $obj->getDeviceInfo($params['BillRefNumber']);
        if (1 != $info['code']) {
            return $info['data'];
        }
        $device = $info['device'];
        $operator = $info['data'];
        if (!empty($device['is_fault'])) {
            cache($user_lock, null);
            return ['ResultCode' => 'C2B00016', 'ResultDesc' => 'This device is under maintenance.  Please try to scan anther one'];
        }
        if ($operator['deposit'] != $params['TransAmount']) {
            return ['ResultCode' => 'C2B00013', 'ResultDesc' => 'Invalid Amount'];
        }
        save_log('pay_validation', $operator);
        //判断机柜是否在线
        $cache = $this->getEquipmentCache($device['cabinet_id']);
        if (!$cache || time() - $cache['heart_time'] > config('online_time')) {
            cache($user_lock, null);
            return ['ResultCode' => 'C2B00016', 'ResultDesc' => 'Cabinet is not online'];
        }
        try {
            $battery_power = $this->getOperatorConfig('battery_power');
            $service_obj = new Command();
            $service = $service_obj->initData($device['cabinet_id']);

            $service->setLowPower($battery_power);
            //获取库存
            $ret = $service->preBorrow();
            if ($ret['status'] != 1) {
                cache($user_lock, null);
                save_log('validation_offline', $data);
                return ['ResultCode' => 'C2B00016', 'ResultDesc' => $ret['msg']];
            }
        } catch (\Exception $e) {
            Log::record('开始租赁接口报错：' . $e->getMessage(), 'error');
            cache($user_lock, null);
            return ['code' => 'C2B00016', 'msg' => 'The device failed to connect to the network. Please try again later'];
        }
        cache($user_lock, null);
        $time = microtime(true) - THINK_START_TIME;
        return ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
    }

    /**
     * 支付成功
     */
    public function complatedConfirmation()
    {
        $data = file_get_contents("php://input");
        save_log('mpesa_complated', $data);

        $params = json_decode($data, true);
        //记录充值日志
        $log = $this->db->name('recharge_log')->where(['trade_no' => $params['TransID']])->find();
        if ($log) {
            return ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
        }
        $params = [
            'trade_no' => $params['TransID'],
            'amount' => $params['TransAmount'],
            'payment_time' => strtotime($params['TransTime']),
            'bill_no' => $params['BillRefNumber'],
            'create_time' => time(),
        ];
        !$log && $this->db->name('recharge_log')->insert($params);
        $ret = (new Mpesa())->query($params['trade_no']);
        return ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
    }


    public function cancelledConfirmation()
    {
        $data = file_get_contents("php://input");
        save_log('cancelled_confirmation', $data);
        $result = ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
        return $result;
    }

    public function cancelledValidation()
    {
        $data = file_get_contents("php://input");
        save_log('cancelled_validation ', $data);
        $result = ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
        return $result;
    }


    public function queryQueue()
    {
        $data = file_get_contents("php://input");
        save_log('query_queue ', $data);
        $result = ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
        return $result;
    }

    public function queryResult()
    {
        $data = file_get_contents("php://input");
        save_log('query_result ', $data);

        $data = json_decode($data, true);
        if (empty($data) || !is_array($data)) {
            return ['ResultCode' => 'C2B00013', 'ResultDesc' => 'Invalid Account Number'];
        }

        $result = ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
        $params = [];
        foreach ($data['Result']['ResultParameters']['ResultParameter'] as $val) {
            $params[$val['Key']] = $val['Value'];
        }
        $status = ($params['TransactionStatus'] == 'Completed') ? 1 : 2;

        //更新充值记录
        $recharge_log = $this->db->name('recharge_log')->where(['trade_no' => $params['ReceiptNo']])->find();
        if (!$recharge_log || !empty($recharge_log['status'])) {
            return $result;
        }

        $this->db->name('recharge_log')->where(['id' => $recharge_log['id']])->update(['status' => $status]);

        //注册用户
        $tmp = explode('-', $params['DebitPartyName']);
        $phone = trim($tmp[0]);
        $name = trim($tmp[1]);
        $amount = $params['Amount'];
        $status != 1 && $amount = 0;//未成功付款

        $user = $this->db->name('user')->where(['openid' => $phone, 'not_delete' => 1])->find();
        if (!$user) {
            $user = ['app_type' => 1];
            $user['openid'] = $phone;
            $user['nick'] = $name;
            $user['nickCode'] = base64_encode($name);
            $user['balance'] = $amount;
            $user['create_time'] = time();
            $user['last_login'] = time();
            $user['mobile'] = $phone;
            $uid = $this->db->name('user')->insertGetId($user);
            $member_id = 10181111 + $uid;//会员卡号
            $this->db->name('user')->where(['id' => $uid])->update(['member_id' => $member_id]);
            $user['id'] = $uid;
            $user['member_id'] = $member_id;
        } else {
            $this->db->name('user')->where(['id' => $user['id']])->update(['last_login' => time(), 'balance' => ['exp', 'balance+' . $amount]]);
        }
        if ($status != 1) {
            return $result;
        }

        //用户充值记录
        $order_no = date('YmdHis', strtotime($params['InitiatedTime'])) . mt_rand(1000, 9999);
        $payment_time = strtotime($params['FinalisedTime']);
        $log_params = [
            'order_no' => $order_no,
            'trade_no' => $params['ReceiptNo'],
            'uid' => $user['id'],
            'amount' => $amount,
            'balance' => $amount,
            'payment_time' => $payment_time,
        ];
        $user_table = getTableNo('recharge_user', 'hash', 4, $user['id']);
        $this->db->name($user_table)->insert($log_params);

        //资金流水记录
        $log_params = [
            'order_no' => $order_no,
            'trade_no' => $params['ReceiptNo'],
            'uid' => $user['id'],
            'amount' => $amount,
            'type' => 1,
            'create_time' => $payment_time,
        ];
        $this->db->name("trade_log_" . date("Y", $payment_time))->insert($log_params);

        // if ($payment_time - time() > 60) {//退款
        //      \think\Loader::model('Payment', 'logic')->refund($user['id'], $amount, 'Renting timeout,Deposit refund');
        //     return $result;
        // }

        $re = (new Lease())->deviceLease($user, $recharge_log['bill_no'], $recharge_log['trade_no']);
        save_log('debug', $re);
        if ($re['code'] != 1 && $amount > 0) {//退款
            \think\Loader::model('Payment', 'logic')->refund($user['id'], $amount, 'Renting failed,Deposit refund');
        }
        return $result;
    }

//    public function reversalQueue()
//    {
//        $data = file_get_contents("php://input");
//        save_log('reversal_queue ', $data);
//        $result = ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
//        return $result;
//    }
//
//    public function reversalResult()
//    {
//        $data = file_get_contents("php://input");
//        save_log('reversal_result ', $data);
//        $result = ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
//        return $result;
//    }


    public function b2cQueue()
    {
        $data = file_get_contents("php://input");
        save_log('b2c_queue ', $data);
        $result = ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
        return $result;
    }

    public function b2cResult()
    {
        $data = file_get_contents("php://input");
        save_log('b2c_result ', $data);
        $data = json_decode($data, true);
        $params = [
            'OriginatorConversationID' => $data['Result']['OriginatorConversationID'],
            'ConversationID' => $data['Result']['ConversationID'],
            'TransactionID' => $data['Result']['TransactionID'],
        ];
        foreach ($data['Result']['ResultParameters']['ResultParameter'] as $val) {
            $params[$val['Key']] = $val['Value'];
        }
        (new Payment())->refundSuccessful($params);
        return ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
    }
}
