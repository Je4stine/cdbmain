<?php

namespace app\common\logic;

use app\common\service\MercadoPay;
use app\common\service\Mpesa;
use think\Exception;

/**
 * 支付相关
 * @package app\common\service
 */
class Payment extends Common
{

    public function _initialize()
    {
        parent::_initialize();
    }


    function generateTrade($pay_type = 'mercadopago', $params = [], $uid = 0)
    {
        $result = ['code' => 0, 'msg' => lang('支付渠道不存在')];

        switch ($pay_type) {
            case 'mercadopago':
                $service = new MercadoPay();
                $result = $service->generateTrade($params);
                break;
            case 'mercadopago_freeze':
                $service = new MercadoPay();
                $result = $service->generateTradeFreeze($params);
                break;
        }
        $this->db->name('card')->where(['customer_id' => $params['customerId'], 'uid' => $uid])->update(['card_token' => 0]);

        if (1 == $result['code']) {
            if ($pay_type == 'mercadopago_freeze') {
                return $result;
            }
            $order_no = date("YmdHis") . mt_rand(100000, 999999);
            $params = array(
                'order_no' => $order_no,
                'uid' => $uid,
                'amount' => $params['amount'],
                'trade_no' => $result['data']['pay_id'],
                'balance' => 0,
                'pay_type' => config('pay_type.' . $pay_type),
                'create_time' => time(),
            );
            $this->db->name('recharge_log')->insertGetId($params);
        }
        return $result;
    }

    /**
     * @param $pay_type
     * @param $params
     * TODO 添加银行卡
     */
    public function addCard($pay_type, $params, $uid)
    {
        $result = ['code' => 0, 'msg' => lang('支付渠道不存在')];
        switch ($pay_type) {
            case 'app':
                $service = new MercadoPay();
                $result = $service->addCard($params);
                break;
        }

        if (1 == $result['code']) {
            $result['data']['uid'] = $uid;
            $result['data']['update_time'] = time();
            $result['data']['create_time'] = time();
            $idCard = $this->db->name('card')->insertGetId($result['data']);

            $this->db->name('user')->where(['id' => $uid])->update(['update_time' => time(), 'default_card_id' => $idCard]);
        }

        return $result;
    }

    /**
     * TODO 删除银行卡
     * @param $pay_type
     * @param $params
     * @param null $user_info
     * @return array
     */
    public function deleteCard($pay_type, $params)
    {
        $result = ['code' => 0, 'msg' => lang('支付渠道不存在')];
        switch ($pay_type) {
            case 'app':
                $service = new MercadoPay();
                $result = $service->deleteCard(['card_id' => $params['card_id'], 'customer_id' => $params['customer_id']]);
                break;
        }

        if ($result['code'] == 1) {
            try {
                $this->db->name('card')->where($params)->delete();//删卡
                $this->db->name('user')->where(['id' => $params['uid']])->update(['customer_id' => null, 'default_card_id' => 0]);
                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollback();
                save_log('sql', '删卡失败:' . $e->getMessage());
                return ['code' => 0, 'msg' => '充值失败'];
            }

            return ['code' => 1, 'msg' => '删除成功'];
        }

        return ['code' => 0, 'msg' => '删除失败'];
    }


    /**
     * TODO  获取银行卡列表
     * @param $pay_type
     * @param $params
     * @param null $user_info
     * @return array
     */
    public function getCardList($pay_type, $user_info = null)
    {

        if (empty($user_info['default_card_id'])) {
            return 0;
        }

        $customer_id = $this->db->name('card')->where(['id' => $user_info['default_card_id']])->value('customer_id');
        if (empty($customer_id)) {
            return [];
        }
        $card = new MercadoPay();
        $cardInfo = $card->cardInfo($customer_id);

        return $cardInfo['data'];

    }


    //支付回调
    function payNotify($result = [])
    {

        $notify = new MercadoPay();
        $res = $notify->payNotify($result['data']['id']);
        if (!$res['code']) {
            save_log('mercadopay', '支付失败:' . $result['data']['id']);
            return false;
        }
        save_log('mercadopay', '支付成功:' . $result['data']['id']);
        $info = $result['data'];
        $payId = $info['id'];
        $type = config('pay_type.' . "mercadopago");
        //加锁3秒，防止并发
        $lock = cache("paynotify:{$type}:{$payId}");
        if ($lock) {
            sleep(3);
        }
        cache("paynotify:{$type}:{$payId}", 1, 3);

        $order = $this->db->name('recharge_log')
            ->where(['trade_no' => $payId, 'pay_status' => 0, 'pay_type' => $type])
            ->find();
        if (!$order) {
            return false;
        }
        $payment_time = time();
        //更新订单状态
        $balance = $order['amount'];//该笔充值余额
        if (!empty($order['auth_log_id'])) {
            $lease_order_no = $this->db->name('pay_auth_log')
                ->filed('lease_order_no')
                ->where(['id' => $order['auth_log_id']])
                ->value('lease_order_no');
            $balance = 0;

        }
        $this->db->name('recharge_log')
            ->where(['id' => $order['id']])
            ->update([
                'payment_time' => $payment_time,
                'balance' => $balance,
                'pay_status' => 1,
                'update_time' => time(),
            ]);

        //更新用户余额
        $this->db->name('user')->where('id', $order['uid'])->setInc('balance', $order['amount']);
        $this->db->name('card')->where(['uid' => $order['uid']])->update(['card_token' => 0]);

        //用户充值记录
        $params = [
            'order_no' => $order['order_no'],
            'trade_no' => $info['id'],
            'uid' => $order['uid'],
            'amount' => $order['amount'],
            'balance' => $balance,
            'pay_type' => $order['pay_type'],
            'payment_time' => $payment_time,
            'is_credit' => empty($order['auth_log_id']) ? 0 : 1
        ];
        $user_table = getTableNo('recharge_user', 'hash', 4, $order['uid']);

        $this->db->name($user_table)->insert($params);

        //资金流水记录
        $params = [
            'order_no' => $order['order_no'],
            'trade_no' => $info['id'],
            'uid' => $order['uid'],
            'amount' => $order['amount'],
            'pay_type' => $order['pay_type'],
            'type' => 1,
            'create_time' => $payment_time,
        ];
        $this->db->name("trade_log_" . date("Y", $payment_time))->insert($params);
        //用户资金日志
        $params = [
            'uid' => $order['uid'],
            'type' => 1,
            'app_type' => $type,
            'amount' => $order['amount'],
            'order_no' => $order['order_no'],
            'create_time' => time(),
        ];
        $this->userAccountLog($params);
        return true;
    }


    //用户资金日志
    function userAccountLog($params = [])
    {
        $params['create_time'] = time();
        $table = "user_account_log_" . date("Y", $params['create_time']);
        $this->db->name($table)->insert($params);
    }

    //支付宝授权资金冻结回调
    function freezeNotify($pay_type, $info = [])
    {
        if ('alipay' == $pay_type) {
            $result = \think\Loader::model('AlipayApi', 'service')->authNotify();
            if (1 != $result['code']) {
                return $result;
            }
            $info = $result['data'];
            $info['finish_ticket'] = NULL;
        } else if ('wechat' == $pay_type) {
            $info['operation_id'] = $info['order_id'];
            $info['finish_ticket'] = $info['finish_ticket'];
            $info['auth_no'] = $info['order_id'];
            $info['pre_auth_type'] = NULL;
            $info['amount'] = priceFormat($info['deposit_amount'] / 100);
        }
        $order_no = $info['out_order_no'];
        $type = config('pay_type.' . $pay_type);
        //加锁3秒，防止并发
        $lock = cache("paynotify:{$type}:{$order_no}");
        if ($lock) {
            sleep(3);
        }
        cache("paynotify:{$type}:{$order_no}", 1, 3);

        $order = $this->db->name('pay_auth_log')
            ->where(['order_no' => $order_no, 'pay_status' => 0])
            ->find();

        if (!$order) {
            return ['code' => 0, 'msg' => lang('授权订单不存在')];
        }

        //更新授权订单状态
        $update_params = ['trade_no' => $info['auth_no'],
            'operation_id' => $info['operation_id'],
            'payment_time' => time(),
            'pay_status' => 1,
            'auth_type' => $info['pre_auth_type'],
            'finish_ticket' => $info['finish_ticket'],
            'update_time' => time(),
        ];
        $this->db->name('pay_auth_log')
            ->where(['id' => $order['id']])
            ->update($update_params);
        $this->db->name('pay_auth_log_' . substr($order['order_no'], 0, 4))
            ->where(['order_no' => $order['order_no']])
            ->update($update_params);

        //资金日志
        $params = [
            'uid' => $order['uid'],
            'type' => 4,//授权冻结
            'app_type' => $type,
            'amount' => $info['amount'],
            'order_no' => $order_no,
            'create_time' => time(),
        ];
        $this->userAccountLog($params);
        return ['code' => 1, 'msg' => lang('用户授权订单')];
    }


    //转支付回调
    function freezeToPayNotify($pay_type)
    {
        if ('wechat' == $pay_type) {
            $result = \think\Loader::model('WechatApi', 'service')->notifyWxv();
        } else {
            $result = \think\Loader::model('AlipayApi', 'service')->authNotify();
        }

        if (1 != $result['code']) {
            save_log('debug', $this->oCode);
            save_log('debug', $result);
            return false;
        }

        $info = $result['data'];
        return $this->freezeNotifyProcess($pay_type, $info);
    }


    /**
     * @param $pay
     * @param $info
     * TODO 支付完成处理
     */
    function freezeNotifyProcess($pay, $info)
    {
        $payment_time = isset($info['time']) ? $info['time'] : time();
        $info['total_amount'] = $pay['amount'];
        $params = [
            'payment_time' => $payment_time,
            'pay_status' => 1,
            'balance' => $info['total_amount'],
            'update_time' => time(),
            'trade_no' => $info['trade_no']
        ];

        $this->db->name('recharge_log')
            ->where(['id' => $pay['id']])
            ->update($params);


        //用户充值记录
        $params = [
            'order_no' => $pay['order_no'],
            'trade_no' => $info['trade_no'],
            'uid' => $pay['uid'],
            'amount' => $info['total_amount'],
            'balance' => $info['total_amount'],
            'pay_type' => $pay['pay_type'],
            'payment_time' => $payment_time,
            'is_credit' => empty($pay['auth_log_id']) ? 0 : 1
        ];
        $user_table = getTableNo('recharge_user', 'hash', 4, $pay['uid']);
        $this->db->name($user_table)->insert($params);

        //资金流水记录
        $params = [
            'order_no' => $pay['order_no'],
            'trade_no' => $info['trade_no'],
            'uid' => $pay['uid'],
            'amount' => $info['total_amount'],
            'pay_type' => $pay['pay_type'],
            'type' => 1,
            'create_time' => $payment_time,
        ];
        $this->db->name("trade_log_" . date("Y", $payment_time))->insert($params);

        $order = $this->db->name('order_active')->where(['order_no' => $pay['lease_order_no']])->find();

        if ($order['is_lose'] > 0) {//丢失了扣除充电宝成本
            $config = $this->getOperatorConfig('charge_info');
            $config = json_decode($config, true);
            $config['device_price'] = priceFormat($config['device_price']);
//            $order['brokerage_amount'] = bcsub($order['deposit'], $config['device_price'], 2);
            $order['brokerage_amount'] < 0.01 && $order['brokerage_amount'] = 0;
        }
        //代理分成
        $brokerage = \think\Loader::model('Lease', 'logic')->calBrokerage($order);
        $agency_ids = $brokerage['agency_ids'];
        $brokerage_amount = priceFormat($brokerage['amount']);
        $brokerage = $brokerage['brokerage'];
        $order['brokerage_total'] = $brokerage_amount;

        $order_no = $order['order_no'];
        $params = ['is_pay' => 1, 'payment_time' => time(), 'payment_amount' => $info['total_amount'], 'brokerage_total' => $brokerage_amount, 'update_time' => time()];
        $this->db->name('order_active')->where(['order_no' => $order_no])->update($params);
        if ($order['is_lose'] > 0) {
            $this->db->name('order_lose')->where(['order_no' => $order_no])->update($params);
        }
        //用户订单
        $user_table = getTableNo('order_user', 'hash', 16, $order['uid']);
        $this->db->name($user_table)->where(['order_no' => $order_no])->update(['is_pay' => 1]);
        //月表
        $month = substr($order_no, 0, 6);
        $order_table = getTableNo('lease_order', 'date', $month);
        $this->db->name($order_table)->where(['order_no' => $order_no])->update($params);
        //代理商表
        $agency_table = getTableNo('order_agency', 'date', $month);
        $this->db->name($agency_table)->where(['order_no' => $order_no])->update(['is_pay' => 1]);

        if ($brokerage) {
            $brokerage_log = $this->db->name('order_brokerage')->where(['order_no' => $order_no])->find();
            if (!$brokerage_log) {
                foreach ($brokerage as $k => $v) {
                    $brokerage[$k]['create_time'] = $payment_time;
                }
                $this->db->name('order_brokerage')->insertAll($brokerage);
                $this->db->name('order_brokerage_' . date("Ym", $payment_time))->insertAll($brokerage);
                save_log('order_brokerage_debug', "{$order_no} : {$payment_time} - " . date("Ym", $payment_time));
            }
        }
        \think\Loader::model('Lease', 'logic')->deviceStat($order, $agency_ids, $brokerage, 'pay');
    }

    //完结租借订单，转支付
    function finishRentBill($order)
    {
        $info = $this->db->name('pay_auth_log')
            ->where(['uid' => $order['uid'], 'id' => $order['pay_auth_id']])
            ->find();
        $pay_auth_table = 'pay_auth_log_' . substr($info['order_no'], 0, 4);

        $order_no = ($info['pay_type'] == config('pay_type.alipay')) ? date('YmdHis') . rand(1000, 9999) : $info['order_no'];
        if (isset($order['pay_order_no'])) {//支付宝15天内
            $order_no = $order['pay_order_no'];
            $pay_id = $order['pay_order_id'];
        } else {
            $params = array(
                'order_no' => $order_no,
                'uid' => $info['uid'],
                'amount' => $order['amount'],
                'balance' => 0,
                'pay_type' => $info['pay_type'],
                'auth_log_id' => $order['pay_auth_id'],
                'create_time' => time(),
            );
            $order['amount'] > 0 && $pay_id = $this->db->name('recharge_log')->insertGetId($params);
        }

        //支付宝
        if (($info['pay_type'] == config('pay_type.alipay'))) {
            $logic = \think\Loader::model('AlipayApi', 'service');
            if ($order['amount'] > 0) {//转支付
                if (!isset($order['openid']) || empty($order['openid'])) {
                    $order['openid'] = $this->db->name('user')->where(['id' => $order['uid']])->value('openid');
                }
                $result = $logic->authPay($order_no, $info['trade_no'], $order['amount'], $order['openid']);
                //save_log('debug', $result);
                if ($result['data']['trade_no']) {//转支付订单号
                    $this->db->name('recharge_log')
                        ->where(['id' => $pay_id])
                        ->update(['trade_no' => $result['data']['trade_no'], 'update_time' => time()]);
                }
                $status = (1 == $result['code']) ? 3 : 2;
                $this->db->name('pay_auth_log')
                    ->where(['id' => $order['pay_auth_id']])
                    ->update(['status' => $status, 'update_time' => time()]);
                $this->db->name($pay_auth_table)
                    ->where(['order_no' => $info['order_no']])
                    ->update(['status' => $status, 'update_time' => time()]);
                return $result;
            }
            //解冻
            $result = $logic->unfreeze($info['trade_no'], $info['order_no'], $order['deposit']);
            $status = (1 == $result['code']) ? 4 : 2;
            $this->db->name('pay_auth_log')
                ->where(['id' => $order['pay_auth_id']])
                ->update(['status' => $status, 'update_time' => time()]);
            $this->db->name($pay_auth_table)
                ->where(['order_no' => $info['order_no']])
                ->update(['status' => $status, 'update_time' => time()]);
            return ['code' => 1, 'msg' => lang("支付成功"), "data" => $result];
        }

        //微信
        $attributes = [
            'out_order_no' => $info['order_no'],
            'returned' => 'TRUE',
            'real_end_time' => date('YmdHis', $order['end_time']),
            'start_time' => $order['start_time'],
            'total_amount' => intval(bcmul($order['amount'], 100, 0)),
            'rent_fee' => intval(bcmul($order['amount'], 100, 0)),
            'finish_ticket' => $info['finish_ticket']
        ];
        if (!empty($order['return_sid'])) {
            $seller = $this->db->name('seller')->where(['id' => $order['return_sid'], 'not_delete' => 1])->value('name');
            $seller && $attributes['service_end_location'] = mb_substr($seller, 0, 18, 'utf8');
        }

        $result = \think\Loader::model('WechatApi', 'service')->wxvFinishrentbill($attributes);

        save_log('wxtest0716', $result);
        if ('INVALID_ORDER_STATE' == $result['err_code']) {//不能转支付，则获取订单状态
            $query = \think\Loader::model('WechatApi', 'service')->wxvQueryrentbill($info['order_no']);
            if ('USER_PAID' == $query['code']) {//用户支付
                $result['code'] = 1;
                $result['msg'] = '支付成功';
            }
        }
        save_log('wxtest0716', 'end');

        $status = 2;//解冻失败
        if (1 == $result['code']) {
            $status = ($attributes['total_amount'] < 1) ? 4 : 3;
        }
        $this->db->name('pay_auth_log')
            ->where(['id' => $order['pay_auth_id']])
            ->update(['status' => $status, 'update_time' => time()]);

        $this->db->name($pay_auth_table)
            ->where(['order_no' => $info['order_no']])
            ->update(['status' => $status, 'update_time' => time()]);
        return $result;
    }


    function refund($uid, $amount, $remark = '', $occassion = '')
    {
        $user = $this->db->name('user')->where(['id' => $uid])->find();
        $balance = floatval($user['balance']);
        if ($amount > $balance) {
            return ['code' => 0, 'msg' => lang("退款金额不能超过客户实际订单支付的总金额") . $balance . lang("元")];
        }

        //生成退款订单
        $refund_no = date('YmdHis') . rand(1000, 9999);
        $params = [
            'uid' => $uid,
            'amount' => $amount,
            'status' => 0,
            'create_time' => time()
        ];
        $refund_id = $this->db->name('refund_log')->insertGetId($params);
        $this->db->name('user')->where(['id' => $uid])
            ->update([
                'balance' => ['exp', 'balance-' . $amount],
                'freeze_amount' => ['exp', 'freeze_amount+' . $amount],
            ]);
        $account = $user['openid'];

        if (!preg_match("/^254*/", $account)) {
            $account = '254' . intval($account);
        }
        $ret = (new Mpesa())->b2c($account, $amount, $remark);
        $res = json_decode($ret, true);
        if (!is_array($res) || !isset($res['OriginatorConversationID'])) {
            $this->db->name('refund_log')->where(['id' => $refund_id])->update(['status' => 2]);
            return ['code' => 0, 'msg' => $res['errorMessage']];
        }
        $this->db->name('refund_log')->where(['id' => $refund_id])->update(['order_no' => $res['OriginatorConversationID']]);
        return ['code' => 1, 'refund' => $amount];
    }

    /**
     * 退款成功
     */
    function refundSuccessful($data)
    {
        $log = $this->db->name('refund_log')
            ->where(['order_no' => $data['OriginatorConversationID']])
            ->find();
        if (!$log) {
            return;
        }
        $this->db->name('refund_log')
            ->where(['id' => $log['id']])
            ->update([
                'refund_time' => time(),
                'status' => 1,
                'transaction_id' => $data['TransactionID'],
                'refund_no' => $data['ConversationID'],
                'balance' => $data['B2CUtilityAccountAvailableFunds'],
            ]);
        //账户余额
        $this->db->name('user')->where('id', $log['uid'])->setDec('freeze_amount', $log['amount']);

        //资金流水记录
        $trade_params = [
            'order_no' => $data['ConversationID'],
            'trade_no' => $data['TransactionID'],
            'uid' => $log['uid'],
            'amount' => $log['amount'],
            'type' => 2,
            'create_time' => time(),
        ];
        $this->db->name("trade_log_" . date("Y"))->insert($trade_params);
    }


    //取消授权
    function authCancel($type, $info)
    {
        if ($type == config('app_type.alipay')) {
            $result = \think\Loader::model('AlipayApi', 'service')->unfreeze($info['trade_no'], $info['trade_no'], $info['amount']);
        } else {
            $result = \think\Loader::model('WechatApi', 'service')->wxvCancelbill($info['order_no'], '租借失败');
        }
        if (1 == $result['code']) {
            $this->db->name('pay_auth_log')->where(['id' => $info['id']])->update(['update_time' => time(), 'status' => 99]);
            $this->db->name('pay_auth_log_' . substr($info['order_no'], 0, 4))->where(['order_no' => $info['order_no']])->update(['update_time' => time(), 'status' => 99]);
        }
        return $result;
    }

}
