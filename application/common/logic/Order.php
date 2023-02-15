<?php

namespace app\common\logic;

use app\common\service\MercadoPay;

/**
 * 订单
 * @package app\common\logic
 */
class Order extends Common
{

    public function _initialize()
    {
        parent::_initialize();
    }


    //未完成订单
    public function activeList($condition = [], $page_size = 20, $isReturn = false)
    {
        $where = [];
        $pageParam = ['query' => []];
        $table = "order_active";//未完成订单表
        //判断订单状态
        $status = input('status', '', 'trim');
        isset($condition['status']) && $status = $condition['status'];
        if (!empty($status)) {
            $pageParam['query']['status'] = $status;
            if ('active' == $status) {//租借中
                $where['o.status'] = 1;
                $where['o.is_late'] = 0;
            } else if ('over' == $status) {//超时
                $where['o.status'] = 1;
                $where['o.is_late'] = 1;
            } else if ('end' == $status) {//代理商申请结束
                $where['o.status'] = 1;
                $where['o.agency_end'] = 1;
            } else if ('not_pay' == $status) {//待付款
                $where['o.status'] = 2;
                $where['o.is_pay'] = 0;
            } else if ('lose' == $status) {//丢失充电宝
                $where['o.status'] = 2;
                $table = "order_lose";
            } else {
                $where['o.is_pay'] = 0;
            }
        } else {
            $where['o.is_pay'] = 0;
        }

        //订单号
        $order_no = input('order_no', '', 'trim');
        if (!empty($order_no)) {
            $where['o.order_no'] = $order_no;
            $pageParam['query']['order_no'] = $order_no;
        }

        //会员号
        $mobile = input('mobile', '', 'trim');
        if ('' != $mobile) {
            $user_id = $this->db->name('user')->where(['mobile' => $mobile])->value('id');
            !$user_id && $user_id = 0;
            $where['o.uid'] = $user_id;
            $pageParam['query']['mobile'] = $mobile;
        }

        //客户端
        $app_type = input('app_type', 0, 'intval');
        if (!empty($app_type)) {
            $where['o.app_type'] = $app_type;
            $pageParam['query']['app_type'] = $app_type;
        }

        //判断订单类型
        $type = input('type', 0, 'intval');
        isset($condition['type']) && $type = $condition['type'];
        if (!empty($type)) {
            $where['o.type'] = $type;
            $pageParam['query']['type'] = $type;
        }

        //商家
        $sid = input('sid', 0, 'intval');
        if (!empty($sid)) {
            $where['o.sid'] = $sid;
            $pageParam['query']['sid'] = $sid;
            $pageParam['query']['seller_name'] = input('seller_name');
        }

        //设备名称
        $device_code = input('device_code', '', 'trim');
        if (!empty($device_code)) {
            $where['o.device_code'] = $device_code;
            $pageParam['query']['device_code'] = $device_code;
        }

        $lose_process = input('lose_process', '', 'trim');
        if (!empty($lose_process)) {
            $pageParam['query']['lose_process'] = $lose_process;
            $lose_process < 0 && $lose_process = 0;
            $where['o.lose_process'] = $lose_process;
        }

        //代理商
        $agency_id = input('aid', 0, 'intval');
        $employee_id = input('eid', 0, 'intval');

        if (!empty($agency_id)) {
            $where['o.agency_id'] = $agency_id;
            $pageParam['query']['aid'] = $agency_id;
            $pageParam['query']['agency_name'] = input('agency_name');
        }

        if (!empty($employee_id)) {
            $where['o.employee_id'] = $employee_id;
            $pageParam['query']['employee_id'] = $employee_id;
        }
        $start_time = input('start_time', '', 'trim');
        $end_time = input('end_time', '', 'trim');
        $patten = "/^[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/";
        if (preg_match($patten, $start_time) && preg_match($patten, $end_time)) {
            $where['o.create_time'] = ['between', [strtotime($start_time), strtotime($end_time)]];
            $pageParam['query']['start_time'] = $start_time;
            $pageParam['query']['end_time'] = $end_time;
        } else if (preg_match($patten, $start_time)) {
            $where['o.create_time'] = ['>=', strtotime($start_time)];
            $pageParam['query']['start_time'] = $start_time;
        } else if (preg_match($patten, $end_time)) {
            $where['o.create_time'] = ['<=', strtotime($end_time)];
            $pageParam['query']['end_time'] = $end_time;
        }

        $query = $this->db->name('order_active')
            ->alias('o')
            ->field('o.id')
            ->where($where)
            ->order('o.start_time DESC')
            ->paginate($page_size, false, $pageParam);
        $ids = $query->column('id');

        $status_text = [1 => lang('租借中'), 2 => lang('待付款'), 3 => lang('丢失充电宝'), 4 => lang('超时'), 5 => lang('代理商申请结束'),];
        $paginate = $query->render();
        $total = $query->total();
        $list = [];
        if ($ids) {
            $list = $this->db->name($table)
                ->alias('o')
                ->field('o.id,o.order_no,o.is_pay,o.is_lose,o.lose_process,o.type,o.sid,o.agency_id,o.uid,o.app_type,o.start_time,o.end_time,o.amount,o.status,o.device_id,o.battery_id,o.device_code,o.agency_end,o.expire_time,o.payment_time')
                ->where(['o.id' => ['IN', $ids]])
                ->order('o.start_time DESC')
                ->select();

            $seller_ids = array_column($list, 'sid');
            $agency_ids = array_column($list, 'agency_id');
            $user_ids = array_column($list, 'uid');

            $seller_data = $this->getSellerData($seller_ids);
            $agency_data = $this->getAgencyData($agency_ids);
            $user_data = $this->getUserData($user_ids);
        }
        foreach ($list as $k => $v) {
            $user = $user_data[$v['uid']];
            $v['nick'] = $this->getCustomerNick($user['nickCode']);
            $v['avatar'] = $user['avatar'];
            $v['member_id'] = $user['member_id'];
            $v['mobile'] = $user['mobile'];
            $v['email'] = $user['email'];
            empty($v['avatar']) && $v['avatar'] = config('customer.avatar');
            $v['seller_name'] = isset($seller_data[$v['sid']]) ? $seller_data[$v['sid']]['name'] : '';
            $v['agency_name'] = isset($agency_data[$v['agency_id']]) ? $agency_data[$v['agency_id']]['name'] : '';
            $v['is_cancel'] = false;
            $v['is_end'] = false;
            if ($v['status'] == 1 && $v['expire_time'] < time()) {//超时订单
                $v['status'] = 4;
            }
            //3天内已结束的订单
            if ($v['status'] == 2) {
                if (empty($v['is_pay'])) {
                    $v['is_cancel'] = true;
                } else if ($v['is_pay'] == 1 && (time() - $v['payment_time']) < 86400 * 3) {
                    $v['is_cancel'] = true;
                }
            }
            $v['start_time'] = date("d/m/Y H:i:s", $v['start_time']);
            $v['end_time'] = !empty($v['end_time']) ? date("d/m/Y H:i:s", $v['end_time']) : '';
            $v['app_name'] = config('app_name.' . $v['app_type']);

            $v['order_status'] = $v['status'];//订单状态后台使用
            if (in_array($v['status'], [1, 4]) && $v['agency_end'] == 1) {
                $v['order_status'] = 5; //代理商申请结束
            }
            $v['lose_ope'] = ($v['is_lose'] == 1 && $v['is_pay'] == 1 && empty($v['lose_process'])); //丢失充电宝操作
            !empty($v['lose_process']) && $v['is_cancel'] = false;//已处理丢失的不能取消
            //$v['is_lose'] == 1 && $v['order_status'] = 4;
            $v['status_text'] = $status_text[$v['order_status']];
            if ($v['status'] == 1 || $v['status'] == 4) {
                $v['is_end'] = true;
            }
            $list[$k] = $v;
        }

        return ['total' => $total, 'list' => $list];



    }

    //批量获取商户数据
    function getSellerData($seller_ids)
    {
        $this->lang = "'$.{$this->lang}'";
        $seller_query = $this->db->name('seller')
            ->field("id, JSON_UNQUOTE(name->$this->lang) name")
            ->where(['id' => ['IN', $seller_ids]])
            ->select();
        return array_column($seller_query, NULL, 'id');
    }

    //批量获取代理数据
    function getAgencyData($agency_ids)
    {
        $agency_query = $this->db->name('agency')
            ->field('id,name,type')
            ->where(['id' => ['IN', $agency_ids]])
            ->select();
        return array_column($agency_query, NULL, 'id');
    }

    //批量获取用户数据
    function getUserData($user_ids)
    {
        $user_query = $this->db->name('user')
            ->field('id,nickCode,avatar,member_id,mobile,email')
            ->where(['id' => ['IN', $user_ids]])
            ->select();
        return array_column($user_query, NULL, 'id');
    }

    /**
     * 列表
     */
    public function orderList($condition = [], $page_size = 20, $isReturn = false)
    {
        $where = [];
        $pageParam = ['query' => []];

        //判断订单状态
        $status = input('status', 0, 'intval');
        isset($condition['status']) && $status = $condition['status'];
        if (!empty($status)) {

            $pageParam['query']['status'] = $status;
            if (1 == $status) {//进行中
                $where['o.status'] = $status;
                $where['o.expire_time'] = ['>', time()];
                $where['o.agency_end'] = 0;
            } else if (4 == $status) {//超过3天则视为超时订单
                $where['o.status'] = 1;
                $where['o.end_time'] = ['exp', 'is null'];
                $where['o.expire_time'] = ['<', time()];
            } else if (5 == $status) {//代理商申请结束
                $where['o.status'] = 1;
                $where['o.agency_end'] = 1;
            } else if (6 == $status) {//已丢失
                $where['o.status'] = 2;
                $where['o.is_lose'] = 1;
            } else if (7 == $status) {//已归还未付款
                $where['o.status'] = 2;
                $where['o.is_pay'] = 0;
            } else if (2 == $status) { //已完成已付款
                $where['o.status'] = 2;
                $where['o.is_pay'] = 1;
            } else {
                $where['o.status'] = $status;
            }
        }


        //判断订单类型
        $type = input('type', 0, 'intval');
        isset($condition['type']) && $type = $condition['type'];
        if (!empty($type)) {
            $where['o.type'] = $type;
            $pageParam['query']['type'] = $type;
        }

        //订单号
        $order_no = input('order_no', '', 'trim');
        if (!empty($order_no)) {
            $where['o.order_no'] = $order_no;
            $pageParam['query']['order_no'] = $order_no;
        }

        //会员号
        $mobile = input('mobile', '', 'trim');
        if ('' != $mobile) {
            $user_id = $this->db->name('user')->where(['mobile' => $mobile])->value('id');
            !$user_id && $user_id = 0;
            $where['o.uid'] = $user_id;
            $pageParam['query']['mobile'] = $mobile;
        }


        //客户端
        $app_type = input('app_type', 0, 'intval');
        if (!empty($app_type)) {
            $where['o.app_type'] = $app_type;
            $pageParam['query']['app_type'] = $app_type;
        }

        //商家
        $sid = input('sid', 0, 'intval');
        if (!empty($sid)) {
            $where['o.sid'] = $sid;
            $pageParam['query']['sid'] = $sid;
            $pageParam['query']['seller_name'] = input('seller_name');
        }

        //设备名称
        $device_code = input('device_code', '', 'trim');
        if (!empty($device_code)) {
            $where['o.device_code'] = $device_code;
            $pageParam['query']['device_code'] = $device_code;
        }

        $lose_process = input('lose_process', '', 'trim');
        if (!empty($lose_process)) {

            $pageParam['query']['lose_process'] = $lose_process;
            $lose_process < 0 && $lose_process = 0;
            $where['o.lose_process'] = $lose_process;
        }

        //代理商
        $agency_id = input('aid', 0, 'intval');
        $employee_id = input('eid', 0, 'intval');

        isset($condition['aid']) && $agency_id = $condition['aid'];
        isset($condition['eid']) && $employee_id = $condition['eid'];

        if (!empty($agency_id)) {
            $where['o.agency_id'] = $agency_id;
            $pageParam['query']['aid'] = $agency_id;
            $pageParam['query']['agency_name'] = input('agency_name');
        }

        if (!empty($employee_id)) {
            $where['o.employee_id'] = $employee_id;
            $pageParam['query']['employee_id'] = $employee_id;
        }
        //时间查询
        $start_time = input('start_time', '', 'trim');
        $end_time = input('end_time', '', 'trim');
        $patten = "/^[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/";
        !preg_match($patten, $start_time) && $start_time = date("Y-m-01") . " 00:00:00";
        !preg_match($patten, $end_time) && $end_time = date("Y-m-d") . " 23:59:59";
        $where['o.create_time'] = ['between', [strtotime($start_time), strtotime($end_time)]];
        $pageParam['query']['start_time'] = $start_time;
        $pageParam['query']['end_time'] = $end_time;

        $monthNum = getMonthNum($start_time, $end_time);
        if ($monthNum > 1) {
            return $this->error(lang('查询时间跨度不能超过一个月'));
        }
        try {
            $start_table = $this->checkOrderMonth($start_time, 'lease_order');
            $end_table = $this->checkOrderMonth($end_time, 'lease_order');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        $table = $end_table;

        //金额是否大于0
        $amount = input('amount');
        if (!empty($amount)) {
            $where['o.amount'] = ['GT', 0];
            $pageParam['query']['amount'] = 1;
        }

        //查询的字段
        $fields = "o.id,o.order_no,o.is_pay,o.is_lose,o.lose_process,o.type,o.sid,o.agency_id,o.uid,o.app_type,o.start_time,o.end_time,o.billing_unit,o.amount,o.status,o.device_id,o.battery_id,o.device_code,o.agency_end,o.expire_time,o.payment_time";
        //单表总数
        $total = $total_end = $this->db->name($table)->alias('o')->where($where)->count();


        $page = input('page', 1, 'intval');
        $page = $page < 1 ? 1 : $page;
        $view_num = $page * $page_size;//显示数据
        $offset = ($page - 1) * $page_size;//查询游标

        $union_size = 0;//跨表查询游标数
        if ($monthNum > 0) {//跨月查询

            $total_start = $this->db->name($start_table)->alias('o')->where($where)->count();
            $total += $total_start;//两张表总数
            $view_num > $total && $view_num = $total;
            if ($offset > $total_end) {//游标大于结束表数量只查询起始表
                $table = $start_table;
                $offset = $offset - $total_end;
            } else if ($view_num > $total_end) {//跨表查询
                $union_size = $view_num - $total_end;
            }
        }
        $list = $this->db->name($table)
            ->alias('o')
            ->field($fields)
            ->where($where)
            ->order('start_time desc')
            ->limit($offset, $page_size)
            ->select();

        if ($union_size > 0) {//需要跨表查询
            $list2 = $this->db->name($start_table)
                ->alias('o')
                ->field($fields)
                ->where($where)
                ->order('start_time desc')
                ->limit(0, $union_size)
                ->select();
            $list = array_merge($list, $list2);
        }

        $paginate = $this->db->name('order_active')->where("id =0")->paginate($page_size, $total, $pageParam);
        $paginate = $paginate->render();


        if ($list) {
            $seller_ids = array_column($list, 'sid');
            $agency_ids = array_column($list, 'agency_id');
            $user_ids = array_column($list, 'uid');

            $seller_data = $this->getSellerData($seller_ids);
            $agency_data = $this->getAgencyData($agency_ids);
            $user_data = $this->getUserData($user_ids);
        }
        $order_status_text = [1 => lang('租借中'),
            2 => lang('已完成'),
            3 => lang('已撤销'),
            4 => lang('订单超时'),
            5 => lang('申请结束'),
            6 => lang('丢失充电宝'),
            7 => lang('已还待付款'),
        ];


        foreach ($list as $k => $v) {
            $user = $user_data[$v['uid']];
            $v['nick'] = $this->getCustomerNick($user['nickCode']);
            $v['avatar'] = $user['avatar'];
            $v['member_id'] = $user['member_id'];
            $v['mobile'] = $user['mobile'];
            $v['email'] = $user['email'];
            empty($v['avatar']) && $v['avatar'] = config('customer.avatar');
            $v['seller_name'] = isset($seller_data[$v['sid']]) ? $seller_data[$v['sid']]['name'] : '';
            $v['agency_name'] = isset($agency_data[$v['agency_id']]) ? $agency_data[$v['agency_id']]['name'] : '';
            $v['is_cancel'] = false;
            $v['is_end'] = false;
            if ($v['status'] == 1 && time() - $v['expire_time'] > 0) {//3天没有结束的订单则为超时
                $v['status'] = 4;
            }

            //3天内已结束的订单
            if ($v['status'] == 2) {
                if (empty($v['is_pay'])) {
                    $v['is_cancel'] = true;
                } else if ($v['is_pay'] == 1 && (time() - $v['payment_time']) < 86400 * 3) {
                    $v['is_cancel'] = true;
                }
            }

            $v['start_time'] = date("d/m/Y H:i:s", $v['start_time']);
            $v['end_time'] = !empty($v['end_time']) ? date("d/m/Y H:i:s", $v['end_time']) : '';
            $v['payment_time'] = !empty($v['payment_time']) ? date("d/m/Y H:i:s", $v['payment_time']) : '';
            $v['app_name'] = config('app_name.' . $v['app_type']);


            $v['order_status'] = $v['status'];//订单状态后台使用
            if (in_array($v['status'], [1, 4]) && $v['agency_end'] == 1) {
                $v['order_status'] = 5; //代理商申请结束
            }
            $v['lose_ope'] = ($v['is_lose'] == 1 && $v['is_pay'] == 1 && empty($v['lose_process'])); //丢失充电宝操作
            !empty($v['lose_process']) && $v['is_cancel'] = false;//已处理丢失的不能取消
            $v['status_text'] = $order_status_text[$v['order_status']];
            if ($v['status'] == 1 || $v['status'] == 4) {
                $v['is_end'] = true;
            }
            $list[$k] = $v;
        }

        if ($isReturn) {
            return ['total' => $total, 'list' => $list];
        }

        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
        $this->assign('list', $list);
        $this->assign('paginate', $paginate);
        $this->assign('title', lang('订单列表'));
    }

    //检查月表是否存在
    function checkOrderMonth($month, $name = 'lease')
    {
        $month = str_replace("-", "", $month);
        $month = substr($month, 0, 6);
        $table = $name . "_" . $month;
        $query = $this->db->query("SHOW TABLES LIKE '" . $table . "'");
        if (!$query) {
            save_log('sql', "表 $table 不存在");
            throw new \Exception("查询时间范围错误 ");
        }
        return $table;
    }


    /**
     * 丢失订单
     */
    public function loseList($condition = [], $page_size = 20, $isReturn = false)
    {
        $where = [];
        $pageParam = ['query' => []];

        //处理状态
        $lose_process = input('lose_process', '', 'trim');
        if (!empty($lose_process)) {
            $pageParam['query']['lose_process'] = $lose_process;
            $lose_process < 0 && $lose_process = 0;
            $where['o.lose_process'] = $lose_process;
        }

        //订单号
        $order_no = input('order_no', '', 'trim');
        if (!empty($order_no)) {
            $where['o.order_no'] = $order_no;
            $pageParam['query']['order_no'] = $order_no;
        }

        //会员号
        $mobile = input('mobile', '', 'trim');
        if ('' != $mobile) {
            $user_id = $this->db->name('user')->where(['mobile' => $mobile])->value('id');
            !$user_id && $user_id = 0;
            $where['o.uid'] = $user_id;
            $pageParam['query']['mobile'] = $mobile;
        }

        //客户端
        $app_type = input('app_type', 0, 'intval');
        if (!empty($app_type)) {
            $where['o.app_type'] = $app_type;
            $pageParam['query']['app_type'] = $app_type;
        }

        //商家
        $sid = input('sid', 0, 'intval');
        if (!empty($sid)) {
            $where['o.sid'] = $sid;
            $pageParam['query']['sid'] = $sid;
            $pageParam['query']['seller_name'] = input('seller_name');
        }

        //设备名称
        $device_code = input('device_code', '', 'trim');
        if (!empty($device_code)) {
            $where['o.device_code'] = $device_code;
            $pageParam['query']['device_code'] = $device_code;
        }

        //代理商
        $agency_id = input('aid', 0, 'intval');
        $employee_id = input('eid', 0, 'intval');

        isset($condition['aid']) && $agency_id = $condition['aid'];
        isset($condition['eid']) && $employee_id = $condition['eid'];

        if (!empty($agency_id)) {
            $where['o.agency_id'] = $agency_id;
            $pageParam['query']['aid'] = $agency_id;
            $pageParam['query']['agency_name'] = input('agency_name');
        }

        if (!empty($employee_id)) {
            $where['o.employee_id'] = $employee_id;
            $pageParam['query']['employee_id'] = $employee_id;
        }
        //时间查询
        $start_time = input('start_time', '', 'trim');
        $end_time = input('end_time', '', 'trim');
        $patten = "/^[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/";
        if (preg_match($patten, $start_time) && preg_match($patten, $end_time)) {
            $where['o.create_time'] = ['between', [strtotime($start_time), strtotime($end_time)]];
            $pageParam['query']['start_time'] = $start_time;
            $pageParam['query']['end_time'] = $end_time;
        } else if (preg_match($patten, $start_time)) {
            $where['o.create_time'] = ['>=', strtotime($start_time)];
            $pageParam['query']['start_time'] = $start_time;
        } else if (preg_match($patten, $end_time)) {
            $where['o.create_time'] = ['<=', strtotime($end_time)];
            $pageParam['query']['end_time'] = $end_time;
        }

        //金额是否大于0
        $amount = input('amount');
        if (!empty($amount)) {
            $where['o.amount'] = ['GT', 0];
            $pageParam['query']['amount'] = 1;
        }

        //查询的字段
        $fields = 'o.id,o.order_no,o.is_pay,o.is_lose,o.lose_process,o.type,o.sid,o.agency_id,o.uid,o.app_type,o.start_time,o.end_time,o.billing_unit,o.amount,o.status,o.device_id,o.battery_id,o.device_code,o.agency_end,o.expire_time,o.payment_time';

        $query = $this->db->name("order_lose")
            ->alias('o')
            ->field($fields)
            ->where($where)
            ->order('start_time desc')
            ->paginate($page_size, false, $pageParam);


        //$paginate = $query->render();
        $total = $query->total();
        $list = $query->all();

        if ($list) {
            $seller_ids = array_column($list, 'sid');
            $agency_ids = array_column($list, 'agency_id');
            $user_ids = array_column($list, 'uid');

            $seller_data = $this->getSellerData($seller_ids);
            $agency_data = $this->getAgencyData($agency_ids);
            $user_data = $this->getUserData($user_ids);
        }
        $order_status_text = [1 => lang('租借中'),
            2 => lang('已完成'),
            3 => lang('已撤销'),
            4 => lang('订单超时'),
            5 => lang('申请结束'),
            6 => lang('丢失充电宝'),
            7 => lang('已还待付款'),
        ];


        foreach ($list as $k => $v) {
            $user = $user_data[$v['uid']];
            $v['nick'] = $this->getCustomerNick($user['nickCode']);
            $v['avatar'] = $user['avatar'];
            $v['member_id'] = $user['member_id'];
            $v['mobile'] = $user['mobile'];
            $v['email'] = $user['email'];
            empty($v['avatar']) && $v['avatar'] = config('customer.avatar');
            $v['seller_name'] = isset($seller_data[$v['sid']]) ? $seller_data[$v['sid']]['name'] : '';
            $v['agency_name'] = isset($agency_data[$v['agency_id']]) ? $agency_data[$v['agency_id']]['name'] : '';
            $v['is_cancel'] = false;
            $v['is_end'] = false;
            if ($v['status'] == 1 && time() - $v['expire_time'] > 0) {//3天没有结束的订单则为超时
                $v['status'] = 4;
            }

            //3天内已结束的订单
            if ($v['status'] == 2) {
                if (empty($v['is_pay'])) {
                    $v['is_cancel'] = true;
                } else if ($v['is_pay'] == 1 && (time() - $v['payment_time']) < 86400 * 3) {
                    $v['is_cancel'] = true;
                }
            }

            $v['start_time'] = date("d/m/Y H:i:s", $v['start_time']);
            $v['end_time'] = !empty($v['end_time']) ? date("d/m/Y H:i:s", $v['end_time']) : '';
            $v['app_name'] = config('app_name.' . $v['app_type']);
            $v['payment_time'] = !empty($v['payment_time']) ? date("d/m/Y H:i:s", $v['payment_time']) : '';

            $v['order_status'] = $v['status'];//订单状态后台使用
            if (in_array($v['status'], [1, 4]) && $v['agency_end'] == 1) {
                $v['order_status'] = 5; //代理商申请结束
            }
            $v['lose_ope'] = ($v['is_lose'] == 1 && $v['is_pay'] == 1 && empty($v['lose_process'])); //丢失充电宝操作
            !empty($v['lose_process']) && $v['is_cancel'] = false;//已处理丢失的不能取消
            $v['status_text'] = $order_status_text[$v['order_status']];
            if ($v['status'] == 1 || $v['status'] == 4) {
                $v['is_end'] = true;
            }
            $list[$k] = $v;
        }

        if ($isReturn) {
            return ['total' => $total, 'list' => $list];
        }

        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
        $this->assign('list', $list);
        $this->assign('title', lang('订单列表'));
    }

    //取消密码线订单
    public function cancelOrder($order_no)
    {
        $month = substr($order_no, 0, 6);
        $order_table = getTableNo('lease_order', 'date', $month);
        $order = $this->db->name($order_table)->where(['order_no' => $order_no, 'type' => 2])->find();
        !$order && $this->error(lang('订单不存在'));
        if ($order['status'] == 3) {
            return $this->errorResponse(0, lang('订单已经撤销'));
        }
        if ($order['status'] != 2) {
            return $this->errorResponse(0, lang('进行中的订单请先结束'));
        }
        if (time() - $order['end_time'] > 86400 * 3) {
            return $this->errorResponse(0, lang('只能撤销三天内结束的订单'));
        }
        $agency_table = getTableNo('order_agency', 'date', $month);
        $this->db->startTrans();
        try {
            $this->db->name($order_table)->where(['order_no' => $order_no])->update(['status' => 3, 'brokerage_total' => 0, 'amount' => 0, 'payment_amount' => 0, 'refund_amount' => $order['amount'], 'update_time' => time()]);
            $this->db->name($agency_table)->where(['order_no' => $order_no])->update(['status' => 3, 'amount' => 0]);
            $this->db->name('order_active')->where(['order_no' => $order_no])->delete();
            if ($order['amount'] > 0) {
                $this->db->name('user')->where(['id' => $order['uid']])->setInc('balance', $order['amount']);
                $refund = $order['amount'];

                //代理统计
                $stat_text = [];
                $brokerage = $this->db->name('order_brokerage')->where(['order_no' => $order['order_no']])->select();
                $brokerage_cancel = 0;
                foreach ($brokerage as $v) {
                    $brokerage_cancel = bcadd($brokerage_cancel, $v['amount'], 2);
                    $stat_text[] = statSql('stat_agency', ['date' => date("Y-m-d"), 'agency_id' => $v['relation_id']],
                        ['wired_refund' => $refund, 'total_refund' => $refund, 'total_pay_refund' => $refund, 'brokerage_cancel' => $v['amount'], 'brokerage_pay_cancel' => $v['amount']]);

                    $stat_text[] = statSql('stat_agency_seller', ['date' => date("Y-m-d"), 'relation_id' => $v['relation_id'], 'sid' => $order['sid']],
                        ['refund_amount' => $refund, 'refund_pay_amount' => $refund]);
                }

                //平台统计
                $stat_text[] = statSql('stat_operator', ['date' => date("Y-m-d")],
                    ['wired_refund' => $refund, 'total_refund' => $refund, 'total_pay_refund' => $refund, 'brokerage_cancel' => $brokerage_cancel, 'brokerage_pay_cancel' => $brokerage_cancel]);

                //商家统计
                $stat_text[] = statSql('stat_seller', ['date' => date("Y-m-d"), 'sid' => $order['sid']], ['wired_refund' => $refund, 'total_refund' => $refund, 'total_pay_refund' => $refund]);
                statText($this->oCode, $stat_text);

                $params = [//资金日志
                    'uid' => $order['uid'],
                    'type' => 3,
                    'app_type' => $order['app_type'],
                    'amount' => $order['amount'],
                    'order_no' => $order['order_no'],
                    'create_time' => time(),
                ];
                \think\Loader::model('Payment', 'logic')->userAccountLog($params);
            }
            $this->db->name('order_brokerage')->where(['order_no' => $order_no])->update(['status' => 3]);
            $this->db->name('order_brokerage_' . date("Ym", $order['payment_time']))->where(['order_no' => $order_no])->update(['status' => 3]);

            $this->db->name('order_operate_log')->insert([
                'order_no' => $order['order_no'],
                'operate' => lang('取消订单'),
                'memo' => $order['amount'],
                'user_id' => $this->auth_info['id'],
                'create_time' => time(),
            ]);
            //$this->operateLog(1, '取消订单');
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            save_log('sql', $e->getMessage());
            return $this->errorResponse(0, lang('信息提交失败，请稍后重试'));
        }
        return $this->successResponse([], lang('撤销成功'));
    }


    //分成
    function brokerageList($condition = [], $page_size = 20, $isReturn = false)
    {
        $where = [];
        $pageParam = ['query' => []];

        //订单号
        $order_no = input('order_no', '', 'trim');
        if (!empty($order_no)) {
            $where['o.order_no'] = $order_no;
            $pageParam['query']['order_no'] = $order_no;
        }

        //结算状态
        $status = input('status', 0, 'intval');
        if (!empty($status)) {
            $where['o.status'] = $status;
            $pageParam['query']['status'] = $status;
        }

        //判断是否有按名查询
        $name = input('name', '', 'trim');
        if ('' != $name) {
            $where['a.name'] = ['LIKE', "%{$name}%"];
            $pageParam['query']['name'] = $name;
        }

        //判断是否有按手机查询
        $phone = input('phone', '', 'trim');
        if ('' != $phone) {
            $where['a.phone'] = ['LIKE', "%{$phone}%"];
            $pageParam['query']['phone'] = $phone;
        }

        $start_time = input('start_time', '', 'trim');
        $end_time = input('end_time', '', 'trim');
        $patten = "/^[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/";

        if (!preg_match($patten, $start_time) && !preg_match($patten, $end_time)) {
            $start_time = date("Y-m-01 00:00:00");
            $end_time = date("Y-m-d") . " 23:59:59";
        } else if (preg_match($patten, $start_time)) {
            $end_time = strtotime("+1 month -1 day", strtotime(substr($start_time, 0, 7) . "-01"));
            $end_time = date("Y-m-d", $end_time) . " 23:59:59";
        } else if (preg_match($patten, $end_time)) {
            $start_time = date("Y-m-01 00:00:00", strtotime($end_time));
        }


        if (substr($start_time, 0, 7) != substr($end_time, 0, 7)) {
            $this->error(lang('日期不能跨月'));
        }
        $pageParam['query']['start_time'] = $start_time;
        $pageParam['query']['end_time'] = $end_time;


        $where['o.create_time'] = ['between', [strtotime($start_time), strtotime($end_time)]];
        $query = $this->db->name('order_brokerage_' . date("Ym", strtotime($start_time)))
            ->alias('o')
            ->join("agency a", 'o.relation_id = a.id', 'LEFT')
            ->field('o.*,a.name,a.phone')
            ->where($where)
            ->order('o.id DESC')
            ->paginate($page_size, false, $pageParam);

        $list = $query->all();
        $paginate = $query->render();
        $total = $query->total();

        $role = config('user_type_name');
        $status_text = [1 => lang('待结算'), 2 => lang('已结算'), 3 => lang('撤销'), 9 => lang('充电宝赔偿')];
        foreach ($list as $k => $v) {
            $v['role'] = $role[$v['user_type']];
            $v['status'] = $status_text[$v['status']];
            $v['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
            $list[$k] = $v;
        }


        if ($isReturn) {
            return ['total' => $total, 'list' => $list];
        }

        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
        $this->assign('list', $list);
        $this->assign('status_text', $status_text);
        $this->assign('paginate', $paginate);
        $this->assign('title', lang('订单分成记录'));
    }


    //订单详情
    function detail($order)
    {
        $this->lang = "'$.{$this->lang}'";
        $order['app_type'] = config('app_name.' . $order['app_type']);
        $nick = $this->db->name('user')->where(['id' => $order['uid']])->value('nickCode');
        $logic = \think\Loader::model('Lease', 'logic');
        $billing = json_decode($order['billing_data'], true);
        $order_status_text = [1 => lang('租借中'),
            2 => lang('已完成'),
            3 => lang('已撤销'),
            4 => lang('订单超时'),
            5 => lang('申请结束'),
            6 => lang('丢失充电宝'),
            7 => lang('已还待付款'),
        ];
        if (1 == $order['status'] && 1 == $order['type'] && $order['is_late'] > 0) {//超时订单
            $order['status'] = 4;
        }
        if (2 == $order['status'] && empty($order['is_pay'])) {//超时订单
            $order['status'] = 7;
        }


        $use_time = !empty($order['end_time']) ? $order['end_time'] : time();
        $info = [
            'id' => $order['id'],
            'order_no' => $order['order_no'],
            'user_type' => $order['app_type'],
            'nick' => $this->getCustomerNick($nick),
            'order_type' => $order['type'],
            'device_id' => $order['device_code'],
            'battery_id' => $order['battery_id'],
            'seller' => $this->db->name("seller")->where(['id' => $order['sid']])->field("JSON_UNQUOTE(name->$this->lang) name")->find()['name'],
            'start_time' => $logic->formatTime($order['start_time']),
            'use_time' => $logic->calLeaseTime($order['start_time'], $use_time),
            'amount' => priceFormat($order['amount']),
            'free_time' => $order['free_time'],
            'billing' => $logic->showBilling($order['billing_data']),
            'ceiling' => $billing['ceiling'],
            'deposit' => $billing['deposit'],
            'device_price' => $billing['device_price'],
            'return_seller' => '',
            'return_time' => empty($order['end_time']) ? '' : $logic->formatTime($order['end_time']),
            'status' => $order['status'],
            'status_text' => $order_status_text[$order['status']],
            'brokerage' => [],
            'pay_auth_id' => $order['pay_auth_id'],
            'agency_end' => $order['agency_end'],
            'is_lose' => $order['is_lose'],
            'lose_process' => $order['lose_process'],
        ];
        if (!empty($order['return_sid'])) {
            $info['return_seller'] = $this->db->name('seller')->where(['id' => $order['return_sid']])->field("JSON_UNQUOTE(name->$this->lang) name")->find()['name'];
        }
        $brokerage = json_decode($order['brokerage_data'], true);
        if (!empty($brokerage)) {//显示各级代理分成
            $agency_ids = array_column($brokerage, 'id');
            $brokerage = array_column($brokerage, NULL, 'id');

            $agency = $this->db->name('agency')->field('id,name,parent_id')->where(['id' => ['IN', $agency_ids]])->select();
            $agency = array_column($agency, NULL, 'id');

            $role = config('user_type_name');


            foreach ($brokerage as $v) {
                $info['brokerage'][] = [
                    'id' => $v['id'],
                    'name' => $agency[$v['id']]['name'],
                    'type' => $role[$v['type']],
                    'role_type' => $v['type'],
                    'ratio' => $v['ratio'],
                ];
            }
        }
        return $info;
    }

    //免押订单支付状态
    function payStatus($order)
    {
        if (!$order) {
            $this->error(lang('订单不存在'));
        }

        $key = "pay_status:{$this->oCode}:{$order['pay_auth_id']}";
        $lock = cache($key);
        if ($lock) {
            $this->error(lang('访问频繁，请稍后重试'));
        }
        cache($key, 1, 10);
        if (2 != $order['status']) {
            cache($key, null);
            return ['status' => 2, 'code' => 2, 'msg' => lang('请先归还充电宝'), 'd' => 1];
        }
        if (empty($order['pay_auth_id'])) {
            cache($key, null);
            return ['status' => 1, 'code' => 1, 'msg' => lang('订单已支付'), 'd' => 2];
        }
        $log = $this->db->name('pay_auth_log')->where(['id' => $order['pay_auth_id']])->find();
        if (!$log || $log['status'] > 3) {
            cache($key, null);
            return ['status' => 1, 'code' => 1, 'msg' => lang('订单已支付'), 'type' => 1, 'd' => 3];
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
                }
            }
            $result = \think\Loader::model('Payment', 'logic')->finishRentBill($order);
            if (1 != $result['code']) {//失败
                cache($key, null);
                return ['code' => 2, 'status' => 2, 'msg' => lang("支付失败，请确定") . $client . lang("扣款账户有足够金额")];
            }
            $result['code'] = 1;
            $result['status'] = 1;
            $result['d'] = 4;
            cache($key, null);
            return $result;
        }
        $status = 0;
        $info = [];
        if ($order['app_type'] == config('app_type.wechat')) {//微信
            $result = \think\Loader::model('WechatApi', 'service')->wxvQueryrentbill($log['order_no']);
            save_log('wxtest0716', $result);
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
        if ($status < 1) {
            cache($key, null);
            return ['status' => 2, 'code' => 2, 'msg' => lang("请确定") . $client . lang("扣款账户有足够金额或者请稍后查看订单状态")];
        }

//        $this->db->name('lease_order')->where(['id' => $order_id])->update(['is_pay' => 1, 'payment_time' => time(), 'payment_amount' => $info['total_amount']]);
//        $this->db->name('pay_auth_log')->where(['id' => $log['id']])->update(['status' => 4]);
        \think\Loader::model('Payment', 'logic')->freezeNotifyProcess($pay_type, $info);
        cache($key, null);
        return ['status' => 1, 'code' => 1, 'msg' => lang('订单已支付'), 'd' => 5];
    }

    //订单支付状态数据修改
    function orderPayStatus($order_no, $uid, $payment_amount)
    {
        $params = ['is_pay' => 1, 'payment_time' => time(), 'payment_amount' => $payment_amount, 'update_time' => time()];
        $this->db->name('order_active')->where(['order_no' => $order_no])->update($params);
        //用户订单
        $user_table = getTableNo('order_user', 'hash', 16, $uid);
        $this->db->name($user_table)->where(['order_no' => $order_no])->update(['is_pay' => 1]);
        //月表
        $month = substr($order_no, 0, 6);
        $order_table = getTableNo('lease_order', 'date', $month);
        $this->db->name($order_table)->where(['order_no' => $order_no])->update($params);
        //代理商表
        $agency_table = getTableNo('order_agency', 'date', $month);
        $this->db->name($agency_table)->where(['order_no' => $order_no])->update(['is_pay' => 1]);
    }


    //待履约订单
    public function creditList($condition = [], $page_size = 20, $isReturn = false)
    {
        $page_size < 1 && $page_size = 20;
        $where = ['o.type' => 1, 'u.status' => ['in', [1, 2, 3, 5]], 'o.pay_auth_id' => ['>', 0]];
        $pageParam = ['query' => []];

        //订单号
        $order_no = input('order_no', '', 'trim');
        if (!empty($order_no)) {
            $where['o.order_no'] = $order_no;
            $pageParam['query']['order_no'] = $order_no;
        }
        //会员号
        $member_id = input('member_id', '', 'trim');
        if ('' != $member_id) {
            $user_id = $this->db->name('user')->where(['member_id' => $member_id])->value('id');
            !$user_id && $user_id = 0;
            $where['o.uid'] = $user_id;
            $pageParam['query']['member_id'] = $member_id;
        }
        //交易号
        $trade_no = input('trade_no', '', 'trim');
        if ('' != $trade_no) {
            $where['u.trade_no'] = $trade_no;
            $pageParam['query']['trade_no'] = $trade_no;
        }
        //客户端
        $app_type = input('app_type', 0, 'intval');
        if (!empty($app_type)) {
            $where['o.app_type'] = $app_type;
            $pageParam['query']['app_type'] = $app_type;
        }

        //商家
        $sid = input('sid', 0, 'intval');
        if (!empty($sid)) {
            $where['o.sid'] = $sid;
            $pageParam['query']['sid'] = $sid;
            $pageParam['query']['seller_name'] = input('seller_name');
        }

        //设备名称
        $device_code = input('device_code', '', 'trim');
        if (!empty($device_code)) {
            $where['o.device_code'] = $device_code;
            $pageParam['query']['device_code'] = $device_code;
        }

        //代理商
        $agency_id = input('aid', 0, 'intval');
        $employee_id = input('eid', 0, 'intval');

        isset($condition['aid']) && $agency_id = $condition['aid'];
        isset($condition['eid']) && $employee_id = $condition['eid'];

        if (!empty($agency_id)) {
            $where['o.agency_id'] = $agency_id;
            $pageParam['query']['aid'] = $agency_id;
            $pageParam['query']['agency_name'] = input('agency_name');
        }

        if (!empty($employee_id)) {
            $where['o.employee_id'] = $employee_id;
            $pageParam['query']['employee_id'] = $employee_id;
        }

        $amount = input('amount');
        if (!empty($amount)) {
            $where['o.amount'] = ['GT', 0];
            $pageParam['query']['amount'] = 1;
        }

        // $where['u.status'] = 0;
        $query = $this->db->name('order_active')
            ->alias('o')
            ->join("pay_auth_log u", 'o.pay_auth_id = u.id', 'LEFT')
            ->field('o.id,o.order_no,o.type,o.sid,o.agency_id,o.uid,o.app_type,o.is_pay,o.amount,o.status,o.device_code,o.start_time,o.end_time,o.expire_time,
            u.order_no as pay_order_no,u.trade_no,u.create_time,u.status as pay_status')
            ->where($where)
            ->order('o.id DESC')
            ->paginate($page_size, false, $pageParam);
        $list = $query->all();
        $paginate = $query->render();
        $total = $query->total();

        if ($list) {
            $seller_ids = array_column($list, 'sid');
            $agency_ids = array_column($list, 'agency_id');
            $user_ids = array_column($list, 'uid');
            $ids = array_column($list, 'id');

            $seller_query = $this->db->name('seller')
                ->field('id,name')
                ->where(['id' => ['IN', $seller_ids]])
                ->select();
            $seller_data = array_column($seller_query, NULL, 'id');

            $agency_query = $this->db->name('agency')
                ->field('id,name,type')
                ->where(['id' => ['IN', $agency_ids]])
                ->select();
            $agency_data = array_column($agency_query, NULL, 'id');

            $user_query = $this->db->name('user')
                ->field('id,nickCode,member_id')
                ->where(['id' => ['IN', $user_ids]])
                ->select();
            $user_data = array_column($user_query, NULL, 'id');

        }
        $order_status_text = [1 => lang('租借中'), 2 => lang('已归还'), 3 => lang('已撤销'), 4 => lang('超时')];
        $pay_status_text = [1 => lang('冻结中'), 2 => lang('转支付失败'), 3 => lang('待支付'), 4 => lang('完成'), 5 => lang('违约')];
        foreach ($list as $k => $v) {
            if ($v['status'] == 1 && time() - $v['expire_time'] > 0) {//3天没有结束的订单则为超时
                $v['status'] = 4;
            }
            $v['nick'] = isset($user_data[$v['uid']]) ? $this->getCustomerNick($user_data[$v['uid']]['nickCode']) : '';
            $v['member_id'] = isset($user_data[$v['uid']]) ? $user_data[$v['uid']]['member_id'] : '';
            $v['seller_name'] = isset($seller_data[$v['sid']]) ? $seller_data[$v['sid']]['name'] : '';
            $v['agency_name'] = isset($agency_data[$v['agency_id']]) ? $agency_data[$v['agency_id']]['name'] : '';
            $v['app_name'] = config('app_name.' . $v['app_type']);
            $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            $v['start_time'] = date("Y-m-d H:i:s", $v['start_time']);
            $v['overdueDay'] = '';
            if ($v['end_time']) {
                $v['overdueDay'] = ceil((time() - $v['end_time']) / 86400);
            }
            $v['is_overdue'] = $v['overdueDay'] > 15 && $v['app_type'] == 2 && $v['pay_status'] != 5 ? 1 : 0;
            $v['end_time'] = !empty($v['end_time']) ? date("Y-m-d H:i:s", $v['end_time']) : '';
            $v['pay_status_text'] = $pay_status_text[$v['pay_status']];
            $v['order_status_text'] = $order_status_text[$v['status']];
            $v['show_pay'] = 0;
            if ($v['status'] == 2 && $v['pay_status'] != 5) {
                $v['show_pay'] = 1;
            }
            $list[$k] = $v;
        }

        if ($isReturn) {
            return ['total' => $total, 'list' => $list];
        }

        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
        $this->assign('list', $list);
        $this->assign('paginate', $paginate);
        $this->assign('title', lang('待结算订单列表'));
    }


    //支付宝上报逾期
    public function overdue($order)
    {
        if (2 != $order['status']) {
            return ['code' => 0, 'msg' => lang('请先归还充电宝')];
        }
        if (1 == $order['is_pay'] || empty($order['pay_auth_id'])) {
            return ['code' => 0, 'msg' => lang('订单已支付')];
        }

        $log = $this->db->name('pay_auth_log')->where(['id' => $order['pay_auth_id'], 'status' => 2])->find();
        !$log && $this->error(lang('订单不存在'));
        if ($log['pay_type'] != config('pay_type.alipay')) {
            return ['code' => 0, 'msg' => lang('非支付宝授权订单')];
        }
        //信息同步
        $recharge = $this->db->name('recharge_log')->where(['auth_log_id' => $log['id'], 'pay_status' => 99, 'create_time' => ['LT', time() - 86400 * 15]])->order('id DESC')->find();
        if (!$recharge) {
            return ['code' => 0, 'msg' => '不符合逾期条件'];
        }
        $result = \think\Loader::model('AlipayApi', 'service')->orderInfo($recharge['trade_no'], $recharge['order_no'], 'VIOLATED');
        if ($result['code'] == 1) {
            $pay_status = 5;
            $this->db->name('pay_auth_log')->where(['id' => $log['id']])->update(['status' => $pay_status]);
            $this->db->name('pay_auth_log_' . substr($log['order_no'], 0, 4))->where(['order_no' => $log['order_no']])->update(['status' => $pay_status]);
            //结束订单
            $recharge = $this->db->name('recharge_log')->where(['auth_log_id' => $log['id'], 'pay_status' => 0])->select();
            foreach ($recharge as $v) {
                $result = \think\Loader::model('AlipayApi', 'service')->close($v['order_no']);
                if ($result['code'] == 1) {
                    $this->db->name('recharge_log')->where(['id' => $v['id']])->update(['pay_status' => 99]);
                }
            }
            return ['code' => 1, 'msg' => lang('上传成功')];
        }
        return ['code' => 0, 'msg' => $result['msg']];
    }

    //修改订单信息
    public function modifyOrder($order, $amount)
    {
        if (!preg_match("/^[0-9]+(.[0-9]{1,2})?$/", $amount) || $amount < 0) {
            return ['code' => 0, 'msg' => lang('请输入正确的金额')];
        }

        if ($amount > $order['amount']) {
            return ['code' => 0, 'msg' => lang('修改金额不能大于订单金额').$order['amount']];
        }
        if (priceFormat($amount) == priceFormat($order['amount'])) {
            return ['code' => 0, 'msg' => lang('金额没有变动')];
        }
        if (!empty($order['lose_process'])) {
            return ['code' => 0, 'msg' => lang('丢失订单已处理')];
        }
        $original_amount = $order['amount'];
        $refund = bcsub($order['amount'], $amount, 2);
        $order['amount'] = $amount;
        if ($order['is_pay'] == 1) {//已付款，直接退款
            if ($order['is_lose'] > 0) {//丢失了
                $config = $this->getOperatorConfig('charge_info');
                $config = json_decode($config, true);
                $config['device_price'] = priceFormat($config['device_price']);
                $order['brokerage_amount'] = bcsub($order['amount'], $config['device_price'], 2);
                $order['brokerage_amount'] < 0.01 && $order['brokerage_amount'] = 0;
            }


            $brokerage_table = 'order_brokerage_' . date("Ym", $order['payment_time']);
            //代理分成
            $brokerage = \think\Loader::model('Lease', 'logic')->calBrokerage($order);
            $brokerage_amount = priceFormat($brokerage['amount']);
            $brokerage = array_column($brokerage['brokerage'], NULL, 'relation_id');
            //原有分成
            $old_brokerage = $this->db->name('order_brokerage')->where(['order_no' => $order['order_no'], 'status' => 1])->select();
            $old_brokerage = array_column($old_brokerage, NULL, 'relation_id');
            //撤销的分成
            $brokerage_cancel = bcsub($order['brokerage_total'], $brokerage_amount, 2);

            $this->db->startTrans();
            try {
                $params = [
                    'amount' => $amount,
                    'payment_amount' => $amount,
                    'brokerage_total' => $brokerage_amount,
                    'update_time' => time()
                ];
                $update_params = [
                    'order_active' => $params,
                    'order_lose' => $params,
                    'lease_order' => $params,
                    'order_agency' => ['amount' => $amount],
                    'order_user' => ['amount' => $amount],
                ];
                $update_params['lease_order']['refund_amount'] = $refund;
                $this->updateOrderData($order['order_no'], $order['uid'], $update_params);
                $stat_text = [];
                foreach ($old_brokerage as $aid => $v) {
                    $tmp = !isset($brokerage[$aid]) ? 0 : $brokerage[$aid]['amount'];
                    $ratio = !isset($brokerage[$aid]) ? 0 : $brokerage[$aid]['ratio'];
//                    if ($tmp > 0) {
                    $this->db->name($brokerage_table)
                        ->where(['order_no' => $order['order_no'], 'relation_id' => $aid])
                        ->update(['amount' => $tmp, 'ratio' => $ratio]);

                    $this->db->name('order_brokerage')
                        ->where(['order_no' => $order['order_no'], 'relation_id' => $aid])
                        ->update(['amount' => $tmp, 'ratio' => $ratio]);
//                    } else {
//                        $this->db->name($brokerage_table)
//                            ->where(['order_no' => $order['order_no'], 'relation_id' => $aid])
//                            ->update(['status' => 3]);
//
//                        $this->db->name('order_brokerage')
//                            ->where(['order_no' => $order['order_no'], 'relation_id' => $aid])
//                            ->update(['status' => 3]);
//                    }

                    //撤销的分成金额
                    $tmp = bcsub($v['amount'], $tmp, 2);

                    $stat_text[] = statSql('stat_agency', ['date' => date("Y-m-d"), 'agency_id' => $aid], ['battery_refund' => $refund, 'battery_pay_refund' => $refund, 'total_refund' => $refund, 'total_pay_refund' => $refund, 'brokerage_cancel' => $tmp, 'brokerage_pay_cancel' => $tmp]);
                    $stat_text[] = statSql('stat_agency_seller', ['date' => date("Y-m-d"), 'relation_id' => $aid, 'sid' => $order['sid']], ['refund_amount' => $refund, 'refund_pay_amount' => $refund]);
                    $stat_text[] = statSql('stat_agency_device', ['date' => date("Y-m-d"), 'relation_id' => $aid, 'device_id' => $order['device_code']], ['refund_amount' => $refund, 'refund_pay_amount' => $refund]);

                }
                //店铺统计
                $stat_text[] = statSql('stat_seller', ['date' => date("Y-m-d"), 'sid' => $order['sid']], ['battery_refund' => $refund, 'battery_pay_refund' => $refund, 'total_refund' => $refund, 'total_pay_refund' => $refund]);
                //设备统计
                $stat_text[] = statSql('stat_device', ['date' => date("Y-m-d"), 'device_id' => $order['device_code']], ['pay_refund' => $refund, 'refund' => $refund]);
                //平台统计
                $stat_text[] = statSql('stat_operator', ['date' => date("Y-m-d")],
                    ['battery_refund' => $refund, 'battery_pay_refund' => $refund, 'total_refund' => $refund, 'total_pay_refund' => $refund, 'brokerage_cancel' => $brokerage_cancel, 'brokerage_pay_cancel' => $brokerage_cancel]);

                statText($this->oCode, $stat_text);

//                //用户余额
                $this->db->name('user')->where(['id' => $order['uid']])->setInc('balance', $refund);
                //直接退款
                if ( $refund > 0 ) {
                    $re = \think\Loader::model('Payment', 'logic')->refund($order['id'],$refund,'Refund of power bank rent');
                    if ( !$re['code'] ) {
                        return ['code' => 0, 'msg' => lang($re['msg'])];
                    }
                }

                $this->db->name('order_operate_log')->insert([
                    'order_no' => $order['order_no'],
                    'operate' => '修改订单金额',
                    'memo' => $amount,
                    'user_id' => $this->auth_info['id'],
                    'create_time' => time(),
                ]);
                $this->db->commit();
            } catch (\Exception $e) {
                $this->db->rollback();
                save_log('sql', $e->getMessage());
                return ['code' => 0, 'msg' => lang('信息提交失败，请稍后重试')];
            }

            return $this->successResponse([], lang('保存成功'));
        }
        return ['code' => 0, 'msg' => lang('订单状态异常')];
    }

    /**
     * 更新订单相关数据
     * @param $order_no
     * @param $uid
     * @param $params
     */
    public function updateOrderData($order_no, $uid, $params)
    {
        //进行中订单
        isset($params['order_active']) && $this->db->name('order_active')->where(['order_no' => $order_no])->update($params['order_active']);
        //丢失订单
        isset($params['order_lose']) && $this->db->name('order_lose')->where(['order_no' => $order_no])->update($params['order_lose']);
        //用户订单表
        $user_table = getTableNo('order_user', 'hash', 16, $uid);
        isset($params['order_user']) && $this->db->name($user_table)->where(['order_no' => $order_no])->update($params['order_user']);
        //订单月表
        $month = substr($order_no, 0, 6);
        $order_table = getTableNo('lease_order', 'date', $month);
        isset($params['lease_order']) && $this->db->name($order_table)->where(['order_no' => $order_no])->update($params['lease_order']);
        //代理订单表
        $agency_table = getTableNo('order_agency', 'date', $month);
        isset($params['order_agency']) && $this->db->name($agency_table)->where(['order_no' => $order_no])->update($params['order_agency']);
    }

    private function _modifyStat($order, $refund)
    {
        $order_table = getTableNo('order_agency', 'date', date("Ym", $order['start_time']));
        $query = $this->db->name($order_table)->field('relation_id')->where(["order_no" => $order['order_no']])->column('relation_id');
        foreach ($query as $aid) {
            $stat_text[] = statSql('stat_agency', ['date' => date("Y-m-d"), 'agency_id' => $aid], ['battery_refund' => $refund, 'battery_pay_refund' => $refund, 'total_refund' => $refund, 'total_pay_refund' => $refund]);
            $stat_text[] = statSql('stat_agency_seller', ['date' => date("Y-m-d"), 'relation_id' => $aid, 'sid' => $order['sid']], ['refund_amount' => $refund, 'refund_pay_amount' => $refund]);
            $stat_text[] = statSql('stat_agency_device', ['date' => date("Y-m-d"), 'relation_id' => $aid, 'device_id' => $order['device_code']], ['refund_amount' => $refund, 'refund_pay_amount' => $refund]);
        }
        //店铺统计
        $stat_text[] = statSql('stat_seller', ['date' => date("Y-m-d"), 'sid' => $order['sid']], ['battery_refund' => $refund, 'battery_pay_refund' => $refund, 'total_refund' => $refund, 'total_pay_refund' => $refund]);
        //设备统计
        $stat_text[] = statSql('stat_device', ['date' => date("Y-m-d"), 'device_id' => $order['device_code']], ['pay_refund' => $refund, 'refund' => $refund]);
        //平台统计
        $stat_text[] = statSql('stat_operator', ['date' => date("Y-m-d")], ['battery_refund' => $refund, 'battery_pay_refund' => $refund, 'total_refund' => $refund, 'total_pay_refund' => $refund]);
        statText($this->oCode, $stat_text);
    }

    //授权日志
    public function authList($condition = [], $page_size = 20, $isReturn = false)
    {
        $page_size < 1 && $page_size = 20;
        $pageParam = ['query' => []];

        $where = ['o.status' => ['<', 99], 'pay_status' => 1];

        //支付分号
        $order_no = input('order_no', '', 'trim');
        if (!empty($order_no)) {
            $where['o.order_no'] = $order_no;
            $pageParam['query']['order_no'] = $order_no;
        }

        //信用分号
        $trade_no = input('trade_no', '', 'trim');
        if (!empty($trade_no)) {
            $where['o.trade_no'] = $trade_no;
            $pageParam['query']['trade_no'] = $trade_no;
        }

        //会员号
        $member_id = input('member_id', '', 'trim');
        if ('' != $member_id) {
            $user_id = $this->db->name('user')->where(['member_id' => $member_id])->value('id');
            !$user_id && $user_id = 0;
            $where['o.uid'] = $user_id;
            $pageParam['query']['member_id'] = $member_id;
        }

        //客户端
        $app_type = input('app_type', 0, 'intval');
        if (!empty($app_type)) {
            $where['o.pay_type'] = $app_type;
            $pageParam['query']['app_type'] = $app_type;
        }

        $year = input('year', date("Y"), 'intval');
        if (empty($year) || !in_array($year, range(2019, date("Y")))) {
            $year = date("Y");
        }
        $table = 'pay_auth_log';
        $status = input('status', 'active');
        if ($status === 'complate') {
            $table = 'pay_auth_log_' . $year;
        } else {
            $where['o.status'] = ['<', 4];
        }

        $pageParam['query']['status'] = $status;
        $pageParam['query']['year'] = $year;

        $page_size = 20;
        $query = $this->db->name($table)
            ->alias('o')
            ->field('o.id')
            ->where($where)
            ->order('o.create_time DESC')
            ->paginate($page_size, false, $pageParam);
        $ids = $query->column('id');


        $paginate = $query->render();
        $total = $query->total();
        $list = [];

        if ($ids) {
            $list = $this->db->name($table)
                ->where(['id' => ['IN', $ids]])
                ->order('create_time DESC')
                ->select();

            $user_ids = array_column($list, 'uid');
            $user_data = $this->getUserData($user_ids);
        }
        $status_text = [0 => lang('未使用'), 1 => lang('冻结中'), 2 => lang('解冻失败'), 3 => lang('转支付'), 4 => lang('完成'), 5 => lang('违约'), 99 => lang('取消')];
        foreach ($list as $k => $v) {
            $user = $user_data[$v['uid']];
            $v['nick'] = $this->getCustomerNick($user['nickCode']);
            $v['avatar'] = $user['avatar'];
            $v['member_id'] = $user['member_id'];
            empty($v['avatar']) && $v['avatar'] = config('customer.avatar');
            $v['app_name'] = config('app_name.' . $v['pay_type']);
            $v['auth_time'] = date("Y-m-d H:i:s", $v['create_time']);
            $v['lease_order'] = !empty($v['lease_order_no']) ? $v['lease_order_no'] : '';
            $v['status_text'] = $status_text[$v['status']];
            $list[$k] = $v;
        }

        if ($isReturn) {
            return ['total' => $total, 'list' => $list];
        }
        $years = [];
        foreach (range(2019, date("Y")) as $v) {
            $years[] = ['val' => $v];
        }
        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
        $this->assign('list', $list);
        $this->assign('years', $years);
        $this->assign('paginate', $paginate);
        $this->assign('title', lang('订单列表'));
    }

    //代理订单
    public function agencyList($page_size = 10, $agency = [])
    {
        $page_size < 1 && $page_size = 10;
        $is_sub = input('is_sub', '', 'trim');//代理商有下级
        $status = input('status', 'end', 'trim');

        $month = input('month', date("Y-m"), 'trim');
        !preg_match("/^[0-9]{4}\-[0-9]{2}$/", $month) && $month = date("Y-m");
        $start_time = $month . "-01";//开始时间
        $end_time = date('Y-m-d', strtotime("$start_time +1 month -1 day"));//结束时间


        $params = ['start_time' => $start_time, 'end_time' => $end_time];
        $where = $this->_getAgencyCondition($params, $agency);
        $where = $this->_getAgencyConditionStatus($where, $status);
        $where = implode(" AND ", $where);

        try {
            $table = $this->checkOrderMonth($start_time, 'order_agency');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        $fields = 'a.order_no,a.agency_end,a.type,a.sid,a.agency_id,a.employee_id,a.uid,a.app_type,a.start_time,a.end_time,a.amount,a.status,a.device_id,a.battery_id,a.is_pay,a.is_credit';
        $query = $this->db->name($table)
            ->alias('a')
            ->field($fields)
            ->where($where)
            ->order('a.start_time DESC')
            ->paginate($page_size);

        $total = $query->total();
        $data = $query->all();


        $sids = [];
        $uids = [];
        foreach ($data as $k => $v) {
            $sids[] = $v['sid'];
            $uids[] = $v['uid'];
            $sub_id = ('true' == $is_sub) ? $v['agency_id'] : $v['employee_id'];
            $sub_ids[] = $sub_id;
            $data[$k]['sub_id'] = $sub_id;
        }
        $sids = array_unique($sids);
        $uids = array_unique($uids);
        $users = $this->db->name('user')->field('id,nickCode,avatar')->where(['id' => ['IN', $uids]])->select();
        $users = array_column($users, NULL, 'id');

        $sellers = $this->db->name('seller')->field('id,name')->where(['id' => ['IN', $sids]])->select();
        $sellers = array_column($sellers, NULL, 'id');

        $subs = $this->db->name('agency')->field('id,name')->where(['id' => ['IN', $sub_ids]])->select();
        $subs = array_column($subs, NULL, 'id');
        $list = [];
        $logic = \think\Loader::model('Lease', 'logic');
        foreach ($data as $v) {
            empty($users[$v['uid']]['avatar']) && $users[$v['uid']]['avatar'] = config('customer.avatar');
            empty($v['end_time']) && $v['end_time'] = time();

            $can_end = 0;
            if ('agency' == $agency['role']
                && in_array($v['status'], [1, 4])
                && $v['agency_id'] == $agency['id']) {
                $can_end = 1;
            }
            if ($v['agency_end'] == 1) {
                $can_end = 2;
            }

            $tmp = [
                'order_no' => $v['order_no'],
                'start_time' => date("Y-m-d H:i:s", $v['start_time']),
                'nick' => $this->getCustomerNick($users[$v['uid']]['nickCode']),
                'avatar' => $users[$v['uid']]['avatar'],
                'user_type' => config('app_name.' . $v['app_type']),
                'use_time' => $logic->calLeaseTime($v['start_time'], $v['end_time']),
                'amount' => $v['amount'],
                'seller' => isset($sellers[$v['sid']]['name']) ? $sellers[$v['sid']]['name'] : '',
                'agency_name' => isset($subs[$v['sub_id']]) ? $subs[$v['sub_id']]['name'] : '',
                'agency_avatar' => config('qcloudurl') . config('seller_img.avatar'),
                'can_end' => $can_end,
                'is_pay' => $v['is_pay'],
                'credit' => null,
            ];
            if ($v['is_credit'] > 0) {
                $tmp['credit'] = ($v['app_type'] == config('app_type.wechat')) ? '微信信用' : '芝麻信用';
            }
            $list[] = $tmp;
        }

        $isVip = $this->db->name('agency')->where(['id' => $agency['uid']])->value('is_vip');

        $data = ['total' => $total, 'list' => $list, 'is_vip' => $isVip];
        return $data;
    }

    private function _getAgencyCondition($params = [], $agency = [])
    {
        $is_sub = input('is_sub', '', 'trim');//代理商有下级
        $keyword = input('keyword', '', 'trim');//订单号&商户名
        $type = input('type', 0, 'intval');//订单类型
        $seller = input('seller', '', 'trim');//商户
        $member_id = input('member_id', '', 'trim');//会员id
        $uid = $agency['id'];

        $where = [];
        $where[] = "a.relation_id = '{$uid}'";

        $patten = "/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/";
        if (preg_match($patten, $params['start_time']) && preg_match($patten, $params['end_time'])) {
            $where[] = "a.start_time  BETWEEN " . strtotime($params['start_time']) . " AND  " . (strtotime($params['end_time']) + 86399);
        } else if (preg_match($patten, $params['start_time'])) {
            $where[] = "a.start_time > '" . (strtotime($params['start_time']) - 1) . "'";
        } else if (preg_match($patten, $params['end_time'])) {
            $where[] = "a.start_time < '" . (strtotime($params['end_time']) - 1) . "'";
        }
        if (!empty($type) && in_array($type, [1, 2])) {
            $where[] = "a.type = $type ";
        }

        //订单号
        if ('' != $keyword) {
            $where[] = "a.order_no = '" . addslashes($keyword) . "'";
        }
        //会员
        if (!empty($member_id)) {
            $member_id = $this->db->name('user')->where(['member_id' => $member_id])->value('id');
            !$member_id && $member_id = 0;
            $where[] = "a.uid = '" . addslashes($member_id) . "'";
        }

        if (!empty($type) && in_array($type, [1, 2])) {
            $where[] = "a.type = '" . addslashes($type) . "'";
        }

        $seller_Ids = [];//店铺关键字查询
        if ('agency' == $agency['role']) {
            //商家
            if ('' != $seller) {
                if ($is_sub == 'true') {
                    $ids = \think\Loader::model('Agency', 'logic')->subAgencyIds($uid);
                    empty($ids) && $ids = ['-1'];
                } else {
                    $ids = [$uid];
                }
                $seller_Ids = $this->db->name('seller')->where(['not_delete' => 1, 'agency_id' => ['IN', $ids], 'name' => ['LIKE', "%{$seller}%"]])->column('id');
            }

            if ($is_sub == 'true') {
                $where[] = "a.is_self = 0";
            } else {
                $where[] = "a.is_self = 1";
            }

        } else if ('employee' == $agency['role']) {
            if ('' != $seller) {
                $seller_Ids = $this->db->name('seller')->where(['not_delete' => 1, 'employee_id' => $uid, 'name' => ['LIKE', "%{$seller}%"]])->column('id');
            }
        } else if ('seller' == $agency['role']) {
            if ('' != $seller) {
                $seller_Ids = $this->db->name('seller')->where(['not_delete' => 1, 'manager_id' => $uid, 'name' => ['LIKE', "%{$seller}%"]])->column('id');
            }
        }
        if ('' != $seller) {
            if (empty($seller_Ids)) {
                $where[] = "a.sid  = -1";
            } else {
                $where[] = "a.sid IN (" . implode(',', $seller_Ids) . ")";
            }
        }
        return $where;
    }

    private function _getAgencyConditionStatus($where = [], $status)
    {
        switch ($status) {
            case 'active'://租借中
                $where[] = 'a.status = 1 ';
                break;
            case 'end'://已完成
                $where[] = 'a.status = 2 ';
                $where[] = 'a.is_pay = 1';
                break;
            case 'not_pay'://待支付
                $where[] = 'a.status = 2 ';
                $where[] = 'a.is_pay = 0';
                break;
            case 'over'://超时
                $where[] = 'a.status = 1 ';
                $where[] = 'a.is_late = 1 ';
                break;
            case 'cancel'://已取消
                $where[] = 'a.status = 3 ';
                break;
        }

        return $where;
    }


}
