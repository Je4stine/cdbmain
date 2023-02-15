<?php

namespace app\common\logic;


/**
 * 统计
 * @package app\common\logic
 */
class Stat extends Common
{

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 获取时间段内收入
     * @param $start
     * @param $end
     * @param $uid
     * @param $user_type
     * @return string
     */
    public function getIncome($start, $end, $uid, $user_role)
    {

        if ('seller' == $user_role) {//店铺管理显示到手分成
            $amount = $this->db->name('stat_agency')
                ->where(['agency_id' => $uid, 'date' => ['between', [date("Y-m-d", $start), date("Y-m-d", $end)]]])
                ->sum("brokerage_settle");
        } else {
            $start_table = date("Ym", $start);
            $end_table = date("Ym", $end);
            $where = ['create_time' => ['between', [$start, $end]],
                'status' => ['<>', 3],
                'relation_id' => $uid,
            ];

            $amount = $this->db->name('order_brokerage_' . $start_table)->where($where)->sum("amount");
            if ($start_table != $end_table) {
                $amount2 = $this->db->name('order_brokerage_' . $end_table)->where($where)->sum("amount");
                $amount = bcadd($amount, $amount2, 2);
            }
        }
        return number_format($amount, 2, ".", "");
    }


    //代理统计数据

    function dateFormat($date = '', $format = true)
    {
        $date = trim($date);
        if (!preg_match("/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/", $date)) {
            return '';
        }
        if (!$format) {
            return $date;
        }
        return strtotime($date);
    }


    //代理统计数据
    public function statAgency($ids, $start = '', $end = '', $top = 20)
    {
        $start = $this->dateFormat($start, false);
        $end = $this->dateFormat($end, false);

        $where = ['agency_id' => ['IN', $ids]];
        if (!empty($start) && !empty($end)) {
            $where['date'] = ['between', [$start, $end]];
        } else if (!empty($start)) {
            $where['date'] = ['>=', $start];
        } else if (!empty($end)) {
            $where['date'] = ['<', $end];
        }
        $list = $this->db->name('stat_agency')
            ->where($where)
            ->field("sum(total_amount) as total_amount,sum(total_refund) as total_refund,sum(total_pay_amount) as total_pay_amount,sum(total_pay_refund) as total_pay_refund,agency_id,sum(total_num) as total_num")
            ->group('agency_id')
            ->order('total_amount desc')
            ->limit(0, $top)
            ->select();

        $list = array_column($list, NULL, 'agency_id');
        return $list;
    }


    //代理店铺统计数据
    public function statAgencySeller($uid, $start = '', $end = '', $keyword = '', $top = 100)
    {
        $start = $this->dateFormat($start, false);
        $end = $this->dateFormat($end, false);

        $sub_sql = "SELECT a.sid,sum(a.order_amount) as total_amount,
                   sum(a.refund_amount) as total_refund,
                   sum(a.pay_amount) as total_pay_amount,
                   sum(a.refund_pay_amount) as total_pay_refund,
                   sum(a.order_num) as total_num 
                    FROM stat_agency_seller  as a  ";


        $where_join = " WHERE  a.`relation_id` = '{$uid}' ";
        $where = " WHERE  s.agency_id = '{$uid}' ";
        if ('' != $keyword) {
            $where .= " AND sh.name LIKE '%{$keyword}%'";
            $where_join .= " AND sh.name LIKE '%{$keyword}%'";
            $sub_sql .= " LEFT JOIN `seller` `sh` ON `a`.`sid`=`sh`.`id` ";
            $total = $this->db->name('seller_agency')->alias('a')->join("seller s", 'a.sid = s.id', 'LEFT')->where(['a.agency_id' => $uid, 's.name' => ['LIKE', "%{$keyword}%"]])->count();
        } else {
            $total = $this->db->name('seller_agency')->where(['agency_id' => $uid])->count();
        }


        if (!empty($start) && !empty($end)) {
            $where_join .= " AND a.date BETWEEN '{$start}' AND '{$end}'";
        } else if (!empty($start)) {
            $where_join .= " a.date >= '{$start}'";
        } else if (!empty($end)) {
            $where_join .= " a.date >= '{$end}'";
        }
        $sub_sql = $sub_sql . $where_join . " GROUP BY a.sid ORDER BY total_amount desc LIMIT 0,{$top} ";


        $sql = " SELECT distinct s.sid,s.agency_id,b.total_amount,b.total_refund,b.total_pay_amount,b.total_pay_refund,b.total_num 
                FROM `seller_agency` `s`
                LEFT JOIN ({$sub_sql}) b ON `s`.`sid`=`b`.`sid` ";
        if ('' != $keyword) {
            $sql .= " LEFT JOIN `seller` `sh` ON `s`.`sid`=`sh`.`id` ";
        }
        $sql = $sql . $where . " order by b.total_amount desc LIMIT 0,{$top}";

        $data = $this->db->query($sql);
        foreach($data  as $k=>$v){
            $v['total_amount'] = priceFormat($v['total_amount']);
            $v['total_refund'] = priceFormat($v['total_refund']);
            $v['total_pay_amount'] = priceFormat($v['total_pay_amount']);
            $v['total_pay_refund'] = priceFormat($v['total_pay_refund']);
            $v['total_num'] = priceFormat($v['total_num']);
            $data[$k] = $v;
        }

        $sids = array_column($data, 'sid');
        $seller = $this->db->name('seller')
            ->field('id,name')
            ->where(['id' => ['IN', $sids]])
            ->select();
        $names = array_column($seller, 'name', 'id');


        $where1 = ['a.agency_id' => $uid];
        ('' != $keyword) && $where1['s.name'] = ['LIKE', "%{$keyword}%"];
        $seller = $this->db->name('seller_agency')
            ->alias('a')
            ->join("seller s", 'a.sid = s.id', 'LEFT')
            ->field('s.id,s.name')
            ->where($where1)
            ->limit(0, $top)
            ->select();


        $seller = array_column($seller, 'name', 'id');
        $names = $names + $seller;
        foreach ($data as $k => $v) {
            $v['name'] = $names[$v['sid']];
            $data[$k] = $v;
            unset($names[$v['sid']]);
        }
        $data = array_values($data);

        if (count($data) < $top && $total > 0) {
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
                    'sid' => $k,
                    'name' => $v,
                ];
            }
        }
        return $data;
    }


    //管理员店铺统计数据
    public function statManagerSeller($uid, $role = 'seller', $start = '', $end = '', $keyword = '', $top = 100)
    {
        $start = $this->dateFormat($start, false);
        $end = $this->dateFormat($end, false);

        $where = ['a.relation_id' => $uid, 's.not_delete' => 1];
        if ($role == 'seller') {
            $where['s.manager_id'] = $uid;
            $total = $this->db->name('seller')->where(['manager_id' => $uid])->count();
        } else {
            $where['s.employee_id'] = $uid;
            $total = $this->db->name('seller')->where(['employee_id' => $uid])->count();
        }
        if (!empty($start) && !empty($end)) {
            $where['a.date'] = ['between', [$start, $end]];
        } else if (!empty($start)) {
            $where['a.date'] = ['>=', $start];
        } else if (!empty($end)) {
            $where['a.date'] = ['<', $end];
        }
        if ('' != $keyword) {
            $where['s.name'] = ['LIKE', "%{$keyword}%"];
        }

        $data = $this->db->name('stat_agency_seller')
            ->alias('a')
            ->join("seller s", 'a.sid = s.id', 'LEFT')
            ->where($where)
            ->field("sum(a.order_amount) as total_amount,sum(a.refund_amount) as total_refund,sum(a.pay_amount) as total_pay_amount,sum(a.refund_pay_amount) as total_pay_refund,a.sid,sum(a.order_num) as total_num")
            ->group('a.sid')
            ->order('total_amount desc')
            ->limit(0, $top)
            ->select();


        $sids = array_column($data, 'sid');
        $seller = $this->db->name('seller')
            ->field('id,name')
            ->where(['id' => ['IN', $sids]])
            ->select();
        $names = array_column($seller, 'name', 'id');


        if ($role == 'seller') {
            $where1 = ['manager_id' => $uid, 'not_delete' => 1];
        } else {
            $where1 = ['employee_id' => $uid, 'not_delete' => 1];
        }
        if ('' != $keyword) {
            $where1['name'] = ['LIKE', "%{$keyword}%"];
        }
        $seller = $this->db->name('seller')
            ->field('id,name')
            ->where($where1)
            ->limit(0, $top)
            ->select();
        $seller = array_column($seller, 'name', 'id');

        $names = $names + $seller;
        foreach ($data as $k => $v) {
            $v['name'] = $names[$v['sid']];
            $data[$k] = $v;
            unset($names[$v['sid']]);
        }
        $data = array_values($data);

        if (count($data) < $top && $total > 0) {
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
                    'sid' => $k,
                    'name' => $v,
                ];
            }
        }

        return $data;
    }


    //代理设备统计数据
    public function statAgencyDevice($uid, $start = '', $end = '', $keyword = '', $top = 100)
    {
        $start = $this->dateFormat($start, false);
        $end = $this->dateFormat($end, false);

        $where = ['a.relation_id' => $uid, 's.agency_id' => $uid, 's.type' => 1];
        if (!empty($start) && !empty($end)) {
            $where['a.date'] = ['between', [$start, $end]];
        } else if (!empty($start)) {
            $where['a.date'] = ['>=', $start];
        } else if (!empty($end)) {
            $where['a.date'] = ['<', $end];
        }
        $where1 = ['agency_id' => $uid, 'type' => 1];
        if ('' != $keyword) {
            $where['s.device_code'] = ['LIKE', "%{$keyword}%"];
            $where1['device_code'] = ['LIKE', "%{$keyword}%"];
        }
        $data = [];
        $results = $this->db->name('device_agency')
            ->alias('s')
            ->join("stat_agency_device a", 'a.device_id = s.device_code', 'LEFT')
            ->where($where)
            ->field("sum(a.order_amount) as total_amount,sum(a.refund_amount) as total_refund,sum(a.pay_amount) as total_pay_amount,sum(a.refund_pay_amount) as total_pay_refund,a.device_id,sum(a.order_num) as total_num")
            ->group('a.device_id')
            ->order('total_amount desc')
            ->limit(0, $top)
            ->select();

        $device_ids = array_column($results, 'device_id');
        $device = $this->db->name('charecabinet')
            ->field('sid,cabinet_id as device_id,model')
            ->where(['cabinet_id' => ['IN', $device_ids]])
            ->select();
        $device = array_column($device, NULL, 'device_id');
        foreach ($results as $k => $v) {
            $v['sid'] = $device[$v['device_id']]['sid'];
            $v['model'] = $device[$v['device_id']]['model'];
            $data[$v['device_id']] = $v;
        }

        $total = count($data);//已有统计数据
        $count = $total;
        if ($total < $top) {//数据不足需补充
            $device_ids = $this->db->name('device_agency')->where($where1)->field('device_code')->limit(0, $top)->column('device_code');
            $top_device = $this->db->name('charecabinet')
                ->field('sid,cabinet_id as device_id,model')
                ->where(['cabinet_id' => ['IN', $device_ids]])
                ->select();
            $count = count($top_device);
        }

        if ($count > $total) {
            for ($i = 0; $i <= $top; $i++) {
                if ($total >= $top || !isset($top_device[$i])) {
                    break;
                }
                $val = $top_device[$i];
                if (isset($data[$val['device_id']])) {
                    continue;
                }

                $data[$val['device_id']] = [
                    'total_amount' => 0,
                    'total_refund' => 0,
                    'total_pay_amount' => 0,
                    'total_pay_refund' => 0,
                    'total_num' => 0,
                    'device_id' => $val['device_id'],
                    'sid' => intval($val['sid']),
                    'model' => $val['model'],
                ];
            }
        }


        $sids = array_column($data, 'sid');
        $seller = $this->db->name('seller')
            ->field('id,name')
            ->where(['id' => ['IN', $sids]])
            ->select();
        $seller = array_column($seller, 'name', 'id');
        $equipment = config('equipment');

        foreach ($data as $k => $v) {
            $v['model'] = $equipment[$v['model']]['name'];
            $v['sname'] = isset($seller[$v['sid']])? $seller[$v['sid']] :'';
            $data[$k] = $v;
        }
        $data = array_values($data);
        return $data;
    }


    //管理员设备统计数据
    public function statManagerDevice($uid, $start = '', $end = '', $keyword = '', $top = 100)
    {
        $start = $this->dateFormat($start, false);
        $end = $this->dateFormat($end, false);

        $where = ['a.relation_id' => $uid, 's.not_delete' => 1];

        if (!empty($start) && !empty($end)) {
            $where['a.date'] = ['between', [$start, $end]];
        } else if (!empty($start)) {
            $where['a.date'] = ['>=', $start];
        } else if (!empty($end)) {
            $where['a.date'] = ['<', $end];
        }

        $sids = $this->db->name('seller')->field('id')->where(['manager_id' => $uid, 'not_delete' => 1])->column('id');
        empty($sids) && $sids = [-1];
        $where['s.sid'] = ['IN', $sids];
        $where1 = ['sid' => ['IN', $sids], 'not_delete' => 1];
        if ('' != $keyword) {
            $where['s.cabinet_id'] = ['LIKE', "%{$keyword}%"];
            $where1['cabinet_id'] = ['LIKE', "%{$keyword}%"];
        }
        $data = [];
        $results = $this->db->name('stat_agency_device')
            ->alias('a')
            ->join("charecabinet s", 'a.device_id = s.cabinet_id', 'LEFT')
            ->where($where)
            ->field("sum(a.order_amount) as total_amount,sum(a.refund_amount) as total_refund,sum(a.pay_amount) as total_pay_amount,sum(a.refund_pay_amount) as total_pay_refund,a.device_id,sum(a.order_num) as total_num")
            ->group('a.device_id')
            ->order('total_amount desc')
            ->limit(0, $top)
            ->select();
        $device_ids = array_column($results, 'device_id');
        $device = $this->db->name('charecabinet')
            ->field('sid,cabinet_id as device_id,model')
            ->where(['cabinet_id' => ['IN', $device_ids]])
            ->select();
        $device = array_column($device, NULL, 'device_id');
        foreach ($results as $k => $v) {
            $v['sid'] = $device[$v['device_id']]['sid'];
            $v['model'] = $device[$v['device_id']]['model'];
            $data[$v['device_id']] = $v;
        }

        $total = count($data);//已有统计数据
        $count = $total;
        if ($total < $top) {//数据不足需补充
            $top_device = $this->db->name('charecabinet')
                ->field('sid,cabinet_id as device_id,model')
                ->where($where1)
                ->select();
            $count = count($top_device);
        }

        if ($count > $total) {
            for ($i = 0; $i <= $top; $i++) {
                if ($total >= $top || !isset($top_device[$i])) {
                    break;
                }
                $val = $top_device[$i];
                if (isset($data[$val['device_id']])) {
                    continue;
                }

                $data[$val['device_id']] = [
                    'total_amount' => 0,
                    'total_refund' => 0,
                    'total_pay_amount' => 0,
                    'total_pay_refund' => 0,
                    'total_num' => 0,
                    'device_id' => $val['device_id'],
                    'sid' => intval($val['sid']),
                    'model' => $val['model'],
                ];
            }
        }


        $sids = array_column($data, 'sid');
        $seller = $this->db->name('seller')
            ->field('id,name')
            ->where(['id' => ['IN', $sids]])
            ->select();
        $seller = array_column($seller, 'name', 'id');
        $equipment = config('equipment');

        foreach ($data as $k => $v) {
            $v['model'] = $equipment[$v['model']]['name'];
            $v['sname'] = isset($seller[$v['sid']])? $seller[$v['sid']] :'';
            $data[$k] = $v;
        }
        $data = array_values($data);
        return $data;
    }

    //业务员设备数据统计

    public function statEmployeeDevice($uid, $start = '', $end = '', $keyword = '', $top = 100)
    {
        $start = $this->dateFormat($start, false);
        $end = $this->dateFormat($end, false);

        $where = ['a.relation_id' => $uid, 's.not_delete' => 1];
        $where['s.employee_id'] = $uid;

        if (!empty($start) && !empty($end)) {
            $where['a.date'] = ['between', [$start, $end]];
        } else if (!empty($start)) {
            $where['a.date'] = ['>=', $start];
        } else if (!empty($end)) {
            $where['a.date'] = ['<', $end];
        }
        $where1 = ['employee_id' => $uid, 'not_delete' => 1];
        if ('' != $keyword) {
            $where['s.cabinet_id'] = ['LIKE', "%{$keyword}%"];
            $where1['cabinet_id'] = ['LIKE', "%{$keyword}%"];
        }
        $data = [];
        $results = $this->db->name('stat_agency_device')
            ->alias('a')
            ->join("charecabinet s", 'a.device_id = s.cabinet_id', 'LEFT')
            ->where($where)
            ->field("sum(a.order_amount) as total_amount,sum(a.refund_amount) as total_refund,sum(a.pay_amount) as total_pay_amount,sum(a.refund_pay_amount) as total_pay_refund,a.device_id, sum(a.order_num) as total_num")
            ->group('a.device_id')
            ->order('total_amount desc')
            ->limit(0, $top)
            ->select();

        $device_ids = array_column($results, 'device_id');
        $device = $this->db->name('charecabinet')
            ->field('sid,cabinet_id as device_id,model')
            ->where(['cabinet_id' => ['IN', $device_ids]])
            ->select();
        $device = array_column($device, NULL, 'device_id');
        foreach ($results as $k => $v) {
            $v['sid'] = $device[$v['device_id']]['sid'];
            $v['model'] = $device[$v['device_id']]['model'];
            $data[$v['device_id']] = $v;
        }

        $total = count($data);//已有统计数据
        $count = $total;

        if ($total < $top) {//数据不足需补充
            $top_device = $this->db->name('charecabinet')
                ->field('sid,cabinet_id as device_id,model')
                ->where($where1)
                ->select();
            $count = count($top_device);
        }

        if ($count > $total) {
            for ($i = 0; $i <= $top; $i++) {
                if ($total >= $top || !isset($top_device[$i])) {
                    break;
                }
                $val = $top_device[$i];
                if (isset($data[$val['device_id']])) {
                    continue;
                }

                $data[$val['device_id']] = [
                    'total_amount' => 0,
                    'total_refund' => 0,
                    'total_pay_amount' => 0,
                    'total_pay_refund' => 0,
                    'total_num' => 0,
                    'device_id' => $val['device_id'],
                    'sid' => intval($val['sid']),
                    'model' => $val['model'],
                ];
            }
        }


        $sids = array_column($data, 'sid');
        $seller = $this->db->name('seller')
            ->field('id,name')
            ->where(['id' => ['IN', $sids]])
            ->select();
        $seller = array_column($seller, 'name', 'id');
        $equipment = config('equipment');

        foreach ($data as $k => $v) {
            $v['model'] = $equipment[$v['model']]['name'];
            $v['sname'] = isset($seller[$v['sid']])? $seller[$v['sid']] :'';
            $data[$k] = $v;
        }
        $data = array_values($data);
        return $data;
    }

    //下级代理统计数据

    public function subAgencyLease($ids, $start = '', $end = '')
    {
        $start = $this->dateFormat($start);
        $end = $this->dateFormat($end);
        !empty($end) && $end = $end + 86399;
        $where = ['agency_id' => ['IN', $ids], 'status' => ['IN', [1, 2, 4]]];
        if (!empty($start) && !empty($end)) {
            $where['create_time'] = ['between', [$start, $end]];
        } else if (!empty($start)) {
            $where['create_time'] = ['>', $start - 1];
        } else if (!empty($end)) {
            $where['create_time'] = ['<', $end + 1];
        }
        $order_num = $this->db->name('lease_order')
            ->where($where)
            ->count();

        $amount = $this->db->name('lease_order')
            ->where($where)
            ->sum('amount');

        return ['order_num' => $order_num, 'amount' => $amount];
    }


    function deviceTrend($device_id)
    {
        $data = [];
        $range = ['today', 'yesterday', 'week', 'lastWeek', 'month', 'lastMonth', 'total'];
        $fields = ['amount', 'num', 'pay_amount', 'pay_refund'];

        foreach ($range as $val) {
            foreach ($fields as $field) {
                $data[$field][$val] = 0;
            }
            if ($val == 'total') {
                continue;
            }
            $day = getTimeStamp($val);
            $start = date("Y-m-d", $day['start']);
            $end = date("Y-m-d", $day['end']);
            $query = $this->db->name('stat_device')
                ->where(['device_id' => $device_id, 'date' => ['between', [$start, $end]]])
                ->select();

            foreach ($query as $v) {
                $data['amount'][$val] = bcadd($data['amount'][$val], $v['amount'], 2);
                $data['pay_amount'][$val] = bcadd($data['pay_amount'][$val], $v['pay_amount'], 2);
                $data['pay_refund'][$val] = bcadd($data['pay_refund'][$val], $v['pay_refund'], 2);
                $data['num'][$val] += $v['create_num'];
            }
        }
        $query = $this->db->name('stat_device')
            ->where(['device_id' => $device_id])
            ->select();
        foreach ($query as $v) {
            $data['amount']['total'] = bcadd($data['amount']['total'], $v['amount'], 2);
            $data['pay_amount']['total'] = bcadd($data['pay_amount']['total'], $v['pay_amount'], 2);
            $data['pay_refund']['total'] = bcadd($data['pay_refund']['total'], $v['pay_refund'], 2);
            $data['num']['total'] += $v['create_num'];
        }
        return $data;
    }

    //店铺统计
    function sellerTrend($id)
    {
        $data = [];
        $range = ['today', 'yesterday', 'week', 'lastWeek', 'month', 'lastMonth', 'total'];
        $fields = ['amount', 'num', 'pay_amount', 'pay_refund'];


        foreach ($range as $val) {
            foreach ($fields as $field) {
                $data[$field][$val] = 0;
            }
            if ($val == 'total') {
                continue;
            }

            $day = getTimeStamp($val);
            $start = date("Y-m-d", $day['start']);
            $end = date("Y-m-d", $day['end']);
            $query = $this->db->name('stat_seller')
                ->where(['sid' => $id, 'date' => ['between', [$start, $end]]])
                ->select();

            foreach ($query as $v) {
                $data['amount'][$val] = bcadd($data['amount'][$val], $v['total_amount'], 2);
                $data['pay_amount'][$val] = bcadd($data['pay_amount'][$val], $v['total_pay_amount'], 2);
                $data['pay_refund'][$val] = bcadd($data['pay_refund'][$val], $v['total_pay_refund'], 2);
                $data['num'][$val] += $v['total_num'];
            }

        }
        $query = $this->db->name('stat_seller')
            ->where(['sid' => $id])
            ->select();
        foreach ($query as $v) {
            $data['amount']['total'] = bcadd($data['amount']['total'], $v['total_amount'], 2);
            $data['pay_amount']['total'] = bcadd($data['pay_amount']['total'], $v['total_pay_amount'], 2);
            $data['pay_refund']['total'] = bcadd($data['pay_refund']['total'], $v['total_pay_refund'], 2);
            $data['num']['total'] += $v['total_num'];
        }
        return $data;
    }

    function addUser($start, $end)
    {
        $query = $this->db->name('user')
            ->where(['create_time' => ['between', [$start, $end]]])
            ->field("count(*) as num,app_type")
            ->group('app_type')
            ->select();
        $query = array_column($query, 'num', 'app_type');
        $total = array_sum($query);
        return ['add_user' => $total, 'add_user_data' => json_encode($query)];
    }

    function activeUser($start, $end)
    {
        $query = $this->db->name('user')
            ->where(['last_order' => ['between', [$start, $end]]])
            ->field("count(*) as num,app_type")
            ->group('app_type')
            ->select();
        $query = array_column($query, 'num', 'app_type');
        $total = array_sum($query);
        return ['active_user' => $total, 'active_user_data' => json_encode($query)];
    }


    //运营统计
    public function operatorDay($start = '', $end = '')
    {
        strtotime($end) > time() && $end = date("Y-m-d");
        $format = "j";
        if (date("Ym", strtotime($start)) != date("Ym", strtotime($end))) {
            $format = "n/j";
        }


        $query = $this->db->name('stat_operator')
            ->where(['date' => ['between', [$start, $end]]])
            ->select();
        $query = array_column($query, NULL, 'date');
        $data = ['day' => [], 'total' => []];


        $feilds = [
            'battery_amount',
            'battery_pay_amount',
            'battery_num',
            'battery_pay_num',
            'battery_refund',
            'battery_pay_refund',
            'wired_amount',
            'wired_num',
            'wired_refund',
            'total_amount',
            'total_pay_amount',
            'total_num',
            'total_pay_num',
            'brokerage_amount',
            'brokerage_settle',
            'brokerage_cancel',
            'brokerage_pay_cancel',
        ];


        for ($dateline = strtotime($start); $dateline <= strtotime($end); $dateline = $dateline + 86400) {
            $date = date("Y-m-d", $dateline);
            foreach ($feilds as $feild) {
                !isset($data[$feild]) && $data[$feild] = [];
                !isset($data['total'][$feild . "_sum"]) && $data['total'][$feild . "_sum"] = 0;
                $data[$feild][] = !isset($query[$date]) ? 0 : $query[$date][$feild];
                if (isset($query[$date])) {
                    if (in_array($feild, ['battery_num', 'wired_num', 'total_num', 'total_pay_num', 'battery_pay_num'])) {
                        $data['total'][$feild . "_sum"] += $query[$date][$feild];
                    } else {
                        $data['total'][$feild . "_sum"] = bcadd($data['total'][$feild . "_sum"], $query[$date][$feild], 2);
                    }
                }
            }
            $data['day'][] = date($format, $dateline);;
        }

        return $data;
    }


    //运营统计
    function addSeller($start, $end)
    {
        return $this->db->name('seller')
            ->where([
                'create_time' => ['between', [$start, $end]],
                'not_delete' => 1
            ])
            ->count();
    }


    //代理统计
    public function agencyDay($id, $start = '', $end = '')
    {
        strtotime($end) > time() && $end = date("Y-m-d");
        $format = "j";
        if (date("Ym", strtotime($start)) != date("Ym", strtotime($end))) {
            $format = "n/j";
        }


        $query = $this->db->name('stat_agency')
            ->where(['agency_id' => $id, 'date' => ['between', [$start, $end]]])
            ->select();
        $query = array_column($query, NULL, 'date');
        $data = ['day' => [], 'total' => []];


        $feilds = [
            'battery_amount',
            'battery_pay_amount',
            'battery_num',
            'battery_pay_num',
            'battery_refund',
            'battery_pay_refund',
            'wired_amount',
            'wired_num',
            'wired_refund',
            'total_amount',
            'total_pay_amount',
            'total_num',
            'total_pay_num',
            'brokerage_amount',
            'brokerage_settle',
            'brokerage_cancel',
            'brokerage_pay_cancel',
        ];


        for ($dateline = strtotime($start); $dateline <= strtotime($end); $dateline = $dateline + 86400) {
            $date = date("Y-m-d", $dateline);
            foreach ($feilds as $feild) {
                !isset($data[$feild]) && $data[$feild] = [];
                !isset($data['total'][$feild . "_sum"]) && $data['total'][$feild . "_sum"] = 0;
                $data[$feild][] = !isset($query[$date]) ? 0 : $query[$date][$feild];
                if (isset($query[$date])) {
                    if (in_array($feild, ['battery_num', 'wired_num', 'total_num', 'total_pay_num'])) {
                        $data['total'][$feild . "_sum"] += $query[$date][$feild];
                    } else {
                        $data['total'][$feild . "_sum"] = bcadd($data['total'][$feild . "_sum"], $query[$date][$feild], 2);
                    }
                }
            }
            $data['day'][] = date($format, $dateline);;
        }

        return $data;
    }
}