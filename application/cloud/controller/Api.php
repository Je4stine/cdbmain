<?php

namespace app\cloud\controller;

use think\Controller;
use think\Db;
use think\Request;

//内部数据调用
class Api extends Controller
{
    public $db;//数据库

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
        if ($requestkey != $key || ($difference && $difference > 30) || ($difference && $difference < -30)) {
            $data = array(
                'mykey' => $key,
                'mytime' => time(),
                'requestkey' => $requestkey,
                'requesttime' => $time,
            );
            echo json_encode(['status' => 0, 'msg' => lang('非法请求')], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function authPay()
    {
        $order_id = input('id', 0);
        $code = Request::instance()->header('oCode');
        $this->_getDb($code);

        $order = $this->db->name('order_active')->where(['id' => $order_id])->find();
        $result = \think\Loader::model('Order', 'logic')->payStatus($order);
        //$result = $this->payStatus($order);
        echo json_encode(['status' => 1, 'msg' => $result['msg']], JSON_UNESCAPED_UNICODE);
        exit;
    }


    function payStatus($order)
    {
        if (!$order) {
            $this->error(lang('订单不存在'));
        }

        $key = "pay_status:{$this->oCode}:{$order['pay_auth_id']}";
        $lock = cache($key);
        if($lock){
            $this->error(lang('访问频繁，请稍后重试'));
        }
        cache($key,1,10);
        if (2 != $order['status']) {
            cache($key,null);
            return ['status' => 2, 'code' => 2, 'msg' => lang('请先归还充电宝'),'d'=>1];
        }
        if (empty($order['pay_auth_id'])) {
            cache($key,null);
            return ['status' => 1, 'code' => 1, 'msg' => lang('订单已支付'),'d'=>2];
        }
        $log = $this->db->name('pay_auth_log')->where(['id' => $order['pay_auth_id']])->find();
        if (!$log || $log['status'] > 3) {
            cache($key,null);
            return ['status' => 1, 'code' => 1, 'msg' => lang('订单已支付'), 'type' => 1,'d'=>3];
        }

        $client = ($log['pay_type'] == config('pay_type.alipay')) ? lang('芝麻信用分') : lang('微信信用分');
        if ($log['status'] < 3) {//尚未转支付
            if ($log['pay_type'] == config('pay_type.alipay')) {
                $recharge = $this->db->name('recharge_log')->where(['auth_log_id' => $order['pay_auth_id']])->order('id DESC')->find();
                if ($recharge && (time() - $recharge['create_time']) < 86400 * 14 && $recharge['pay_status'] == 0) {
                    $order['pay_order_no'] = $recharge['order_no'];
                    $order['pay_order_id'] = $recharge['id'];
                } else if ($recharge) {
                    //关闭订单
                    $result = \think\Loader::model('AlipayApi', 'service')->close($recharge['order_no']);
                    if ($result['code'] == 1) {
                        $this->db->name('recharge_log')->where(['id' => $recharge['id']])->update(['pay_status' => 99]);
                    }
                    $result['test'] = $order['order_no'];
                    save_log('0619',$result);
                }
            }
            $result = \think\Loader::model('Payment', 'logic')->finishRentBill($order);
            $result['test'] = $order['order_no'];
            save_log('0619',$result);
            if (1 != $result['code']) {//失败
                cache($key,null);
                return ['code' => 2, 'status' => 2, 'msg' => lang("支付失败，请确定") . $client . lang("扣款账户有足够金额")];
            }
            $result['code'] = 1;
            $result['status'] = 1;
            $result['d'] = 4;
            cache($key,null);
            return $result;
        }
        $status = 0;
        $info = [];

        if ($order['app_type'] == config('app_type.wechat')) {//微信
            $result = \think\Loader::model('WechatApi', 'service')->wxvQueryrentbill($log['order_no']);
            if (!empty($result['code']) && in_array($result['code'], ['USER_PAID', 'REVOKED', 'EXPIRED'])) {
                $status = 1;
                $info = [
                    'out_trade_no' => $result['data']['out_order_no'],
                    'total_amount' => intval($result['data']['rent_fee']) / 100,
                    'trade_no' => $result['data']['finish_transaction_id'],
                ];
                $pay_type = 'wechat';
            }
        } else if ($order['app_type'] == config('app_type.alipay')) {//支付宝
            $result = \think\Loader::model('AlipayApi', 'service')->authQuery($log['trade_no'], $log['operation_id']);
            if ($result['code'] == 1 && number_format($result['data']['total_pay_amount']) > 0.001) {
                $status = 1;
                $info = [
                    'out_trade_no' => $result['data']['out_order_no'],
                    'total_amount' => $result['data']['total_pay_amount'],
                    'trade_no' => $result['data']['auth_no'],
                ];
                isset($result['data']['gmt_trans']) && $info['time'] = strtotime($result['data']['gmt_trans']);
                $pay_type = 'alipay';
            }
        }
        $result['test'] = $order['order_no'];
        save_log('0619',$result);
        if ($status < 1) {
            cache($key,null);
            return ['status' => 2, 'code' => 2, 'msg' => lang("请确定") . $client . lang("扣款账户有足够金额或者请稍后查看订单状态")];
        }

//        $this->db->name('lease_order')->where(['id' => $order_id])->update(['is_pay' => 1, 'payment_time' => time(), 'payment_amount' => $info['total_amount']]);
//        $this->db->name('pay_auth_log')->where(['id' => $log['id']])->update(['status' => 4]);
        \think\Loader::model('Payment', 'logic')->freezeNotifyProcess($pay_type, $info);
        cache($key,null);
        return ['status' => 1, 'code' => 1, 'msg' => lang('订单已支付'),'d'=>5];
    }




    public function authCancel()
    {
        $code = Request::instance()->header('oCode');
        $oid = input('oid', 0);
        $this->_getDb($code);
        $query = $this->db->name('pay_auth_log')
            ->where([
                'pay_status' => 1,
                'status' => 0,
                'create_time'=>['<',time() - 300],
            ])->order(['id' => 'DESC'])->select();
        foreach($query as $log) {

            $user_lock = "lease:{$oid}:{$log['uid']}";
            $lock = cache($user_lock);
            if($lock){
                continue;
            }
            cache($user_lock,1,15);
            $result = \think\Loader::model('Payment', 'logic')->authCancel($log['pay_type'], $log);
            cache($user_lock,null);
            save_log('authCancel',$result);
        }
        echo json_encode(['status' => 1, 'msg' => '取消授权'], JSON_UNESCAPED_UNICODE);
        exit;
    }



    /**
     * 调取付款方法
     *
     * @return void
     */
    public function payOrder()
    {
        $order_id = input('id', 0);
        $this->_getDb();
        $table = getTableNo('lease_order', 'date', date("Ym"));
        $order_info = $this->db->name($table)->where(['id' => $order_id])->find();
        $user_info = $this->db->name('user')->field('id, customer_id, default_card_id')->where('id', $order_info['uid'])->find();
        if (!empty($user_info['customer_id'])) {
            $params = ['order_no' => $order_info['order_no'], 'uid' => $order_info['uid'], 'amount' => $order_info['amount']];
            save_log("pay_order", $user_info);
            $result = \think\Loader::model('Payment', 'logic')->generateTrade($params, $user_info);
            save_log("pay_order", '支付结果:'. json_encode($result));
            if ('1' == $result['code']) {
                //更新支付状态
                $msg = '订单号：' . $order_info['order_no'] . '成功';
            } else {
                $msg = '订单号：' . $order_info['order_no'] . '支付失败，' . $result['msg'];
            }
        }else{
            $msg = 'no customer_uid';
        }
        echo json_encode(['status' => 1, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function stat()
    {
        $code = Request::instance()->header('oCode');
        $this->_getDb($code);
        $start = strtotime(date("Y-m-d")) -86400;
        $end = $start+86399;
        $logic = \think\Loader::model('Stat', 'logic');
        $add_user = $logic->addUser($start, $end);
        $active_user = $logic->activeUser($start, $end);
        $params['add_user'] = $add_user['add_user'];
        $params['add_user_data'] = $add_user['add_user_data'];
        $params['active_user'] = $active_user['active_user'];
        $params['active_user_data'] = $active_user['active_user_data'];
        $params['add_seller'] = $logic->addSeller($start, $end);

        $this->db->name('stat_operator')
            ->where(['date' => date("Y-m-d", $start)])
            ->update($params);
        echo json_encode(['status' => 1, 'msg' => '统计'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function _getDb($code = '')
    {
        //链接数据库
        $db_config = config('database');
        $this->db = Db::connect($db_config);
    }




}

