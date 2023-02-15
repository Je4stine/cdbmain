<?php

namespace app\agency\controller;

//订单管理
class Order extends Common
{
    public function index()
    {
        return $this->orderList();
    }

    public function orderList()
    {
        $page_size = input('page_size', 10, 'intval');
        $page_size < 1 && $page_size = 10;
        $data = \think\Loader::model('Order', 'logic')->agencyList($page_size, $this->auth_info);
        return $this->successResponse($data, '订单列表');
    }

    public function detail()
    {
        $this->checkForbid();
        $order_no = input('order_no', '', 'trim');
        //$order_id = input('order_id', 2);
        $table = getTableByDate('order_agency', $order_no);
        if (!$this->tableExist($table)) {
            return $this->errorResponse(0, lang('订单不存在'));
        }
        //判断订单用户是否有权限查看
        $order = $this->db->name($table)
            ->where(['relation_id' => $this->auth_info['uid'], 'order_no' => $order_no])
            ->find();
        if (!$order) {
            return $this->errorResponse(0, lang('订单不存在'));
        }
        //订单详情
        $table = getTableByDate('lease_order', $order_no);
        $order = $this->db->name($table)
            ->where(['order_no' => $order_no])
            ->find();


        $order['app_type'] = config('app_name.' . $order['app_type']);
        $nick = $this->db->name('user')->where(['id' => $order['uid']])->value('nickCode');
        $logic = \think\Loader::model('Lease', 'logic');
        $billing = json_decode($order['billing_data'], true);
        $status_text = [1 => lang('租借中'), lang('已完成'), lang('已撤销'), lang('已超时')];
        if (1 == $order['status']) {
            if (1 == $order['type'] && time() - $order['expire_time'] > 0) {
                $order['status'] = 4;
            } else if (2 == $order['type'] && empty($order['end_time']) && $order['expire_time'] < time()) {
                $order['status'] = 2;
                $this->db->name('lease_order')->where(['id' => $order['id']])->update(['end_time' => $order['expire_time'], 'update_time' => time()]);
            }
        }

        $info = [
            'order_no' => $order['order_no'],
            'user_type' => $order['app_type'],
            'nick' => $this->getCustomerNick($nick),
            'order_type' => $order['type'],
            'device_id' => $order['device_code'],
            'seller' => $this->db->name('seller')->where(['id' => $order['sid']])->value('name'),
            'start_time' => $logic->formatTime($order['start_time']),
            'use_time' => $logic->calLeaseTime($order['start_time'], $order['end_time']),
            'amount' => priceFormat($order['amount']),
            'free_time' => $order['free_time'],
            'billing' => $logic->showBilling($order['billing_data']),
            'ceiling' => $billing['ceiling'],
            'deposit' => $billing['deposit'],
            'return_seller' => '',
            'return_time' => empty($order['end_time']) ? '' : $logic->formatTime($order['end_time']),
            'status' => $order['status'],
            'status_text' => $status_text[$order['status']],
            'ratio' => 0,
            'brokerage' => [],
            'credit' => null,
        ];
        if ($order['pay_auth_id'] > 0) {
            $info['credit'] = ($order['app_type'] == '微信小程序') ? '微信信用' : '芝麻信用';
        }
        if ($order['status'] == 2) {
            empty($order['is_pay']) && $info['status_text'] = '待支付';
        }
        $order['is_pay'] == 2 && $info['status_text'] = '已违约';
        $order['is_pay'] < 1 && $info['status_text'] = '';

        if (!empty($order['return_sid'])) {
            $info['return_seller'] = $this->db->name('seller')->where(['id' => $order['return_sid']])->value('name');
        }
        $brokerage = json_decode($order['brokerage_data'], true);
        foreach ($brokerage as $v) {
            if ($v['id'] == $this->auth_info['uid']) {
                $info['ratio'] = $v['ratio'];
            }
        }

        if ('agency' == $this->auth_info['role'] && !empty($brokerage)) {//代理商显示下级各级代理
            $agency_ids = array_column($brokerage, 'id');
            $brokerage = array_column($brokerage, NULL, 'id');

            $agency = $this->db->name('agency')->field('id,name,parent_id')->where(['id' => ['IN', $agency_ids]])->select();
            $agency = array_column($agency, NULL, 'id');
            $subs = \think\Loader::model('Agency')->getSubs($agency, $this->auth_info['uid']);
            $role = config('user_type_name');
            foreach ($subs as $k => $v) {
                $v['type'] = $role[$brokerage[$v['id']]['type']];
                $v['ratio'] = $brokerage[$v['id']]['ratio'];
                $info['brokerage'][] = $v;
            }
        }
        return $this->successResponse($info, '订单详情');
    }

    public function num()
    {
        $start_time = input('start_time', '', 'trim');
        $end_time = input('end_time', '', 'trim');
        $patten = "/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/";
        !preg_match($patten, $start_time) && $start_time = date("Y-m-01");
        !preg_match($patten, $end_time) && $start_time = date("Y-m-d");
        $params = ['start_time' => $start_time, 'end_time' => $end_time];

        $where = $this->_getCondition($params);
        $where1 = $this->_getConditionStatus($where, 'active');//进行中
        $where2 = $this->_getConditionStatus($where, 'end');//已完成
        $where3 = $this->_getConditionStatus($where, 'not_pay');//待支付
        $where4 = $this->_getConditionStatus($where, 'over');//超时


        $data = [
            'active' => $this->db->name('order_active')->alias('a')->where($where1)->count(),
            'not_pay' => $this->db->name('order_active')->alias('a')->where($where3)->count(),
            'over' => $this->db->name('order_active')->alias('a')->where($where4)->count(),
        ];
        try {
            $start_table = \think\Loader::model('Order', 'logic')->checkOrderMonth($start_time, 'lease_order');
            $end_table = \think\Loader::model('Order', 'logic')->checkOrderMonth($end_time, 'lease_order');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
        $sql = $this->db->name($end_table)
            ->alias('a')
            ->where($where2);
        if ($start_table != $end_table) {//跨月查询
            $union_sql = $this->db->name($start_table)
                ->alias('a')
                ->where($where2)
                ->buildSql();
            $sql = $sql->union($union_sql, true);
        }
        $data['end'] = $this->db->table($sql . ' a')->count();

        return $this->successResponse($data, '订单数量');
    }

    //申请结束订单
    public function endOrder()
    {
        if ('agency' != $this->auth_info['role']) {
            return $this->errorResponse(0, lang('没有操作权限'));
        }
        $order_no = input('order_no');
        $end_time = time();
        $amount = input('amount', '', 'trim');
        $returned = input('returned', '', 'trim');
        $remark = input('remark', '', 'trim');

        $order = $this->db->name("order_active")->where(['order_no' => $order_no, 'agency_id' => $this->auth_info['uid']])->find();
        if (!$order) {
            return $this->errorResponse(0, lang('订单不存在'));
        }
        if (2 == $order['type']) {//密码线
            return $this->errorResponse(0, lang('订单不能结束'));
        }
        if ($order['status'] == 2 || $order['status'] == 3) {
            return $this->errorResponse(0, lang('订单已结束'));
        }
        if ($order['agency_end'] == 1) {
            return $this->errorResponse(0, lang('申请结束中'));
        }

        if ($returned == 1) {
            if (!preg_match("/^[0-9]+(.[0-9]{1,2})?$/", $amount) || $amount < 0) {
                return ['code' => 0, 'msg' => '请输入正确的金额'];
            }
        } else {
            $amount = 0;
            $returned = 0;
        }

        $data = ['returned' => $returned, 'amount' => $amount, 'end_time' => $end_time, 'remark' => $remark];
        $params = ['end_info' => json_encode($data, JSON_UNESCAPED_UNICODE), 'agency_end' => 1, 'update_time' => time()];
        $update_params = ['order_active' => $params, 'lease_order' => $params];
        \think\Loader::model('Order', 'logic')->updateOrderData($order['order_no'], $order['uid'], $update_params);
        return $this->successResponse([], lang('提交申请成功'));
    }

}
