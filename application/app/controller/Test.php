<?php

namespace app\app\controller;

use think\Log;
use think\Request;
use app\common\service\Mpesa;
use app\common\logic\Lease;
use app\common\service\Command;
use app\common\logic\Payment;

/**
 * 测试
 * @package app\app\controller
 */
class Test extends Common
{
    function index()
    {
 exit;
        $account = '0758555738';
        $ret = (new Mpesa())->b2c('0758555738' . $account, 5, 'test');
        if(!preg_match("/^254*/",$account)) {
            echo 1;
        }


        exit;
    }

    private function _getData()
    {
        return '{
            "TransactionType": "Pay Bill",
            "TransID": "QER3OM0QN3",
            "TransTime": "20220527110948",
            "TransAmount": "10.00",
            "BusinessShortCode": "4086903",
            "BillRefNumber": "666666",
            "InvoiceNumber": "",
            "OrgAccountBalance": "54.00",
            "ThirdPartyTransID": "",
            "MSISDN": "2547 ***** 738",
            "FirstName": "LEI"
        }';
    }


    /**
     * 模拟支付验证
     */
    function validation()
    {
        $data = $this->_getData();
        $params = json_decode($data, true);

        $url = 'https://cdb.mapro.co.ke/app/callback/complatedValidation';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        echo $response;
        exit;
    }

    /**
     * 模拟付款成功
     */
    function pay()
    {
        $data = $this->_getData();
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

        return ['ResultCode' => 0, 'ResultDesc' => 'Accepted', 'no' => $params['trade_no']];
    }


    /**
     * 支付验证
     */
    public function complatedValidation()
    {
        $data = file_get_contents("php://input");
        save_log('mpesa_validation_test', $data);

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
            return ['ResultCode' => 'C2B00016', 'msg' => lang('设备维护中，请扫码其他设备')];
        }
        if ($operator['deposit'] != $params['TransAmount']) {
            return ['ResultCode' => 'C2B00013', 'ResultDesc' => 'Invalid Amount'];
        }

        //判断机柜是否在线
        $cache = $this->getEquipmentCache($device['cabinet_id']);
        if (!$cache || time() - $cache['heart_time'] > config('online_time')) {
            cache($user_lock, null);
            return ['ResultCode' => 'C2B00016', 'ResultDesc' => lang('机柜不在线')];
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
            return ['code' => 'C2B00016', 'msg' => lang('弹出失败，请重新扫码')];
        }
        cache($user_lock, null);
        $time = microtime(true) - THINK_START_TIME;
        return ['ResultCode' => 0, 'ResultDesc' => 'Accepted', 'data' => $ret, 'time' => $time];
    }


    /**
     * 查询回调
     */
    public function queryResult()
    {
        $data = file_get_contents("php://input");
        $data = json_decode($data, true);

        if (empty($data) || !is_array($data)) {
            return ['ResultCode' => '1', 'ResultDesc' => 'Invalid Account Number'];
        }
        save_log('debug', $data);
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
        $amount = 10;
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

        $re = (new Lease())->deviceLease($user, $recharge_log['bill_no'], $recharge_log['trade_no']);
        save_log('debug', $re);
        if ($re['code'] != 1) {//退款

        }
        return $result;
    }


    public function b2cResult()
    {
        $result = ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
        $data = '{
            "Result": {
                "ResultType": 0,
                "ResultCode": 0,
                "ResultDesc": "The service request is processed successfully.",
                "OriginatorConversationID": "11402-61459450-1",
                "ConversationID": "AG_20220610_20206a9c6a7fa4fe9f2c",
                "TransactionID": "QFA4H1O5FC",
                "ResultParameters": {
                    "ResultParameter": [{
                        "Key": "TransactionAmount",
                        "Value": 10
                    }, {
                        "Key": "TransactionReceipt",
                        "Value": "QFA4H1O5FC"
                    }, {
                        "Key": "ReceiverPartyPublicName",
                        "Value": "0758555738 - LEI   WANG"
                    }, {
                        "Key": "TransactionCompletedDateTime",
                        "Value": "10.06.2022 04:53:46"
                    }, {
                        "Key": "B2CUtilityAccountAvailableFunds",
                        "Value": 4974.73
                    }, {
                        "Key": "B2CWorkingAccountAvailableFunds",
                        "Value": 0.00
                    }, {
                        "Key": "B2CRecipientIsRegisteredCustomer",
                        "Value": "Y"
                    }, {
                        "Key": "B2CChargesPaidAccountAvailableFunds",
                        "Value": 0.00
                    }]
                },
                "ReferenceData": {
                    "ReferenceItem": {
                        "Key": "QueueTimeoutURL",
                        "Value": "http:\/\/internalapi.safaricom.co.ke\/mpesa\/b2cresults\/v1\/submit"
                    }
                }
            }
        }';
        save_log('b2c_result_test ', $data);
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
        return $result;
    }
}
