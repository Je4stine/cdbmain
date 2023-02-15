<?php

namespace app\operate\controller;

use think\Config;

class Index extends Common
{
    var $lang;
    /**
     * 控制台
     */
    // http://127.0.0.1/operate/Index/index
    public function index()
    {
        $groupids = \Godok\Org\Auth::user('groupids');
        $data = ['groupname' => lang('游客')];
        if (!empty($groupids)) {
            // var_dump($groupids);die;
            $group = $this->db->name(Config::get('auth.table_group'))->where(['id' => $groupids[0]])->find();
            if ($group) {
                $data['groupname'] = $group['title'];
            }
        }
        $data['menu'] = $this->menu2Html(\Godok\Org\Auth::menu("ismenu=1 AND status=1"));
        $config = $this->getOperatorConfig('basic_info');
        $config = json_decode($config, true);
        $data['site_name'] = $config['name'];
        return $this->fetch('index', $data);
    }

    /**
     * 格式化菜单
     */
    private function menu2Html($list, $level = 0)
    {
        $menu = '';
        $menulevel = ['first', 'second', 'third'];
        if (is_array($list)) {
            if ($level > 2) {
                $level = 2;
            }
            $menu .= $level > 0 ? '<ul class="nav nav-' . $menulevel[$level] . '-level">' : '';

            foreach ($list as $li) {
                $menu .= '<li>';
                if (empty($li['module']) || empty($li['controller']) || '*' == $li['module'] || '*' == $li['controller']) {
                    $menu .= '<a href="javascript:void(0)" >';
                } else {
                    $act = explode(',', $li['action']);
                    $href = \think\Request::instance()->root() . '/' . $li['module'] . '/' . $li['controller'] . '/' . $act[0];
                    if (!empty($li['condition'])) {
                        $href .= '?' . $li['condition'];
                    }
                    $menu .= '<a href="' . $href . '" class="J_menuItem">';
                }

                if (!empty($li['icon'])) {
                    $menu .= '<i class="' . $li['icon'] . '"></i> ';
                }
                $menu .= '<span class="nav-label">' . $li['title'] . '</span>';
                if (isset($li['children'])) {
                    $children = $this->menu2Html($li['children'], $level + 1);
                    $menu .= ' <span class="fa arrow"></span>';
                } else {
                    $children = '';
                }
                $menu .= '</a>' . $children;
                $menu .= '</li>';
            }

            $menu .= $level > 0 ? '</ul>' : '';
            return $menu;
        } else {
            return '';
        }
    }

    public function stat()
    {
        $order_table = getTableNo('lease_order', 'date', date("Ym"));
        $brokerage_table = getTableNo('order_brokerage', 'date', date("Ym"));
        $brokerage_table2 = getTableNo('order_brokerage', 'date', date("Ym", time() - 86400 * 4));
        $stat = [
            'total_order_num' => $this->db->name($order_table)->count(),
            'complete_num' => $this->db->name($order_table)->where(['status' => 2, 'is_pay' => 1])->count(),
            'overdue_num' => $this->db->name('order_active')->where(['status' => 1, 'expire_time' => ['<', time()]])->count(),
            'not_pay_num' => $this->db->name('order_active')->where(['status' => 2, 'is_pay' => 0])->count(),
            'total_order_amount' => (float)$this->db->name($order_table)->sum('amount'),
            'pay_order_amount' => (float)$this->db->name($order_table)->where(['is_pay' => 1])->sum('amount'),
            'brokerage_amount' => (float)$this->db->name($brokerage_table)->where(['status' => ['IN', [2, 9]]])->sum('amount'),
            'audit_amount' => (float)$this->db->name($brokerage_table)->where(['status' => 1])->sum('amount'),
            'device_num' => $this->db->name('charecabinet')->where(['not_delete' => 1])->count(),
            'online_num' => $this->db->name('charecabinet')->where(['not_delete' => 1, 'is_online' => 1, 'heart_time' => ['>', time() - config('online_time')]])->count(),
        ];
        if ($brokerage_table2 != $brokerage_table) {
            $audit_amount = (float)$this->db->name($brokerage_table2)->where(['status' => 1])->sum('amount');
            $stat['audit_amount'] = bcadd($stat['audit_amount'], $audit_amount, 2);
        }
        return $this->successResponse($stat, '数据统计');
    }

    public function seller()
    {
        $this->lang = "'$.{$this->lang}'";
        $month = getTimeStamp('month');
        $data = $this->db->name('stat_seller')
            ->alias('a')
            ->where(['a.sid' => ['>', 0], 'a.date' => ['between', [date("Y-m-d", $month['start']), date("Y-m-d", $month['end'])]]])
            ->join("seller b", 'a.sid = b.id', 'left')
            ->field("sum(a.total_amount) as amount,JSON_UNQUOTE(b.name->$this->lang) name,b.area,a.sid")
            ->group("a.sid")
            ->order('amount desc')
            ->limit(10)
            ->select();
        return $this->successResponse($data, '商户top10');
    }


    public function user()
    {
        $data = $this->db->name('order_active')
            ->alias('a')
            ->join("user b", 'a.uid = b.id')
            ->field('a.id,a.type,a.start_time,a.amount,a.status,a.expire_time,b.nickCode as nick')
            ->order('a.start_time desc')
            ->where(['status' => ['<', 3]])
            ->limit(10)
            ->select();
        foreach ($data as $k => $v) {
            $v['nick'] = $this->getCustomerNick($v['nick']);
            if ($v['expire_time'] < time()) {
                $v['status'] = ($v['type'] == 1) ? 4 : 2;
            }
            $v['start_time'] = date("Y-m-d H:i:s", $v['start_time']);
            unset($v['expire_time'], $v['nickCode']);
            $data[$k] = $v;
        }
        return $this->successResponse($data, '最新订单');
    }


    /**
     * 首页
     */
    public function home()
    {
        $month = getTimeStamp('month');


        //总租金额
        $sumRent = $this->db->name('stat_operator_day')->where(['date' => ['>', date("Y-m-d", $month['start'] - 86400)]])->sum('amount');
        //总商户数
        $sumSeller = $this->db->name('seller')->where(['not_delete' => 1])->count();
        //总用户数
        $sumUser = $this->db->name('user')->count();

        //本周总租金额
        $time = getTimeStamp('week');
        $weekRent = \think\Loader::model('Stat', 'logic')->operatorDay(date("Y-m-d", $time['start']), date("Y-m-d", $time['end']));

        $weekRentArr = [];
        foreach ($weekRent['data'] as $k => $v) {
            $weekRentArr[] = [
                'unit' => $v['date'],
                'amount' => $v['amount'],
            ];
        }

        //本月有效订单数
        $monthSumOrder = $this->db->name('stat_operator_day')->where(['date' => ['>', date("Y-m-d", $month['start'] - 86400)]])->sum('order_num');

        //用户状态列表
        $u = 'user';//定义用户表
        $userStatus = $this->db->name('order_active')
            ->alias('a')
            ->join("$u b", 'a.uid = b.id')
            ->field('a.id,a.type,a.start_time,a.order_no,a.amount,a.status,a.create_time,a.expire_time,b.nickCode as nick')
            ->order('a.id desc')
            ->where(['status' => ['<', 3]])
            ->limit(10)
            ->select();
        foreach ($userStatus as $k => $v) {
            $userStatus[$k]['nick'] = $this->getCustomerNick($v['nick']);
            if ($v['status'] != 1) {
                continue;
            }
            if ($v['expire_time'] < time()) {
                $userStatus[$k]['status'] = ($v['type'] == 1) ? 4 : 2;
            }
        }

        //商户营业额
        $sellerTurnover = $this->db->name('stat_seller_day')
            ->alias('a')
            ->where(['date' => ['between', [date("Y-m-d", $month['start']), date("Y-m-d", $month['end'])]]])
            ->join("seller b", 'a.sid = b.id', 'left')
            ->field("sum(a.amount) as amount,b.name,b.area,a.sid")
            ->group("a.sid")
            ->order('amount desc')
            ->limit(10)
            ->select();

        //整合成数组
        $data = array(
            'sumRent' => priceFormat($sumRent),
            'sumSeller' => $sumSeller,
            'sumUser' => $sumUser,
            'monthSumOrder' => $monthSumOrder,
        );

        $this->assign('v', $data);
        $this->assign('wrant', $weekRentArr);
        $this->assign('userRecent', $userStatus);
        $this->assign('sellerTurnover', $sellerTurnover);

        return $this->fetch();
    }

    public function test()
    {

    }

    //设置语言
    public function setlang()
    {
        setlang();
        $data = $this->db->name('operator_users')
            ->where(['id' => $this->auth_info['id']])
            ->find();
        $menu = \think\Loader::model('Auth', 'logic')->getMemus($data['auth_groups']);
        $equipment = config('equipment');
        $equipment = array_values($equipment);
        foreach ($equipment as $key => $value)
        {
            $equipment[$key]['name'] = lang($value['name']);
        }
        return $this->successResponse(['menu' => $menu, 'equipment' => $equipment], '设置语言');
    }
}
