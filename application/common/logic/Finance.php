<?php

namespace app\common\logic;

use app\common\logic\Common;
use think\Request;
use think\Db;
use think\File;
use think\Session;
use think\Cookie;
use Godok\Org\FileManager;

/**
 * 财务相关
 * @package app\common\logic
 */
class Finance extends Common
{

    public function _initialize()
    {
        parent::_initialize();
    }

    //充值记录
    public function rechargeLog($condition = [], $pages = 20, $isReturn = false)
    {
        $where = ['a.pay_status' => 1];
        $pageParam = ['query' => []];

        //订单号
        $order_no = input('order_no', '', 'trim');
        if (!empty($order_no)) {
            $where['a.order_no'] = $order_no;
            $pageParam['query']['order_no'] = $order_no;
        }
        //判断是否有会员查询
        $member_id = input('member_id', '', 'trim');
        if ('' != $member_id) {
            $where['u.member_id'] = $member_id;
            $pageParam['query']['member_id'] = $member_id;
        }
        //判断支付方式
        $type = input('type', 0, 'intval');
        if (!empty($type)) {
            $where['a.pay_type'] = $type;
            $pageParam['query']['type'] = $type;
        }

        $start_time = input('start_time', '', 'trim');
        $end_time = input('end_time', '', 'trim');
        $patten = "/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/";
        if (!preg_match($patten, $start_time) && !preg_match($patten, $end_time)) {
            $end_time = date("Y-m-d");
            $start_time = date("Y-m-01");
        }
        $stat_where = ['a.pay_status' => 1]; //统计总金额
        if (preg_match($patten, $start_time) && preg_match($patten, $end_time)) {
            $where['a.payment_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
            $stat_where['payment_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
            $pageParam['query']['start_time'] = $start_time;
            $pageParam['query']['end_time'] = $end_time;
        } else if (preg_match($patten, $start_time)) {
            $where['a.payment_time'] = ['>=', strtotime($start_time)];
            $stat_where['payment_time'] = ['>=', strtotime($start_time)];
            $pageParam['query']['start_time'] = $start_time;
        } else if (preg_match($patten, $end_time)) {
            $where['a.payment_time'] = ['<=', strtotime($end_time) + 86399];
            $stat_where['payment_time'] = ['<=', strtotime($end_time) + 86399];
            $pageParam['query']['end_time'] = $end_time;
        }

        $stat = [
            'total_recharge' => 0,
            'wechat_recharge' => 0,
            'alipay_recharge' => 0,
        ];

        $query = $this->db->name('recharge_log')
            ->alias('a')
            ->join("user u", 'a.uid = u.id')
            ->field('a.*,u.nickCode,u.is_auth,u.avatar,u.member_id')
            ->where($where)
            ->order('a.payment_time DESC')
            ->paginate($pages, false, $pageParam);
     
        $list = $query->all();
        $total = $query->total();

        if ($total > 0) {
            $stat_query = $this->db->name('recharge_log')
                ->alias('a')
                ->field("SUM(amount) as amount,pay_type")
                ->group('pay_type')
                ->where($stat_where)
                ->select();
            foreach ($stat_query as $v) {
                if ($v['pay_type'] == 1) {
                    $stat['wechat_recharge'] = priceFormat($v['amount']);
                } else {
                    $stat['alipay_recharge'] = priceFormat($v['amount']);
                }
                $stat['total_recharge'] = bcadd($stat['total_recharge'] , $v['amount'], 2);
            }
        }


        foreach ($list as &$v) {
            $v['payment_time'] = date("Y-m-d H:i:s", $v['payment_time']);
            empty($v['avatar']) && $v['avatar'] = config('customer.avatar');
            $v['nick'] = $this->getCustomerNick($v['nickCode']);
        }
        return ['total' => $total, 'list' => $list];
    }


    //退款记录
    public function refundLog($condition = [], $pages = 20, $isReturn = false)
    {
        $where = [];
        $pageParam = ['query' => ['a.status' => 1]];

        //订单号
        $order_no = input('order_no', '', 'trim');
        if (!empty($order_no)) {
            $where['a.order_no'] = $order_no;
            $pageParam['query']['order_no'] = $order_no;
        }
        //判断是否有会员查询
        $member_id = input('member_id', '', 'trim');
        if ('' != $member_id) {
            $where['u.member_id'] = $member_id;
            $pageParam['query']['member_id'] = $member_id;
        }
        //判断支付方式
        $type = input('type', 0, 'intval');
        if (!empty($type)) {
            $where['a.pay_type'] = $type;
            $pageParam['query']['type'] = $type;
        }

        $start_time = input('start_time', '', 'trim');
        $end_time = input('end_time', '', 'trim');
        $patten = "/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/";
        if (!preg_match($patten, $start_time) && !preg_match($patten, $end_time)) {
            $end_time = date("Y-m-d");
            $start_time = date("Y-m-01");
        }
        $stat_where = ['a.status' => 1]; //统计总金额
        if (preg_match($patten, $start_time) && preg_match($patten, $end_time)) {
            $where['a.refund_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
            $stat_where['refund_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
            $pageParam['query']['start_time'] = $start_time;
            $pageParam['query']['end_time'] = $end_time;
        } else if (preg_match($patten, $start_time)) {
            $where['a.refund_time'] = ['>=', strtotime($start_time)];
            $stat_where['refund_time'] = ['>=', strtotime($start_time)];
            $pageParam['query']['start_time'] = $start_time;
        } else if (preg_match($patten, $end_time)) {
            $where['a.refund_time'] = ['<=', strtotime($end_time) + 86399];
            $stat_where['refund_time'] = ['<=', strtotime($end_time) + 86399];
            $pageParam['query']['end_time'] = $end_time;
        }

        $stat = [
            'total_refund' => 0,
            'wechat_refund' => 0,
            'alipay_refund' => 0,
        ];


        $query = $this->db->name('refund_log')
            ->alias('a')
            ->join("user u", 'a.uid = u.id')
            ->field('a.*,u.nickCode,u.is_auth,u.avatar,u.member_id')
            ->where($where)
            ->order('a.id DESC')
            ->paginate($pages, false, $pageParam);
        $paginate = $query->render();
        $list = $query->all();
        $total = $query->total();

        if ($total > 0) {
            $stat_query = $this->db->name('refund_log')
                ->alias('a')
                ->field("SUM(amount) as amount,pay_type")
                ->group('pay_type')
                ->where($stat_where)
                ->select();
            foreach ($stat_query as $v) {
                if ($v['pay_type'] == 1) {
                    $stat['wechat_refund'] = priceFormat($v['amount']);
                } else {
                    $stat['alipay_refund'] = priceFormat($v['amount']);
                }
                $stat['total_refund'] = bcadd($stat['total_refund'] , $v['amount'], 2);
            }
        }

        foreach ($list as &$v) {
            $v['refund_time'] = date("Y-m-d H:i:s", $v['refund_time']);
            empty($v['avatar']) && $v['avatar'] = config('customer.avatar');
            $v['nick'] = $this->getCustomerNick($v['nickCode']);
        }
        $this->operateLog($this->auth_info['id'], '退款');
        if ($isReturn) {
            return ['total' => $total, 'list' => $list];
        }

        $this->assign('title', lang('退款记录'));
        $this->assign('list', $list);
        $this->assign('paginate', $paginate);
        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
    }

    //分成记录,必须按月和代理查找
    public function brokerageLog($condition = [], $pages = 20, $isReturn = false)
    {
        $where = [];
        $pageParam = ['query' => []];

        //状态
        $status = input('status', 0, 'intval');
        isset($condition['status']) && $status = $condition['status'];
        if (!empty($user_type)) {
            $where['status'] = $user_type;
            $pageParam['query']['status'] = $user_type;
        }
        //用户类型
        $user_type = input('user_type', 0, 'intval');
        isset($condition['user_type']) && $user_type = $condition['user_type'];
        if (!empty($user_type)) {
            $where['user_type'] = $user_type;
            $pageParam['query']['user_type'] = $user_type;
        }
        //用户id
        $user_id = input('user_id', 0, 'intval');
        isset($condition['user_id']) && $user_id = $condition['user_id'];
        if (!empty($user_id)) {
            $where['relation_id'] = $user_id;
            $pageParam['query']['relation_id'] = $user_id;
        }
        //订单id
        $order_no = input('order_no', '', 'trim');
        if (!empty($order_no)) {
            $where['order_no'] = $order_no;
            $pageParam['query']['order_no'] = $order_no;
        }
        $month = input('month', date("Ym"), 'trim');
        $month = str_replace("-","",$month);
        //$month= '202005';
        $table =  getTableByDate("order_brokerage", $month);
        $query = $this->db->query("SHOW TABLES LIKE '" . $table . "'");
        if (!$query) {
            if ($isReturn) {
                return ['total' => 0, 'list' => []];
            }
            $this->error(lang('请选择正确的月份'));
        }

        $query = $this->db->name($table)
            ->where($where)
            ->order('create_time DESC')
            ->paginate($pages, false, $pageParam);
        $paginate = $query->render();
        $list = $query->all();
        $total = $query->total();
        if ($list) {
            $order_month = $order_data = [];
            foreach ($list as $v) {
                $month = substr($v['order_no'], 0, 6);
                $order_month[$month][] = $v['order_no'];
            }
            foreach ($order_month as $month => $order_ids) {
            	$tmp_table = getTableByDate('lease_order', $month);
                $orders = $this->db->name($tmp_table)
                    ->field('order_no,type,app_type,device_code')
                    ->where(['order_no' => ['IN', $order_ids]])
                    ->select();
                foreach($orders as $order){
                    $order_data[$order['order_no']] = $order;
                }
            }

            $status_text = [1 => lang('审核中'), '2' => lang('已结算'), 3 => lang('已撤销'), 9 => lang('充电宝赔偿')];
            foreach ($list as $k=>$v) {
                $order = $order_data[$v['order_no']];
                $v['app_type'] = config('app_name.' . $order['app_type']);
                $v['order_type'] = $order['type'];
                $v['device_code'] = $order['device_code'];
                $v['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
                $v['settlement_time'] = empty($v['settlement_time'])? '':date("Y-m-d H:i:s", $v['create_time']);
                $v['status_text'] = $status_text[$v['status']];
                unset($v['order_id'], $v['user_type'], $v['relation_id']);
                $list[$k] = $v;
            }
        }
        if ($isReturn) {
            return ['total' => $total, 'list' => $list];
        }

        $this->assign('title', lang('分成记录'));
        $this->assign('list', $list);
        $this->assign('paginate', $paginate);
        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
    }


    //资金流水记录
    public function tradeLog($condition = [], $pages = 20, $isReturn = false)
    {
        $pages = intval($pages);
        $pages < 1 && $pages =20;
        $where = [];
        $pageParam = ['query' => []];
        $stat_where = [];
        //支付方式
        $pay_type = input('pay_type', 0, 'intval');
        if (!empty($pay_type)) {
            $where['a.pay_type'] = $pay_type;
            $pageParam['query']['pay_type'] = $pay_type;
        }

        //收支类型
        $type = input('type', 0, 'intval');
        if (!empty($type)) {
            $where['a.type'] = $type;
            $pageParam['query']['type'] = $type;
        }

        //会员号
        $member_id = input('member_id', 0, 'intval');
        if (''!= $member_id) {
            $uid = $this->db->name('user')->where(['member_id'=>$member_id])->value('id');
            !$uid && $uid = -1;
            $where['a.uid'] = $uid;
            $pageParam['query']['member_id'] = $member_id;
        }

        isset($condition['uid']) && $uid = $condition['uid'];
        if ( !empty($uid) ) {
            $where['a.uid'] = $uid;
            $pageParam['query']['uid'] = $uid;
        }

        $start_time = input('start_time', date("Y-m-01"), 'trim');
        $end_time = input('end_time', date("Y-m-d"), 'trim');
        $patten = "/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/";
        !preg_match($patten, $start_time) && $start_time = date("Y-m-01");
        !preg_match($patten, $end_time) && $end_time = date("Y-m-d");
        if(substr($start_time,0,7)!= substr($end_time,0,7)){
            $this->error(lang('只能查询一个月的流水记录'));
        }
        $table = 'trade_log_'.date("Y",strtotime($start_time));
        $where['a.create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        $stat_where['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];

        $pageParam['query']['start_time'] = $start_time;
        $pageParam['query']['end_time'] = $end_time;

        $trade_no = input('trade_no');
        if('' != $trade_no){
            $where['a.trade_no'] = $trade_no;
            $pageParam['query']['trade_no'] = $type;
        }

        $is_excel = input('is_excel', 0, 'intval');
        $is_excel == 1 && $pages = 100000;

        $query = $this->db->name($table)
            ->alias('a')
            ->join("user u", 'a.uid = u.id')
            ->field('a.*,u.nickCode,u.is_auth,u.avatar,u.member_id')
            ->where($where)
            ->order('a.create_time DESC')
            ->paginate($pages, false, $pageParam);
        $paginate = $query->render();
        $list = $query->all();
        $total = $query->total();

        foreach ($list as $k => $v) {
            $v['create_time'] = date("d-m-Y H:i:s", $v['create_time']);
            empty($v['avatar']) && $v['avatar'] = config('customer.avatar');
            $v['nick'] = $this->getCustomerNick($v['nickCode']);
            $v['pay_type_text'] = '';
            $v['pay_type_text'] = 'Mercado Pago';
            $v['type_text'] = ($v['type'] == 1) ? lang('充值') : lang('退款');
            $v['order_no_text'] = "\t{$v['order_no']}\t";
            $v['trade_no_text'] = "\t{$v['trade_no']}\t";
            $list[$k] = $v;
        }
        $stat = [
            'wechat_recharge' => 0,
            'wechat_refund' => 0,
            'wechat_balance' => 0,
            'alipay_recharge' => 0,
            'alipay_refund' => 0,
            'alipay_balance' => 0,
        ];
        $stat_query = $this->db->name($table)
            ->field("SUM(amount) as amount,pay_type,type")
            ->group('pay_type,type')
            ->where($stat_where)
            ->select();
        foreach ($stat_query as $v) {
            if ($v['pay_type'] == 1) {
                if ($v['type'] == 1) {
                    $stat['wechat_recharge'] = priceFormat($v['amount']);
                } else {
                    $stat['wechat_refund'] = priceFormat($v['amount']);
                }
            } else {
                if ($v['type'] == 1) {
                    $stat['alipay_recharge'] = priceFormat($v['amount']);
                } else {
                    $stat['alipay_refund'] = priceFormat($v['amount']);
                }
            }
        }
        $stat['total_recharge'] = bcadd($stat['wechat_recharge'] , $stat['alipay_recharge'],2);
        $stat['total_refund'] = bcadd($stat['wechat_refund'] , $stat['alipay_refund'],2);
        $stat['total_balance'] = bcsub($stat['total_recharge'] , $stat['total_refund'],2);
        $stat['wechat_balance'] = bcsub($stat['wechat_recharge'] , $stat['wechat_refund'],2);
        $stat['alipay_balance'] = bcsub($stat['alipay_recharge'] , $stat['alipay_refund'],2);
        $user = $this->db->name('user')->field("sum(deposit) as deposit,sum(balance) as user_balance")->find();
        $stat['deposit'] = priceFormat($user['deposit']);
        $stat['user_balance'] = priceFormat($user['user_balance']);
        if (1 == $is_excel) {
            $title = array(
                lang('会员ID'),
                lang('用户名'),
                lang('订单号'),
                lang('交易单号'),
                lang('金额'),
                lang('退款后余额'),
                lang('支付方式'),
                lang('类型'),
                lang('时间'),
            );
            $filename = lang('资金流水记录').'-' . date('Y-m-d');
            $excel = new Office();

            //数据中对应的字段，用于读取相应数据：
            $keys = ['member_id', 'nick', 'order_no_text', 'trade_no_text', 'amount', 'balance', 'pay_type_text', 'type_text', 'create_time'];

            $excel->outdata($filename, $list, $title, $keys);
            return;
        }


        if ($isReturn) {
            return ['total' => $total, 'list' => $list, 'stat' => $stat];
        }

        $this->assign('title', lang('资金流水记录'));
        $this->assign('list', $list);
        $this->assign('paginate', $paginate);
        $this->assign('stat', $stat);
        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
    }
}