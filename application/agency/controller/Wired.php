<?php

namespace app\agency\controller;

use app\agency\controller\Common;
use think\Request;
use think\Db;
use think\Controller;
use think\Config;
use Godok\Org\Auth;
use Godok\Org\Filter;
use think\Cache;

//线充管理
class Wired extends Common
{


    public function deviceList()
    {

        $page_size = input('page_size', 10, 'intval');
        $page_size < 1 && $page_size = 10;
        $is_sub = input('is_sub');//代理商有下级
        $is_sub = ($is_sub == 'true') ? true : false;
        $status = input('status', '', 'trim');

        $where = $this->_getCondition($this->auth_info['role'], $this->auth_info['uid']);
        switch ($status) {
            case 'bind':
                (!isset($where['a.sid'])) && $where['a.sid'] = ['>', 0];
                break;
            case 'unbind':
                $where['a.sid'] = ($this->auth_info['role'] == 'seller') ? -1 : 0;
                break;
            case 'fail':
                $where['a.status'] = 0;
                break;
            default:
                return $this->errorResponse(403, '非法请求');
        }


        $query = $this->db->name('wired_device')
            ->alias('a')
            ->field('a.*')
            ->where($where);
        if ($is_sub) {
            $query->join("device_agency b", 'a.device_id = b.device_code', 'LEFT');
        }
        $query = $query->paginate($page_size, false, []);

        $total = $query->total();
        $data = $query->all();
        $list = [];

        if ($data) {//获取店铺信息
            $sids = array_column($data, 'sid');
            $seller_query = $this->db->name('seller')
                ->field('id,name')
                ->where(['id' => ['IN', $sids]])
                ->select();
            $seller_data = array_column($seller_query, NULL, 'id');
        }
        $sub_ids = [];
        foreach ($data as $v) {
            $tmp = [
                'device_id' => $v['id'],
                'qrcode' => $v['code'],
                'sid' => $v['sid'],
                'seller_name' => isset($seller_data[$v['sid']]) ? $seller_data[$v['sid']]['name'] : '',
                'sub_id' => 0,
                'sub_role' => '',
                'sub_name' => "",
                'sub_avatar' => "",
                'is_sub' => $is_sub,
                'can_edit' => true,
                'agency_id' => $v['agency_id'],
                'employee_id' => $v['employee_id'],
                'reset_code' => $v['reset_code'],
            ];
            $sub_ids[] = $v['agency_id'];
            !empty($v['employee_id']) && $sub_ids[] = $v['employee_id'];
            $list[] = $tmp;
        }
        if ('agency' == $this->auth_info['role']) {//代理
            $sub_ids = array_unique($sub_ids);
            $agency = $this->db->name('agency')->field('id,name,avatar,is_self,parent_id,type')->where(['id' => ['IN', $sub_ids]])->select();
            $agency = array_column($agency, NULL, 'id');

            foreach ($list as $k => $v) {
                if (!$is_sub && !empty($v['employee_id'])) {//自己设备
                    $v['sub_id'] = $v['employee_id'];
                    $v['sub_name'] = $agency[$v['employee_id']]['name'];
                    $v['sub_avatar'] = $agency[$v['employee_id']]['avatar'];
                    empty($v['sub_avatar']) && $v['sub_avatar'] = config('seller_img.avatar');
                    $v['sub_role'] = config('user_type_name.2');
                }

                if ($is_sub) {//下级设备
                    $v['sub_id'] = $v['agency_id'];
                    $v['sub_name'] = $agency[$v['agency_id']]['name'];
                    $v['sub_avatar'] = $agency[$v['agency_id']]['avatar'];
                    empty($v['sub_avatar']) && $v['sub_avatar'] = config('seller_img.avatar');
                    $agency[$v['agency_id']]['is_self'] == 1 && $v['can_edit'] = false;//自购机代理不能修改数据
                    $agency[$v['agency_id']]['parent_id'] != $this->auth_info['uid'] && $v['can_edit'] = false;//非直属下级不能修改数据
                    $v['sub_role'] = config('user_type_name.' . $agency[$v['agency_id']]['type']);
                }
                !empty($v['sub_avatar']) && $v['sub_avatar'] = $v['sub_avatar'];

                unset($v['agency_id']);
                $list[$k] = $v;
            }
        }

        $data = ['total' => $total, 'list' => $list];

        return $this->successResponse($data, '设备列表');
    }


    //设备数量
    public function num()
    {
        $where = $this->_getCondition($this->auth_info['role'], $this->auth_info['uid']);
        $where2 = !isset($where['a.sid']) ? ['a.sid' => ['>', 0]] :['a.id' => ['>', 0]];
        $data = [
            'bind' => $this->db->name('wired_device')->alias('a')->where($where)->where($where2),
            'unbind' => $this->db->name('wired_device')->alias('a')->where($where)->where(['a.sid' => 0]),
            'fail' => $this->db->name('wired_device')->alias('a')->where($where)->where(['a.status' => 0]),
        ];
        $is_sub = input('is_sub', '', 'trim');
        if ($this->auth_info['role'] == 'agency' && $is_sub == 'true') {//代理商下级
            $data['bind']->join("device_agency b", 'a.code = b.device_code', 'LEFT');
            $data['unbind']->join("device_agency b", 'a.code = b.device_code', 'LEFT');
            $data['fail']->join("device_agency b", 'a.code = b.device_code', 'LEFT');
        }
        $data['bind'] = $data['bind']->count();
        $data['unbind'] = $data['unbind']->count();
        $data['fail'] = $data['fail']->count();
        return $this->successResponse($data, '设备数量');
    }


    public function simpleList()
    {
        $page_size = input('page_size', 10, 'intval');
        $page_size < 1 && $page_size = 10;
        $uid = input('uid', 2, 'intval');
        $role = input('role', 'agency', 'trim');
        $status = input('status', 'all', 'trim');


        if (empty($uid) || !config('user_type.' . $role)) {
            return $this->errorResponse(403, '非法请求');
        }

        //权限判断
        $agency = $this->db->name('agency')->where(['id' => $uid, 'not_delete' => 1])->find();
        if (!$agency) {
            return $this->errorResponse(0, '用户不存在');
        }

        if ('agency' == $this->auth_info['role']) {
            $parents = empty($agency['parents']) ? [] : explode(',', $agency['parents']);
            if (!in_array($this->auth_info['uid'], $parents)) {
                return $this->errorResponse(0, '用户不存在或无权查看');
            }
        } else if ('employee' == $this->auth_info['role']) {
            if ($this->auth_info['uid'] != $agency['employee_id']) {
                return $this->errorResponse(0, '用户不存在或无权查看');
            }
        }

        $where = $this->_getCondition($role, $uid);
        switch ($status) {
            case 'all':
                break;
            case 'unbind':
                $where['a.sid'] = ($role == 'seller') ? -1 : 0;
                break;
            default:
                return $this->errorResponse(403, '非法请求');
        }

        $query = $this->db->name('wired_device')
            ->alias('a')
            ->join("seller b", 'a.sid = b.id', 'LEFT')
            ->field('a.code as qrcode,b.name as seller_name')
            ->where($where)
            ->paginate($page_size, false, []);
        $total = $query->total();
        $list = $query->all();
        foreach ($list as $k => $v) {
            $list[$k]['seller_name'] = empty($v['seller_name']) ? '' : $v['seller_name'];
        }

        $data = ['total' => $total, 'list' => $list];
        return $this->successResponse($data, '设备列表');
    }


    private function _getCondition($role, $uid)
    {
        if ($this->auth_info['status'] != 1) {//禁止
            return $where['a.id'] = 0;
        }
        $is_sub = input('is_sub', '', 'trim');//代理商有下级
        $keyword = input('keyword', '', 'trim');//设备号
        $seller = input('seller', '', 'trim');//商家

        $where = ['a.not_delete' => 1];
        if ('' != $keyword) {
            $where['a.code'] = ['LIKE', "%{$keyword}%"];
        }

        $seller_Ids = [];//店铺关键字查询
        if ('agency' == $role) {
            if ('' != $seller) {
                if ($is_sub == 'true') {
                    $ids = \think\Loader::model('Agency', 'logic')->subAgencyIds($uid);
                    empty($ids) && $ids = ['-1'];
                } else {
                    $ids = [$uid];
                }
                $seller_Ids = $this->db->name('seller')->where(['not_delete' => 1, 'agency_id' => ['IN', $ids], 'name' => ['LIKE', "%{$seller}%"]])->column('id');
            }
            if ($is_sub == 'true') {//下级
                $where['b.is_self'] = 0;
                $where['b.type'] = 2;
                $where['b.agency_id'] = $uid;
            } else {
                $where['a.agency_id'] = $uid;
            }
        } else if ('employee' == $role) {
            $where['a.employee_id'] = $uid;
            if ('' != $seller) {//商家关键字
                $seller_Ids = $this->db->name('seller')->where(['not_delete' => 1, 'employee_id' => $uid, 'name' => ['LIKE', "%{$seller}%"]])->column('id');
                empty($seller_Ids) && $seller_Ids = ['-1'];
            }
        } else if ('seller' == $role) {
            $query = $this->db->name('seller')->where(['not_delete' => 1, 'manager_id' => $uid]);
            if ('' != $seller) {
                $query->where(['name' => ['LIKE', "%{$seller}%"]]);
            }
            $seller_Ids = $query->column('id');
            empty($seller_Ids) && $seller_Ids = ['-1'];
        }
        !empty($seller_Ids) && $where['a.sid'] = ['IN', $seller_Ids];
        return $where;
    }


    //绑定代理,上级代理不能操作下级代理数据
    function bindAgency()
    {
        if ('agency' != $this->auth_info['role']) {
            return $this->errorResponse(403, '非法请求');
        }
        $this->checkForbid();
        $agency_id = input('agency_id', 0, 'intval');
        $code_type = input('code_type', 'qrcode', 'trim');

        $where = ['agency_id' => $this->auth_info['uid'], 'not_delete' => 1];
        if( 'qrcode' == $code_type){
            $where['code'] = input('qrcode');
        }else{
            $where['id'] = input('device_id');
        }

        $info = $this->db->name('wired_device')
            ->where($where)
            ->find();

        if (!$info) {
            return $this->errorResponse(0, '设备不存在或已绑定下级代理');
        }
        if (!empty($info['employee_id'])) {
            return $this->errorResponse(0, '设备已绑定业务员');
        }
        $agency = $this->db->name('agency')
            ->where(['parent_id' => $this->auth_info['uid'], 'not_delete' => 1, 'id' => $agency_id])
            ->find();
        if (!$agency || $agency['type'] > 2) {
            return $this->errorResponse(0, '下级不存在');
        }
        $field = (1 == $agency['type']) ? 'agency_id' : 'employee_id';
        $params = [$field => $agency_id,  'update_time' => time()];
        if(!empty($info['sid'])){//有绑定店铺
            $seller =  $this->db->name('seller')->where(['id' => $info['sid']])->find();
            if($seller[$field] != $agency_id){
                $params['sid'] = 0;
            }
        }

        $this->db->name('wired_device')
            ->where(['id' => $info['id']])
            ->update($params);
        (1 == $agency['type']) && \think\Loader::model('Equipment', 'logic')->agencyRelation($info, $agency_id, 2);

        $sub_role = config('user_type_name.' . $agency['type']);
        empty($agency['avatar']) && $agency['avatar'] = config('seller_img.avatar');
        $agency['avatar'] = $agency['avatar'];
        $data = ['sub_id' => $agency['id'], 'sub_name' => $agency['name'], 'sub_role' => $sub_role, 'sub_avatar' => $agency['avatar']];
        return $this->successResponse($data, '绑定成功');
    }


    //取消绑定代理，只能操作直属下级代理及业务员
    function unbindAgency()
    {
        if ('agency' != $this->auth_info['role']) {
            return $this->errorResponse(403, '非法请求');
        }
        $this->checkForbid();
        $device_id = input('device_id');
        $qrcode = input('qrcode');
        $type = input('type','id');
        $where = ['not_delete' => 1];
        if('qrcode' == $type){
            $where['code'] = $qrcode;
        }else{
            $where['id'] = $device_id;
        }

        $info = $this->db->name('wired_device')
            ->where($where)
            ->find();
        if (!$info) {
            return $this->errorResponse(0, '设备不存在');
        }
        if ($info['agency_id'] == $this->auth_info['uid']) {//设备属于自身
            if (empty($info['employee_id'])) {
                return $this->successResponse([], '取消成功');
            }
            $this->db->name('wired_device')
                ->where(['id' => $info['id']])
                ->update(['employee_id' => 0, 'update_time' => time()]);
            return $this->successResponse([], '取消成功');
        }
        //直属下级代理
        $agency = $this->db->name('agency')
            ->where(['parent_id' => $this->auth_info['uid'], 'not_delete' => 1, 'id' => $info['agency_id']])
            ->find();
        if (!$agency) {
            return $this->errorResponse(0, '设备不存在或非直属代理');
        }
        if ($agency['is_self'] == 1) {//购机代理不能解绑
            return $this->errorResponse(0, '该代理设备不能取消绑定');
        }
        $parmas = ['agency_id' => $this->auth_info['uid'], 'employee_id' => 0, 'update_time' => time()];
        $agency['type'] == 1 && $parmas['sid'] = 0;//解绑商户

        $this->db->name('wired_device')
            ->where(['id' => $info['id']])
            ->update(['agency_id' => $this->auth_info['uid'], 'employee_id' => 0, 'update_time' => time()]);

        (1 == $agency['type']) && \think\Loader::model('Equipment', 'logic')->agencyRelation($info, $this->auth_info['uid'],2);
        return $this->successResponse([], '取消成功');
    }


    //绑定商户
    function bindSeller()
    {
        $seller_id = input('seller_id', 0, 'intval');
        if (!in_array($this->auth_info['role'], ['agency', 'employee'])) {
            return $this->errorResponse(403, '非法请求');
        }
        $this->checkForbid();
        $field = ('agency' == $this->auth_info['role']) ? 'agency_id' : 'employee_id';
        $code_type = input('code_type', 'qrcode', 'trim');

        $where = [$field => $this->auth_info['uid'], 'not_delete' => 1];

        if( 'qrcode' == $code_type){
            $where['code'] = input('qrcode');
        }else{
            $where['id'] = input('device_id');
        }


        $info = $this->db->name('wired_device')
            ->where($where)
            ->find();
        if (!$info) {
            return $this->errorResponse(0, '设备不存在');
        }
        if (!empty($info['sid'])) {
            return $this->errorResponse(0, '设备已绑定商户');
        }
        $seller = $this->db->name('seller')
            ->field('id,name')
            ->where(['not_delete' => 1, 'id' => $seller_id, $field => $this->auth_info['uid']])
            ->find();
        if (!$seller) {
            return $this->errorResponse(0, '商户不存在或无权绑定');
        }
        $this->db->name('wired_device')
            ->where(['id' => $info['id']])
            ->update(['sid' => $seller_id, 'update_time' => time()]);
        $data = ['sid' => $seller['id'], 'seller_name' => $seller['name']];
        return $this->successResponse($data, '绑定成功');
    }


    //取消绑定商户
    function unbindSeller()
    {
        $device_id = input('device_id');
        if (!in_array($this->auth_info['role'], ['agency', 'employee'])) {
            return $this->errorResponse(403, '非法请求');
        }
        $this->checkForbid();
        $info = $this->db->name('wired_device')
            ->where(['not_delete' => 1, 'id' => $device_id])
            ->find();
        if (!$info) {
            return $this->errorResponse(0, '设备不存在');
        }
        $field = ('agency' == $this->auth_info['role']) ? 'agency_id' : 'employee_id';
        if ($info[$field] != $this->auth_info['uid']) {
            return $this->errorResponse(0, '商户不存在或无权解绑');
        }
        $this->db->name('wired_device')
            ->where(['id' => $info['id']])
            ->update(['sid' => 0, 'update_time' => time()]);
        return $this->successResponse([], '取消商户绑定');
    }

    //复位
    public function reset($id = '')
    {
        $device_id = input('device_id', 0, 'intval');
        $this->checkForbid();
        $info = $this->_checkInfo($device_id);
        if ($info['can_edit'] != 'true') {
            return $this->error('密码线不存在或无权操作');
        }
        $this->db->name('wired_device')
            ->where(['id' => $id])
            ->update(['password_index' => 0, 'update_time' => time()]);
        return ['code' => 1, 'msg' => '数据已更新'];
    }

    private function _checkInfo($device_id)
    {
        $info = $this->db->name('wired_device')
            ->where(['not_delete' => 1, 'id' => $device_id])
            ->find();
        if (!$info) {
            $this->error('设备不存在');
        }
        $this->checkForbid();
        $info['can_edit'] = false;//是否可操作
        if ('agency' == $this->auth_info['role']) {//代理

            if ($info['agency_id'] == $this->auth_info['uid']) {//自己设备
                $info['can_edit'] = true;
            } else {
                $agency = $this->db->name('agency')->where(['id' => $info['agency_id']])->find();
                if (!$agency || empty($agency['parents'])) {
                    $this->error('设备不存在');
                }
                $agency['parents'] = explode(",", $agency['parents']);
                if (!in_array($this->auth_info['uid'], $agency['parents'])) {//不是属于自己下级
                    $this->error('设备不存在');
                }
                if ($agency['is_self'] != 1) {//如果是购机代理，则不能操作数据
                    $info['can_edit'] = true;
                }
            }
        } else if ('employee' == $this->auth_info['role']) {//业务员
            if ($info['employee_id'] != $this->auth_info['uid']) {
                $this->error('设备不存在');
            }
            $info['can_edit'] = true;
        } else if ('seller' == $this->auth_info['role']) {//店铺管理员
            $manager_id = $this->db->name('seller')->where(['id' => $info['sid']])->value('manager_id');
            if ($manager_id != $this->auth_info['uid']) {
                $this->error('设备不存在');
            }
            $info['can_edit'] = true;
        }
        return $info;
    }
}
