<?php

namespace app\agency\controller;


//首页
class Index extends Common
{

    public function index()
    {
        $stat = \think\Loader::model('Stat', 'logic');
        $today = getTimeStamp('today');
        $yesterday = getTimeStamp('yesterday');
        $week = getTimeStamp('week');
        $uid = $this->auth_info['uid'];
        $account = $this->db->name('account')
            ->where(['user_type' => $this->auth_info['user_type'], 'relation_id' => $uid])
            ->find();

        $account['audit_amount'] = (float)$this->db->name("order_brokerage")
            ->where(['relation_id' => $this->auth_info['uid'], 'status' => 1])
            ->sum('amount');
        if($this->auth_info['role'] != 'seller'){//非店铺管理
            $account['total_amount'] = bcadd($account['total_amount'],$account['audit_amount'], 2);
        }

        $data = [
            'today_income' => $stat->getIncome($today['start'], $today['end'], $uid, $this->auth_info['role']),
            'yesterday_income' => $stat->getIncome($yesterday['start'], $yesterday['end'], $uid, $this->auth_info['role']),
            //'week_income' => $stat->getIncome($week['start'], $week['end'], $uid, $this->auth_info['user_type']),
            'total_income' => priceFormat($account['total_amount'], false),
            'lease_wechat' => intval($account['wechat_num']),
            'lease_alipay' => intval($account['alipay_num']),
            'device_total' => 0,
            'device_online' => 0,
            'device_offline' => 0,
            'device_unbind' => 0,
            'wired_total' => 0,
            'wired_unbind' => 0,
        ];
        $heart_time = time() - config('online_time');//心跳
        /*
                //设备数量
                if ('agency' == $this->auth_info['role']) {
                    $data['agency_num'] = $this->db->name('agency')->where(['type' => 1, 'not_delete' => 1, 'parent_id' => $uid])->count();
                    $data['employee_num'] = $this->db->name('agency')->where(['type' => 2, 'not_delete' => 1, 'parent_id' => $uid])->count();
                    $data['seller_num'] = $this->db->name('agency')->where(['type' => 3, 'not_delete' => 1, 'parent_id' => $uid])->count();
                    $ids = \think\Loader::model('Agency', 'logic')->subAgencyIds($uid);
                    $ids[] = $uid;
                    $data['device_total'] = $this->db->name('charecabinet')->where(['not_delete' => 1, 'agency_id' => ['IN', $ids]])->count();
                    if ($data['device_total'] > 0) {
                        $data['device_online'] = $this->db->name('charecabinet')->where(['not_delete' => 1, 'agency_id' => ['IN', $ids], 'is_online' => 1, 'heart_time' => ['>', $heart_time]])->count();
                        $data['device_offline'] = $data['device_total'] - $data['device_online'];
                        $data['device_unbind'] = $this->db->name('charecabinet')->where(['not_delete' => 1, 'agency_id' => ['IN', $ids], 'sid' => 0])->count();
                    }
                    $data['wired_total'] = $this->db->name('wired_device')->where(['not_delete' => 1, 'agency_id' => ['IN', $ids]])->count();
                    $data['wired_total'] && $data['wired_unbind'] = $this->db->name('wired_device')->where(['not_delete' => 1, 'agency_id' => ['IN', $ids], 'sid' => 0])->count();
                } else if ('employee' == $this->auth_info['role']) {
                    $data['seller_num'] = $this->db->name('agency')->where(['type' => 3, 'not_delete' => 1, 'employee_id' => $uid])->count();
                    $data['device_total'] = $this->db->name('charecabinet')->where(['not_delete' => 1, 'employee_id' => $uid])->count();
                    if ($data['device_total'] > 0) {
                        $data['device_online'] = $this->db->name('charecabinet')->where(['not_delete' => 1, 'employee_id' => $uid, 'is_online' => 1, 'heart_time' => ['>', $heart_time]])->count();
                        $data['device_offline'] = $data['device_total'] - $data['device_online'];
                        $data['device_unbind'] = $this->db->name('charecabinet')->where(['not_delete' => 1, 'employee_id' => $uid, 'sid' => 0])->count();
                    }
                    $data['wired_total'] = $this->db->name('wired_device')->where(['not_delete' => 1, 'employee_id' => $uid])->count();
                    $data['wired_total'] && $data['wired_unbind'] = $this->db->name('wired_device')->where(['not_delete' => 1, 'employee_id' => $uid, 'sid' => 0])->count();
                } else if ('seller' == $this->auth_info['role']) {
                    $seller_Ids = $this->db->name('seller')->where(['not_delete' => 1, 'manager_id' => $uid])->column('id');
                    $data['seller_num'] = 0;
                    if ($seller_Ids) {
                        $data['device_total'] = $this->db->name('charecabinet')->where(['not_delete' => 1, 'sid' => ['IN', $seller_Ids]])->count();
                        if ($data['device_total'] > 0) {
                            $data['device_online'] = $this->db->name('charecabinet')->where(['not_delete' => 1, 'sid' => ['IN', $seller_Ids], 'is_online' => 1, 'heart_time' => ['>', $heart_time]])->count();
                            $data['device_offline'] = $data['device_total'] - $data['device_online'];
                        }
                        $data['wired_total'] = $this->db->name('wired_device')->where(['not_delete' => 1, 'sid' => ['IN', $seller_Ids]])->count();
                    }
                }*/
        return $this->successResponse($data, '首页数据');
    }

    //商户统计
    public function statSeller()
    {
        $keyword = input('keyword', '', 'trim');
        $start = input('start', '', 'trim');
        $end = input('end', '', 'trim');
        $logic = \think\Loader::model('Stat', 'logic');
        $uid = $this->auth_info['uid'];
        $top = 100;
        if ('agency' == $this->auth_info['role']) {
            $data = $logic->statAgencySeller($uid, $start, $end, $keyword, $top);
        } else {
            //1766,764
            $data = $logic->statManagerSeller($uid, $this->auth_info['role'], $start, $end, $keyword, $top);
        }
        return $this->successResponse($data, '商户统计');
    }

    //设备统计
    public function statDevice()
    {
        $keyword = input('keyword', '', 'trim');
        $start = input('start', '', 'trim');
        $end = input('end', '', 'trim');
        $logic = \think\Loader::model('Stat', 'logic');
        $uid = $this->auth_info['uid'];
        $top = 100;
        if ('agency' == $this->auth_info['role']) {
            $data = $logic->statAgencyDevice($uid, $start, $end, $keyword, $top);
        } else if ('employee' == $this->auth_info['role']) {
            //764
            $data = $logic->statEmployeeDevice($uid, $start, $end, $keyword, $top);
        } else {
            //1766
            $data = $logic->statManagerDevice($uid, $start, $end, $keyword, $top);
        }
        return $this->successResponse($data, '设备统计');
    }

    //代理统计
    public function statBranch()
    {
        if ('agency' != $this->auth_info['role']) {
            return $this->successResponse(['total' => 0, 'list' => []], '代理统计');
        }

        $page_size = input('page_size', 10, 'intval');
        $page_size < 1 && $page_size = 10;
        $keyword = input('keyword', '', 'trim');
        $start = input('start', '', 'trim');
        $end = input('end', '', 'trim');
        $type = input('type', 'agency', 'trim');
        $type = ('employee' == $type) ? 2 : 1;

        $uid = $this->auth_info['uid'];
        $where = ['not_delete' => 1, 'parent_id' => $uid, 'type' => $type];
        if ('' != $keyword) {
            $where['name'] = ['LIKE', "%{$keyword}%"];
        }
        $top = 100;
        $agency = $this->db->name('agency')
            ->field('id,name')
            ->where($where)
            ->select();
        $ids = array_column($agency, 'id');
        $names = array_column($agency, 'name', 'id');
        $data = \think\Loader::model('Stat', 'logic')->statAgency($ids, $start, $end, $top);
        foreach ($data as $k => $v) {
            $v['name'] = $names[$k];
            $v['avatar'] = config('seller_img.avatar');
            $data[$k] = $v;
            unset($names[$k]);
        }
        $data = array_values($data);
        if (count($data) < $top && count($names) > 0) {
            $num = $top - count($data);
            $names = array_values($names);
            $names = array_slice($names, 0, $num);
            foreach ($names as $k => $v) {
                $data[] = [
                    'total_amount' => 0,
                    'total_refund' => 0,
                    'total_pay_amount' => 0,
                    'total_pay_refund' => 0,
                    'total_num' => 0,
                    'agency_id' => $k,
                    'name' => $v,
                    'avatar' => config('seller_img.avatar'),
                ];
            }
        }
        return $this->successResponse($data, '代理统计');
    }


    function trend()
    {
        $start = strtotime("-1 month");
        $end = strtotime(date("Y-m-d"));
        for ($dateline = $start; $dateline < $end; $dateline = $dateline + 86400) {
            $times[] = $dateline;
        }
        $where = [];
        $where['create_time'] = ['>=', $start];
        $where['create_time'] = ['<', $end];
        if ('agency' == $this->auth_info['role']) {
            $where['agency_id'] = $this->auth_info['uid'];
        } else if ('employee' == $this->auth_info['role']) {
            $where['employee_id'] = $this->auth_info['uid'];
        } else if ('seller' == $this->auth_info['role']) {
            $seller_Ids = $this->db->name('seller')->where(['not_delete' => 1, 'manager_id' => $this->auth_info['uid']])->column('id');
            empty($seller_Ids) && $seller_Ids = ['-1'];
            $where['sid'] = ['IN', $seller_Ids];
        }

        $day = "'%m-%d'";
        $query = $this->db->name('lease_order')
            ->where($where)
            ->field("FROM_UNIXTIME(create_time,$day) as unit,count(*) as num")
            ->group("unit")
            ->select();
        $query = array_column($query, 'num', 'unit');


        $query2 = $this->db->name('lease_order')
            ->where($where)
            ->where('status < 3 AND is_pay = 1')
            ->field("FROM_UNIXTIME(payment_time,$day) as unit,SUM(amount) as amount")
            ->group("unit")
            ->select();
        $query2 = array_column($query2, 'amount', 'unit');


        foreach ($times as $k => $dateline) {
            $day = date("m-d", $dateline);
            $date[] = date("m.d", $dateline);
            $data[] = isset($query[$day]) ? $query[$day] : 0;
            $data2[] = isset($query2[$day]) ? $query2[$day] : 0;
        }
        $max = max($data);
        max($data2) > $max && $max = max($data2);
        $result = [
            'max' => $max + 10,
            'categories' => $date,
            'series' => [
                ['name' => '租借次数', 'data' => $data],
                ['name' => '佣金提成', 'data' => $data2]
            ]
        ];
        return $this->successResponse($result, '近一月走势');
    }

    //设置语言
    public function setlang()
    {
        setlang();
        return $this->successResponse(['user'=>$this->auth_info], '设置语言');
    }
}




