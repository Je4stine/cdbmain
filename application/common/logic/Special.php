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
 * 用户
 * @package app\common\logic
 */
class Special extends Common
{

    public function _initialize()
    {
        parent::_initialize();
    }


    /**
     * 列表
     */
    public function specialList($condition = [], $pages = 20, $isReturn = false)
    {
        $where = [];
        $pageParam = ['query' => []];

        $agency_id = input('agency_id', '', 'intval');
        isset($condition['agency_id']) && $agency_id = (int)$condition['agency_id'];
        if ('' != $agency_id) {
            $where['agency_id'] = $agency_id;
            $pageParam['query']['agency_id'] = $agency_id;
        }


		$member_id = input('keyword', 0, 'intval');
        isset($condition['member_id']) && $member_id = (int)$member_id['member_id'];
        if (!empty($member_id)) {
            $where['member_id'] = $member_id;
            $pageParam['query']['member_id'] = $member_id;
        }

        $query = $this->db->name('special_user')
            ->alias('s')
            ->join("user u", 's.uid = u.id', 'LEFT')
            ->field('s.id,s.uid,u.nick,u.nickCode,u.avatar,u.member_id')
            ->where($where)
            ->group('s.uid')
            ->order('s.id DESC')
            ->paginate($pages, false, $pageParam);
        $list = $query->all();
        $page = $query->render();
        $total = $query->total();
        foreach ($list as $k => $v) {
            empty($v['avatar']) && $v['avatar'] = config('customer.avatar');
            $v['avatar'] = $v['avatar'];
            $v['nick'] = $this->getCustomerNick($v['nickCode']);
            $list[$k] = $v;
        }

        if ($isReturn) {
            return ['total' => $total, 'list' => $list];
        }

        //有查询则赋值
        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('title', lang('特殊用户列表'));
    }

    //保存
    public function batchAdd($agency)
    {
        $uid = input('uid', 0, 'intval');
        $seller_ids = input('seller_ids', '', 'trim');
        $battery_free = input('battery_free', 0, 'intval');
        $wired_free = input('wired_free', 0, 'intval');
        $seller_ids = explode(",", $seller_ids);
        if (empty($battery_free) && empty($wired_free)) {
            return ['code' => 0, 'msg' => lang( '请设置充电宝免费时长或密码线免费次数')];
        }
        if (empty($uid)) {
            return ['code' => 0, 'msg' =>  lang('请选择会员')];
        }
        $sids = $this->db->name('special_user')
            ->where(['uid' => $uid, 'sid' => ['IN', $seller_ids]])
            ->column('sid');
        if ($sids) {
            $name = $this->db->name('seller')->where(['id' => ['IN', $sids]])->column('name');
            return ['code' => 0, 'msg' => implode("、", $name) .  lang('已绑定该用户')];
        }

        //判断是否可以操作该店铺
        $where = ['not_delete' => 1, 'id' => ['IN', $seller_ids]];
        if ('agency' == $agency['role']) {
            $ids = \think\Loader::model('Agency', 'logic')->subAgencyIds($agency['uid']);
            $ids[] = $agency['uid'];
            $where['agency_id']= ['IN', $ids];
        } else if ('employee' == $agency['role']) {
            $where['employee_id'] = $agency['uid'];
        } else if ('seller' == $agency['role']) {
            $where['manager_id'] = $agency['uid'];
        }
        $seller_ids = $this->db->name('seller')
            ->where($where)
            ->column('id');
        if(empty($seller_ids)){
            return ['code' => 0, 'msg' =>  lang('请选择店铺')];
        }

        $this->db->startTrans();
        try {
            foreach ($seller_ids as $sid) {
                $params = [
                    'agency_id' => $agency['uid'],
                    'uid' => $uid,
                    'sid' => $sid,
                    'battery_free' => $battery_free,
                    'wired_free' => $wired_free,
                    'create_time' => time(),
                ];
                $params2 = $params;
                $params2['update_time'] = time();
                unset($params2['create_time']);
                $sql = sqlInsertUpdate('special_user', $params, $params2);
                $this->db->execute($sql);
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            save_log('sql', $e->getMessage());
            return ['code' => 0, 'msg' =>  lang('系统繁忙，请稍后重试')];
        }
        $this->operateLog(0, '添加特殊用户');
        return ['code' => 1, 'msg' =>  lang('保存成功')];
    }

    //用户店铺数据
    public function userShops($agency_id, $uid)
    {
        $list = $this->db->name('special_user')
            ->alias('u')
            ->join("seller s", 'u.sid = s.id', 'LEFT')
            ->field('u.id,u.uid,u.battery_free,u.battery_use,u.wired_free,u.wired_use,u.sid,s.name,s.logo')
            ->where(['u.uid' => $uid, 'u.agency_id' => $agency_id])
            ->order('u.id DESC')
            ->select();

        if (!$list) {
            return ['code' => 0, 'msg' =>  lang('用户未绑定任何商户')];
        }
        foreach ($list as $k => $v) {
            empty($v['logo']) && $v['logo'] = config('seller_img.logo');
            $list[$k]['logo'] = config('qcloudurl') . $v['logo'];
        }
        return ['code' => 1, 'msg' =>  lang('用户店铺数据'), 'data' => ['list' => $list]];
    }


    //删除
    public function delete($agency_id, $id)
    {
        $info = $this->db->name('special_user')->where(['uid' => $id, 'agency_id' => $agency_id])->find();
        if (!$info) {
            return ['code' => 0, 'msg' =>  lang('用户不存在')];
        }
        $this->db->name('special_user')->where(['uid' => $id])->delete();
        $this->operateLog($id, '删除特殊用户');
        return ['code' => 1, 'msg' => lang( '删除成功')];
    }


    //删除店铺用户
    public function deleteShop($agency_id, $id)
    {
        $info = $this->db->name('special_user')->where(['id' => $id, 'agency_id' => $agency_id])->find();
        if (!$info) {
            return ['code' => 0, 'msg' => lang( '用户不存在')];
        }
        $this->db->name('special_user')->where(['id' => $id])->delete();
        $this->operateLog($id, '删除特殊用户');
        return ['code' => 1, 'msg' =>  lang('删除成功')];
    }

    //删除店铺用户
    public function editShop($agency_id)
    {
        $uid = input('uid', 0, 'intval');
        $sid = input('sid', 0, 'intval');
        $battery_free = input('battery_free', 0, 'intval');
        $wired_free = input('wired_free', 0, 'intval');
        if (empty($battery_free) && empty($wired_free)) {
            return ['code' => 0, 'msg' =>  lang('请设置充电宝免费时长或密码线免费次数')];
        }

        $info = $this->db->name('special_user')->where(['uid' => $uid, 'agency_id' => $agency_id, 'sid' => $sid])->find();
        if (!$info) {
            return ['code' => 0, 'msg' =>  lang('用户不存在')];
        }
        $this->db->name('special_user')->where(['id' => $info['id']])->update(['battery_free' => $battery_free, 'wired_free' => $wired_free, 'update_time' => time()]);
        $this->operateLog($uid, '修改特殊用户');
        return ['code' => 1, 'msg' =>  lang('保存成功')];
    }


    function getAgencyIds($role, $uid)
    {
        if ('agency' == $role) {
            $ids = \think\Loader::model('Agency', 'logic')->subAgencyIds($uid);
            $ids[] = $uid;
            $where = ['agency_id' => ['IN', $ids]];
        } else if ('employee' == $this->auth_info['role']) {
            $where = ['employee_id' => $uid];
        } else if ('seller' == $this->auth_info['role']) {
            $where = ['manager_id' => $uid];
        }
    }
}