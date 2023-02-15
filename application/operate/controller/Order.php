<?php
//租还记录
namespace app\operate\controller;

class Order extends Common
{
    public function _initialize()
    {
        parent::_initialize();
        $this->logic = \think\Loader::model('Order', 'logic');
    }

    public function index()
    {
        $data = $this->activeList();
        $this->successResponse($data , lang('获取成功'));
    }

    //未完成订单
    public function activeList()
    {
        $page_size = input('page_size', 20, 'intval');
        $data = $this->logic->activeList(['type' => 1], $page_size, true);
        return $this->successResponse($data, lang('获取成功'));
    }


    //充电宝订单
    public function batteryList()
    {
        $page_size = input('page_size', 20, 'intval');
        $data = $this->logic->orderList(['type' => 1], $page_size, true);
        return $this->successResponse($data, lang('获取成功'));
    }

    //丢失充电宝
    public function loseList()
    {
        $page_size = input('page_size', 20, 'intval');
        $data = $this->logic->loseList([], $page_size, true);
        return $this->successResponse($data, lang('获取成功'));
    }

    //密码线订单
    public function wiredList()
    {
        $page_size = input('page_size', 20, 'intval');
        $data = $this->logic->orderList(['type' => 2], $page_size, true);
        return $this->successResponse($data, lang('获取成功'));
    }

    //结束订单弹出层
    public function endDialog()
    {
        $order_no = input('order_no');
        $order = $this->db->name("order_active")->where(['order_no' => $order_no, 'type' => 1])->find();
        !$order && $this->error(lang('订单不存在'));
        $order['status'] != 1 && $this->error(lang('订单不为进行中'));
        $order['end_time'] = time();
        $billing = \think\Loader::model('Lease', 'logic')->calOrderPrice($order);
        $order['amount'] = $billing['price'];


        $info = \think\Loader::model('Order', 'logic')->detail($order);
        $info['end_time'] = date("Y-m-d H:i:s");
        $info['returned'] = 1;
        if ($info['agency_end'] == 1) {
            $info['end_info'] = json_decode($order['end_info'], true);
            $info['end_info']['returned'] == 1 && $info['end_time'] = date("Y-m-d H:i:s", $info['end_info']['end_time']);
            $info['returned'] = $info['end_info']['returned'];
        }
        return $this->successResponse($info, lang('获取成功'));
    }

    public function endActive()
    {
        return $this->endOrder();
    }

    //结束订单
    public function endOrder()
    {
        $order_no = input('order_no');
        $order = $this->db->name("order_active")->where(['order_no' => $order_no])->find();
        !$order && $this->errorResponse(0, lang('订单不存在'));
        $order['status'] == 2 && $this->errorResponse(0, lang('订单已结束'));
        if (2 == $order['type']) {//密码线
            $this->errorResponse(0, lang('订单不能结束'));
        }


        $end_time = time();
        $returned = input('returned', '', 'trim');
        $amount = input('amount', '', 'trim');

        if ($returned == 1) {//已归还
            if (!preg_match("/^[0-9]+(.[0-9]{1,2})?$/", $amount) || $amount < 0) {
                $this->errorResponse(0, lang('请输入正确的金额'));
            }
            $is_lose = 0;
            $text = lang('归还');
        } else {
            $amount = priceFormat($order['deposit']);
            $is_lose = 1;
            $text = lang('丢失');
        }
        $result = \think\Loader::model('Lease', 'logic')->endLease($order['id'], '', $end_time, $amount, $is_lose);
        if ($result['code'] !== 1) {
            $this->errorResponse(0,  $result['msg']);
        }
        $remark = input('remark', '', 'trim');//手动结束
        $this->db->name('order_operate_log')->insert([
            'order_no' => $order['order_no'],
            'operate' => lang('结束订单'),
            'memo' => $remark,
            'user_id' => $this->auth_info['id'],
            'create_time' => time(),
        ]);
        $this->operateLog($this->auth_info['uid'], '结束订单');
        $this->successResponse([],  $result['msg']);
    }

    //结束订单

    public function endBattery()
    {
        return $this->endOrder();
    }


    //取消密码线订单

    public function cancelOrder()
    {
        $order_no = input('order_no', date("Ym"), 'trim');
        $ret =  \think\Loader::model('Order', 'logic')->cancelOrder($order_no);
        if($ret['code'] === 0){
            $this->operateLog($this->auth_info['uid'], '取消密码线订单');
            $this->errorResponse(0, lang('操作失败'));
        }
        $this->successResponse([], lang('操作成功'));
    }

    function brokerage()
    {
        $page_size = input('page_size', 20, 'intval');
        $data = \think\Loader::model('Order', 'logic')->brokerageList([], $page_size, true);
        return $this->successResponse($data, lang('获取成功'));
    }

    //订单详情
    public function detail()
    {
        $order_no = input('order_no', date("Ym"), 'trim');
        $month = substr($order_no, 0, 6);
        $order_table = getTableNo('lease_order', 'date', $month);
        $order = $this->db->name($order_table)
            ->where(['order_no' => $order_no])
            ->find();
        if (!$order) {
            $this->errorResponse(0, lang('信息不存在'));
        }
        $info = \think\Loader::model('Order', 'logic')->detail($order);
        if ($info['lose_process'] == 2) {//补钱给代理
            $brokerage_table = 'order_brokerage_' . date("Ym", $order['payment_time']);
            $agency_id = $this->db->name($brokerage_table)
                ->where(['order_no' => $order_no, 'status' => 9])
                ->value('relation_id');
            if ($agency_id) {
                $info['lose_agency'] = $this->db->name('agency')
                    ->where(['id' => $agency_id])
                    ->value('name');
            }
        }
        return $this->successResponse($info, lang('获取成功'));

    }

    public function modifyBattery()
    {
        return $this->modify();
    }

    public function modify()
    {
        $order_no = input('order_no', date("Ym"), 'trim');
        $amount = input('amount', 0, 'floatval');
        $month = substr($order_no, 0, 6);
        $order_table = getTableNo('lease_order', 'date', $month);
        $order = $this->db->name($order_table)
            ->where(['order_no' => $order_no])
            ->find();
        if (!$order) {
            $this->errorResponse(0, lang('数据不存在'));
        }
        if ($order['status'] != 2 || $order['is_pay'] > 1) {
            $this->errorResponse(0, lang('订单不能修改'));
        }
        if ($order['is_pay'] == 1 && (time() - $order['payment_time']) > 86400 * 3) {
            $this->errorResponse(0, lang('订单不能修改'));
        }
        $ret =  \think\Loader::model('Order', 'logic')->modifyOrder($order, $amount);
        if($ret['code'] === 0){
            $this->operateLog($this->auth_info['uid'], '修改订单');
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang('操作成功'));
    }

    public function modifyActive()
    {
        return $this->modify();
    }


    //修改订单金额

    public function modifyLose()
    {
        return $this->modify();
    }

    public function creditList()
    {
        $page_size = input('page_size', 20, 'intval');
        $data = $this->logic->creditList([], $page_size, true);
        return $this->successResponse($data, lang('获取成功'));
    }


    public function overdue()
    {
        $order_no = input('order_no', '', 'trim');
        $order = $this->db->name('order_active')->where(['order_no' => $order_no])->find();
        if (!$order) {
            $this->errorResponse(0, lang('信息不存在'));
        }

        $result = \think\Loader::model('Order', 'logic')->overdue($order);
        return $this->successResponse($result, lang($result['msg']));
    }

    //支付状态
    public function authPay()
    {
        $order_no = input('order_no', '');
        $order = $this->db->name('order_active')->where(['order_no' => $order_no])->find();
        $result = \think\Loader::model('Order', 'logic')->payStatus($order);
        if ($result['code'] != 1) {
            return $this->errorResponse(0, lang($result['msg']));
        }
        return $this->successResponse($result);
    }

    //丢失充电宝处理
    public function batteryLose()
    {
        $order_no = input('order_no', '', 'trim');
        $order = $this->db->name("order_lose")->where(['order_no' => $order_no])->find();


        $id = $order['id'];
        !$order && $this->errorResponse(0, lang('订单不存在'));
        (empty($order['is_lose']) || !empty($order['lose_process'])) && $this->error(lang('订单不能操作'));
        $info = \think\Loader::model('Order', 'logic')->detail($order);
        $price = 0;
        $process = input('process', 0, 'intval');
        $lease_table = getTableByDate('lease_order', $order_no);
        if ($process == '0') {
            $lose_process = 1;
        } else {
            $brokerage = array_column($info['brokerage'], NULL, 'id');
            if (!isset($brokerage[$process])) {
                $this->errorResponse(0, lang('代理商不存在'));
            }
            $log = $this->db->name($lease_table)->where(['order_no' => $order_no])->find();
            if (empty($log['is_pay'])) {
                $this->errorResponse(0, lang('订单尚未支付'));
            }

            $agency = $brokerage[$process];
            $lose_process = 2;
            $amount = priceFormat($info['amount']);//实付金额
            $brokerage_table = getTableByDate('order_brokerage', date("Ym", $order['payment_time']));
            $order_brokerage = $this->db->name($brokerage_table)
                ->where(['order_no' => $order_no, 'status' => ['IN', [1, 2]]])
                ->sum('amount');
            $order_brokerage = priceFormat($order_brokerage);
            $amount = bcsub($amount, $order_brokerage, 2);//实付金额减去分成金额
            $price = priceFormat($info['device_price']);//成本
            $price > $amount && $price = $amount;
        }

        $this->db->startTrans();
        try {
            $this->db->name($lease_table)->where(['order_no' => $order_no])->update(['lose_process' => $lose_process, 'update_time' => time()]);
            $this->db->name("order_lose")->where(['id' => $id])->update(['lose_process' => $lose_process, 'update_time' => time()]);
            if ($price > 0) {//分成
                $params = [
                    'order_id' => $order['id'],
                    'order_no' => $order['order_no'],
                    'user_type' => $agency['role_type'],
                    'relation_id' => $agency['id'],
                    'ratio' => 0,
                    'status' => 9,
                    'amount' => $price,
                    'create_time' => time(),
                    'settlement_time' => time()
                ];

                $this->db->name('order_brokerage_' . date("Ym"))->insert($params);
                $this->db->name('account')
                    ->where(['relation_id' => $agency['id']])
                    ->update([
                        'total_amount' => ['exp', 'total_amount+' . $price],
                        'balance' => ['exp', 'balance+' . $price]
                    ]);
                $date = date("Y-m-d");
                //平台统计
                $this->db->name('stat_operator')
                    ->where(['date' => $date])
                    ->update([
                        'brokerage_amount' => ['exp', 'brokerage_amount+' . $price],
                        'brokerage_settle' => ['exp', 'brokerage_settle+' . $price],
                    ]);
                $stat_text = [];
                $stat_text[] = statSql('stat_agency', ['date' => $date, 'agency_id' => $agency['id']], ['brokerage_amount' => $price, 'brokerage_settle' => $price]);//统计
                statText($this->oCode, $stat_text);
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            save_log('sql', $e->getMessage());
            return $this->errorResponse(0, lang('信息提交失败，请稍后重试'));
        }
        $this->operateLog($this->auth_info['uid'], '丢宝处理');
        return $this->successResponse([], lang('保存成功'));
    }

    //信用授权单号
    function authList()
    {
        $page_size = input('page_size', 20, 'intval');
        $data = $this->logic->authList([], $page_size, true);
        return $this->successResponse($data, lang('信用授权单号'));
    }

    //取消授权
    function cancelAuth()
    {
        $id = input('id', 0, 'intval');
        $uid = input('uid', 0, 'intval');
        $user_lock = "lease:{$this->oid}:{$uid}";
        $lock = cache($user_lock);
        if ($lock) {
            $this->errorResponse(0, lang('用户正在租借中，请稍后'));
        }
        cache($user_lock, 1, 15);
        $log = $this->db->name("pay_auth_log")->where(['id' => $id])->find();
        if (!$log || $log['status'] > 0) {
            cache($user_lock, null);
            $this->errorResponse(0, lang('授权不能取消'));
        }
        $result = \think\Loader::model('Payment', 'logic')->authCancel($log['pay_type'], $log);
        cache($user_lock, null);
        if (1 == $result['code']) {
            $this->operateLog($this->auth_info['uid'], '取消授权');
            return $this->successResponse([], lang('操作成功'));
        }
        $this->errorResponse(0, lang($result['msg']));
    }

}