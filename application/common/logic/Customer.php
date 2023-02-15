<?php

namespace app\common\logic;

/**
 * 用户
 * @package app\common\logic
 */
class Customer extends Common
{

    public function _initialize()
    {
        parent::_initialize();
    }


    /**
     * 列表
     */
    public function customerList($condition = [], $pages = 20, $isReturn = false)
    {
        $where = [];
        $pageParam = ['query' => []];


        //判断用户类型
        $type = input('type', 0, 'intval');
        isset($condition['type']) && $type = $condition['type'];
        if (!empty($type)) {
            $where['app_type'] = $type;
            $pageParam['query']['type'] = $type;
        }


        //昵称
        $name = input('name', '', 'trim');
        if ('' != $name) {
            $where['nick'] = ['LIKE', "%{$name}%"];
            $pageParam['query']['name'] = $name;
        }
        //会员号
        $member_id = input('member_id', '', 'trim');
        if ('' != $member_id) {
            $where['member_id'] = $member_id;
            $pageParam['query']['member_id'] = $member_id;
        }

        $query = $this->db->name('user')
            ->field('id,member_id,nick,nickCode,avatar,email,mobile,is_auth,app_type,gender,balance,create_time,last_login')
            ->where($where)
            ->order('id DESC')
            ->paginate($pages, false, $pageParam);
        $list = $query->all();
        $page = $query->render();
        $total = $query->total();
        $sex = [0 => lang('保密'), 1 => lang('男'), 2 => lang('女')];
        foreach ($list as $k => $v) {
            $v['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
            $v['last_login'] = !empty($v['last_login']) ? date("Y-m-d H:i:s", $v['last_login']) : '';
            empty($v['avatar']) && $v['avatar'] = config('customer.avatar');
            empty($v['nick']) && $v['nick'] = 'Usuario Misterioso';
//            $v['nick'] = $this->getCustomerNick($v['nickCode']);
            $v['gender'] = intval($v['gender']);
            $v['gender'] = $sex[$v['gender']];
            $v['client'] = config('app_name.' . $v['app_type']);
            $list[$k] = $v;
        }

        if ($isReturn) {
            return ['total' => $total, 'list' => $list];
        }

        //有查询则赋值
        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('title', lang('用户列表'));
    }


}