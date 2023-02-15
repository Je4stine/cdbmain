<?php

namespace app\agency\controller;

use app\agency\controller\Common;
use think\Request;
use think\Db;
use think\File;
use think\Session;
use think\Cookie;
use Godok\Org\FileManager;

/**
 * 特殊用户
 * Class Special
 * @package app\agency\controller
 */
class Special extends Common
{


    public function specialList()
    {
        $page_size = input('page_size', 0, 'intval');
        $page_size < 1 && $page_size = 10;
        $where = ['agency_id' => $this->auth_info['uid']];
        if ($this->auth_info['status'] != 1) {//禁止
            $where['agency_id'] = '-1';
        }
        $data = \think\Loader::model('Special', 'logic')
            ->specialList($where, $page_size, true);
        return $this->successResponse($data, '特殊用户列表');
    }

    //查找商户
    public function searchShop()
    {
        $page_size = input('page_size', 10, 'intval');
        $page_size < 1 && $page_size = 10;
        $keyword = input('keyword', '', 'trim');
        $uid = $this->auth_info['uid'];
        $where = ['not_delete' => 1];
        if ($this->auth_info['status'] != 1) {//禁止
            $where['id'] = 0;
        } else {
            if ('' != $keyword) {
                $where['name'] = ['LIKE', "%{$keyword}%"];
            }
            if ('agency' == $this->auth_info['role']) {
                $ids = \think\Loader::model('Agency', 'logic')->subAgencyIds($uid);
                $ids[] = $uid;
                $where['agency_id'] = ['IN', $ids];
            } else if ('employee' == $this->auth_info['role']) {
                $where['employee_id'] = $uid;
            } else if ('seller' == $this->auth_info['role']) {
                $where['manager_id'] = $uid;
            }
        }

        $query = $this->db->name('seller')
            ->field('id,name,logo')
            ->where($where)
            ->paginate($page_size, false, []);
        $list = $query->all();
        $total = $query->total();
        foreach ($list as &$v) {
            empty($v['logo']) && $v['logo'] = config('seller_img.logo');
            $v['logo'] = $v['logo'];
        }
        return $this->successResponse(['total' => $total, 'list' => $list], '商户列表');
    }


    //查找用户
    public function searchUser()
    {
        $member_id = input('member_id', '0', 'intval');
        $where = ['member_id' => $member_id];
        if ($this->auth_info['status'] != 1) {//禁止
            $where['id'] = 0;
        }
        $info = $this->db->name('user')
            ->field('id,nick,nickCode,avatar,member_id')
            ->where($where)
            ->find();
        if ($info) {
            $info['nick'] = $this->getCustomerNick($info['nickCode']);
            empty($info['avatar']) && $info['avatar'] = config('customer.avatar');
        } else {
            $info = [];
        }
        return $this->successResponse($info, '查找会员');
    }


    //保存
    public function add()
    {
        $this->checkForbid();
        $result = \think\Loader::model('Special', 'logic')->batchAdd($this->auth_info);
        if (1 != $result['code']) {
            return $this->errorResponse(0, $result['msg']);
        }
        return $this->successResponse([], $result['msg']);
    }

    //用户店铺数据
    public function userShops()
    {
        $this->checkForbid();
        $uid = input('uid', 0, 'intval');
        $result = \think\Loader::model('Special', 'logic')->userShops($this->auth_info['uid'], $uid);
        if (1 != $result['code']) {
            return $this->errorResponse(0, $result['msg']);
        }
        return $this->successResponse($result['data'], '用户店铺数据');
    }


    public function shopInfo()
    {
        $uid = input('uid', 0, 'intval');
        $sid = input('sid', 0, 'intval');
        $this->checkForbid();
        $info = $this->db->name('special_user')
            ->alias('u')
            ->join("seller s", 'u.sid = s.id', 'LEFT')
            ->field('u.id,u.uid,u.battery_free,u.battery_use,u.wired_free,u.wired_use,u.sid,s.name,s.logo')
            ->where(['uid' => $uid, 'sid' => $sid])
            ->find();

        if (!$info) {
            return $this->errorResponse(0, '数据不存在');
        }
        empty($info['logo']) && $info['logo'] = config('seller_img.logo');
        $info['logo'] = $info['logo'];
        return $this->successResponse($info, '店铺特殊用户详情');
    }


    //修改商户数据
    public function edit()
    {
        $this->checkForbid();
        $result = \think\Loader::model('Special', 'logic')->editShop($this->auth_info['uid']);
        if (1 != $result['code']) {
            return $this->errorResponse(0, $result['msg']);
        }
        return $this->successResponse([], $result['msg']);
    }


    //删除删除
    public function deleteShop()
    {
        $this->checkForbid();
        $id = input('id', 0, 'intval');
        $result = \think\Loader::model('Special', 'logic')->deleteShop($this->auth_info['uid'], $id);
        if (1 != $result['code']) {
            return $this->errorResponse(0, $result['msg']);
        }
        return $this->successResponse([], $result['msg']);
    }


    //删除
    public function delete()
    {
        $this->checkForbid();
        $uid = input('uid', 0, 'intval');
        $result = \think\Loader::model('Special', 'logic')->delete($this->auth_info['uid'], $uid);
        if (1 != $result['code']) {
            return $this->errorResponse(0, $result['msg']);
        }
        return $this->successResponse([], $result['msg']);
    }

}
