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

//设备管理
class Device extends Common
{

    //设备列表
    public function deviceList()
    {
        $page_size = input('page_size', 10, 'intval');
        $page_size < 1 && $page_size = 10;
        $is_sub = input('is_sub', '');//代理商有下级
        $is_sub = ($is_sub == 'true') ? true : false;

        $status = input('status', 'offline', 'trim');
        $where = $this->_getCondition($this->auth_info['role'], $this->auth_info['uid']);
        switch ($status) {
            case 'online':
                $where['a.heart_time'] = ['>', time() - config('online_time')];
                $where['a.is_online'] = 1;
                break;
            case 'offline'://and or语句后面拼装
                break;
            case 'bind':
                (!isset($where['a.sid'])) && $where['a.sid'] = ['>', 0];
                break;
            case 'unbind':
                $where['a.sid'] = ($this->auth_info['role'] == 'seller') ? -1 : 0;
                break;
            case 'fail':
                $where['a.is_fault'] = 1;
                break;
            default:
                return $this->errorResponse(403, '非法请求');
        }

        $query = $this->db->name('charecabinet')
            ->alias('a')
            ->field('a.id,a.sid,a.cabinet_id as device_id,a.device_num,a.agency_id,a.employee_id,a.heart_time,a.is_online')
            ->where($where);
        if ($is_sub) {
            $query->join("device_agency b", 'a.cabinet_id = b.device_code', 'LEFT');
        }
        if ('offline' == $status) {
            $query->where('a.heart_time < :time OR a.is_online = :is_online ', ['time' => time() - config('online_time'), 'is_online' => 0]);
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

        $time = time();
        $sub_ids = [];
        foreach ($data as $v) {
            $tmp = [
                'device_id' => $v['device_id'],
                'heart_time' => empty($v['heart_time']) ? '' : date("Y-m-d H:i:s", $v['heart_time']),
                'borrow_num' => 0,
                'return_num' => $v['device_num'],
                'device_num' => $v['device_num'],
                'sid' => $v['sid'],
                'seller_name' => isset($seller_data[$v['sid']]) ? $seller_data[$v['sid']]['name'] : '',
                'sub_id' => 0,
                'sub_role' => '',
                'sub_name' => "",
                'sub_avatar' => "",
                'is_sub' => $is_sub,
                'can_edit' => true,
                'is_online' => true,
                'agency_id' => $v['agency_id'],
                'employee_id' => $v['employee_id'],
            ];
            $cache = $this->getEquipmentCache($v['device_id']);
            if (!$cache || ($time - $v['heart_time']) > config('online_time') || $v['is_online'] < 1) {
                $tmp['is_online'] = false;
            }
            if ($cache) {
                $tmp['borrow_num'] = intval($cache['stock_num']);
                $tmp['return_num'] = $tmp['device_num'] - $tmp['borrow_num'];
                $tmp['return_num'] < 0 && $tmp['return_num'] = 0;
            }
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


    public function simpleList()
    {
        $page_size = input('page_size', 10, 'intval');
        $page_size < 1 && $page_size = 10;
        $uid = input('uid', 0, 'intval');
        $role = input('role', '', 'trim');
        $status = input('status', '', 'trim');

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
            case 'offline'://and or语句后面拼装
                break;
            case 'unbind':
                $where['a.sid'] = ($role == 'seller') ? -1 : 0;
                break;
            default:
                return $this->errorResponse(403, '非法请求');
        }

        $query = $this->db->name('charecabinet')
            ->alias('a')
            ->field('a.id,a.sid,a.cabinet_id as device_id,a.device_num,a.agency_id,a.employee_id,a.heart_time')
            ->where($where);
        if ('offline' == $status) {
            $query->where('a.heart_time < :time OR a.is_online = :is_online ', ['time' => time() - config('online_time'), 'is_online' => 0]);
        }

        $query = $query->paginate($page_size, false, []);
        $total = $query->total();
        $list = [];

        $storage = \think\Loader::model('Storage', 'service');
        $time = time();
        foreach ($query->all() as $v) {
            $tmp = [
                'device_id' => $v['device_id'],
                'heart_time' => empty($v['heart_time']) ? '' : date("Y-m-d H:i:s", $v['heart_time']),
                'is_online' => true,
                'seller_name' => empty($v['seller_name']) ? '' : $v['seller_name'],
                'battery' => [],
            ];
            $cache = $this->getEquipmentCache($v['device_id']);
            if (!$cache || ($time - $v['heart_time']) > config('online_time')) {
                $tmp['is_online'] = false;
            }
            if ($cache && $cache['details']) {
                foreach ($cache['details'] as $v) {
                    $tmp['battery'][] = [
                        'battery_id' => $v['bid'],
                        'power' => $v['power'],
                        'lock_id' => $v['lock'],
                        'status' => 'borrow'
                    ];
                }
            }
            $list[] = $tmp;
        }
        $data = ['total' => $total, 'list' => $list];
        return $this->successResponse($data, '设备列表');
    }


    public function detail()
    {
        $this->checkForbid();
        $device_id = input('device_id', '');
        $logic = \think\Loader::model('Equipment', 'logic');
        $data = $this->_checkInfo($device_id);

        $info = $data;
        $info = [
            'cabinet_id' => $info['cabinet_id'],
            'device_id' => $info['cabinet_id'],
            'create_time' => date("Y-m-d H:i:s", $info['create_time']),
            'heart_time' => empty($info['heart_time']) ? '' : date("Y-m-d H:i:s", $info['heart_time']),
            'qrcode' => config('qrcodeurl') . "/Lease?o=" . mwencrypt((string)$this->oid) . "&&t={$info['qrcode']}",
            'open_lock' => $info['open_lock'],
            'can_edit' => $info['can_edit'],
            'sim' => $info['network_card'] ? $info['network_card'] : '',
            'device_num' => $info['device_num'],
            'is_fault' => $info['is_fault'],
            'agency' => [],
        ];


        $cache = $this->getEquipmentCache($info['cabinet_id']);

        for ($i = 1; $i <= $info['device_num']; $i++) {
            $info['battery'][$i] = ['battery_id' => '', 'power' => '', 'lock_id' => $i];
            if (isset($cache['details'][$i])) {
                $info['battery'][$i]['battery_id'] = $cache['details'][$i]['bid'];
                $info['battery'][$i]['power'] = $cache['details'][$i]['power'];
            }
        }

        if ('agency' == $this->auth_info['role']) {//显示上一级及所有下级归属
            $agency_id = !empty($data['employee_id']) ? $data['employee_id'] : $data['agency_id'];
            $agency_ids = $this->db->name('agency')->where(['id' => $agency_id])->value('parents');
            $agency_ids = empty($agency_ids) ? [] : explode(",", $agency_ids);
            $agency_ids[] = $agency_id;
            //往上最多只能看到自己直属上级
            $key = array_search($this->auth_info['uid'], $agency_ids);
            $key > 0 && $key = $key - 1;
            $agency_ids = array_slice($agency_ids, $key);

            $agency = $this->db->name('agency')->field('id,name,parent_id,type')->where(['id' => ['IN', $agency_ids]])->select();
            $agency = array_column($agency, NULL, 'id');
            $parents = \think\Loader::model('Agency')->getParents($agency, $agency_id);
            $role = config('user_type_name');
            foreach ($parents as $k => $v) {
                $v['type'] = $role[$v['type']];
                empty($v['avatar']) && $v['avatar'] = config('seller_img.avatar');
                $v['avatar'] =  $v['avatar'];
                $info['agency'][] = $v;
            }

        }

        return $this->successResponse($info, '设备详情');
    }

    //设备数量
    public function num()
    {
        $where = $this->_getCondition($this->auth_info['role'], $this->auth_info['uid']);
        $where2 = !isset($where['a.sid']) ? ['a.sid' => ['>', 0]] : ['a.id' => ['>', 0]];
        $data = [
            'online' => $this->db->name('charecabinet')->alias('a')->where($where)->where(['a.heart_time' => ['>', time() - config('online_time')]]),
            'offline' => $this->db->name('charecabinet')->alias('a')->where($where)->where('a.heart_time < :time OR a.is_online = :is_online ', ['time' => time() - config('online_time'), 'is_online' => 0]),
            'bind' => $this->db->name('charecabinet')->alias('a')->where($where)->where($where2),
            'unbind' => $this->db->name('charecabinet')->alias('a')->where($where)->where(['a.sid' => 0]),
            'fail' => $this->db->name('charecabinet')->alias('a')->where($where)->where(['a.is_fault' => 1]),
        ];
        $is_sub = input('is_sub', '', 'trim');
        if ($this->auth_info['role'] == 'agency' && $is_sub == 'true') {//代理商下级
            $data['online']->join("device_agency b", 'a.cabinet_id = b.device_code', 'LEFT');
            $data['offline']->join("device_agency b", 'a.cabinet_id = b.device_code', 'LEFT');
            $data['bind']->join("device_agency b", 'a.cabinet_id = b.device_code', 'LEFT');
            $data['unbind']->join("device_agency b", 'a.cabinet_id = b.device_code', 'LEFT');
            $data['fail']->join("device_agency b", 'a.cabinet_id = b.device_code', 'LEFT');
        }
        $data['online'] = $data['online']->count();
        $data['offline'] = $data['offline']->count();
        $data['bind'] = $data['bind']->count();
        $data['unbind'] = $data['unbind']->count();
        $data['fail'] = $data['fail']->count();
        return $this->successResponse($data, '设备数量');
    }

    private function _getCondition($role, $uid)
    {
        if ($this->auth_info['status'] != 1) {//禁止
            return ['a.id' => 0];
        }

        $is_sub = input('is_sub', '', 'trim');//代理商有下级
        $keyword = input('keyword', '', 'trim');//设备号
        $qrcode = input('qrcode', '', 'trim');//二维码
        $seller = input('seller', '', 'trim');//商家

        $where = ['a.not_delete' => 1];
        if ('' != $keyword) {
            $keyword = strtoupper($keyword);
            $where['a.cabinet_id'] = ['LIKE', "%{$keyword}%"];
        }
        if ('' != $qrcode) {
            $qrcode = strtoupper($qrcode);
            $where['a.qrcode'] = ['LIKE', "%{$qrcode}%"];
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
                $where['b.type'] = 1;
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
        $device_id = input('device_id');
        $agency_id = input('agency_id', 0, 'intval');
        $code_type = input('code_type', 'qrcode', 'trim');

        $where = ['agency_id' => $this->auth_info['uid'], 'not_delete' => 1];
        if ('qrcode' == $code_type) {
            $where['qrcode'] = input('qrcode');
        } else {
            $sn = input('device_id');
            if (preg_match('/http/i', $sn)) {
                $flag = mwencrypt((string)$this->oid);
                $url = "https://qrcode.w-dian.cn/Lease?o={$flag}&&t=";
                $sn = str_replace($url, "", $sn);
            }
            $where['cabinet_id'] = $sn;
        }
        $info = $this->db->name('charecabinet')
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
        $params = [$field => $agency_id, 'update_time' => time()];
        if (!empty($info['sid'])) {//有绑定店铺
            $seller = $this->db->name('seller')->where(['id' => $info['sid']])->find();
            if ($seller[$field] != $agency_id) {
                $params['sid'] = 0;
            }
        }

        $this->db->name('charecabinet')
            ->where(['id' => $info['id']])
            ->update($params);
        (1 == $agency['type']) && \think\Loader::model('Equipment', 'logic')->agencyRelation($info, $agency_id);

        $sub_role = config('user_type_name.' . $agency['type']);
        empty($agency['avatar']) && $agency['avatar'] = config('seller_img.avatar');
        $agency['avatar'] = $agency['avatar'];
        $data = ['sub_id' => $agency['id'], 'sub_name' => $agency['name'], 'sub_role' => $sub_role, 'sub_avatar' => $agency['avatar']];
        $this->operateLog($info['id'], '绑定代理');
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
            $where['qrcode'] = $qrcode;
        }else{
            $where['cabinet_id'] = $device_id;
        }

        $info = $this->db->name('charecabinet')
            ->where($where)
            ->find();
        if (!$info) {
            return $this->errorResponse(0, '设备不存在');
        }
        if ($info['agency_id'] == $this->auth_info['uid']) {//设备属于自身
            if (empty($info['employee_id'])) {
                return $this->successResponse([], '取消成功');
            }
            $this->db->name('charecabinet')
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
        $this->db->name('charecabinet')
            ->where(['id' => $info['id']])
            ->update($parmas);
        (1 == $agency['type']) && \think\Loader::model('Equipment', 'logic')->agencyRelation($info, $this->auth_info['uid']);
        $this->operateLog($info['id'], '解绑代理');
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
        if ('qrcode' == $code_type) {
            $where['qrcode'] = input('qrcode');
        } else {
            $sn = input('device_id');
            if (preg_match('/http/i', $sn)) {
                $flag = mwencrypt((string)$this->oid);
                $url = "https://qrcode.w-dian.cn/Lease?o={$flag}&&t=";
                $sn = str_replace($url, "", $sn);
            }
            $where['cabinet_id'] = $sn;
        }

        $info = $this->db->name('charecabinet')
            ->where($where)
            ->find();
        if (!$info) {
            return $this->errorResponse(0, '设备不存在');
        }
        if (!empty($info['sid'])) {
            return $this->errorResponse(0, '设备已绑定商户');
        }
        $seller = $this->db->name('seller')
            ->field('id,name,employee_id')
            ->where(['not_delete' => 1, 'id' => $seller_id, $field => $this->auth_info['uid']])
            ->find();
        if (!$seller) {
            return $this->errorResponse(0, '商户不存在或无权绑定');
        }
        if (!empty($info['employee_id']) && $info['employee_id'] != $seller['employee_id']) {
            return $this->errorResponse(0, '商户和设备不属于同一个业务员');
        }
        $this->db->name('charecabinet')
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
        $info = $this->db->name('charecabinet')
            ->where(['not_delete' => 1, 'cabinet_id' => $device_id])
            ->find();
        if (!$info) {
            return $this->errorResponse(0, '设备不存在');
        }
        $field = ('agency' == $this->auth_info['role']) ? 'agency_id' : 'employee_id';
        if ($info[$field] != $this->auth_info['uid']) {
            return $this->errorResponse(0, '商户不存在或无权解绑');
        }
        $this->db->name('charecabinet')
            ->where(['id' => $info['id']])
            ->update(['sid' => 0, 'update_time' => time()]);
        return $this->successResponse([], '取消商户绑定');
    }


    //在线检测
    function checkOnline()
    {
        $device_id = input('device_id');
        $cabinet_id = input('id');
        $info = $this->db->name('charecabinet')
            ->where(['cabinet_id' => $device_id, 'not_delete' => 1])
            ->find();
        if (!$info) {
            $this->errorResponse(0, '机柜信息不存在');
        }
        $this->checkForbid();
        $ret = \think\Loader::model('Equipment', 'logic')->operate($info, $this->auth_info['user_type'], $this->auth_info['uid'], ['type' => 'trigger']);
        if (1 != $ret['status']) {
            $this->errorResponse(0, $ret['msg']);
        }

        return $this->successResponse(['isOnlne' => true], '在线检测');
    }


    //重启设备
    function restart()
    {
        $device_id = input('device_id');
        $info = $this->_checkInfo($device_id);
        if ($info['can_edit'] != 'true' || $info['open_lock'] != 'true') {
            return $this->errorResponse(0, '没有操作权限');
        }
        $this->checkForbid();
        $result = \think\Loader::model('Equipment', 'logic')->operate($info, $this->auth_info['user_type'], $this->auth_info['uid'], ['type' => 'restart']);
        if (1 != $result['code']) {
            return $this->errorResponse(0, $result['msg']);
        }
        return $this->successResponse([], $result['msg']);
    }


    //弹出充电宝
    function openLock()
    {
        $device_id = input('device_id');
        $lock_id = input('lock_id', 0, 'intval');
        $info = $this->_checkInfo($device_id);
        if ($info['can_edit'] != 'true' || $info['open_lock'] != 'true') {
            return $this->errorResponse(0, '没有操作权限');
        }
        $this->checkForbid();
        $result = \think\Loader::model('Equipment', 'logic')->operate($info, $this->auth_info['user_type'], $this->auth_info['uid'], ['type' => 'open', 'lock_id' => $lock_id]);
        if (1 != $result['code']) {
            return $this->errorResponse(0, $result['msg']);
        }
        return $this->successResponse([], $result['msg']);

    }


    //弹出所有充电宝
    function openAllLock()
    {
        $device_id = input('device_id');
        $info = $this->_checkInfo($device_id);
        if ($info['can_edit'] != 'true' || $info['open_lock'] != 'true') {
            return $this->errorResponse(0, '没有操作权限');
        }
        $this->checkForbid();
        $result = \think\Loader::model('Equipment', 'logic')->operate($info, $this->auth_info['user_type'], $this->auth_info['uid'], ['type' => 'openAll']);
        if (1 != $result['code']) {
            return $this->errorResponse(0, $result['msg']);
        }
        return $this->successResponse([], $result['msg']);
    }

    //设置是否故障
    function setFault()
    {
        $this->checkForbid();
        $device_id = input('device_id');
        $info = $this->_checkInfo($device_id);
        if ($info['can_edit'] != 'true') {
            return $this->errorResponse(0, '没有操作权限');
        }
        $is_fault = input('is_fault', 0, 'intval');
        $is_fault > 0 && $is_fault = 1;
        $this->db->name('charecabinet')
            ->where(['cabinet_id' => $device_id])
            ->update(['is_fault' => $is_fault, 'update_time' => time()]);
        return $this->successResponse([], '操作成功');
    }

    private function _checkInfo($device_id)
    {
        $info = $this->db->name('charecabinet')
            ->where(['not_delete' => 1, 'cabinet_id' => $device_id])
            ->find();
        if (!$info) {
            $this->error('设备不存在');
        }
        $this->checkForbid();
        $info['open_lock'] = false;//是否可开锁
        $info['can_edit'] = false;//是否可操作
        if ('agency' == $this->auth_info['role']) {//代理

            if ($info['agency_id'] == $this->auth_info['uid']) {//自己设备
                $info['can_edit'] = true;
            } else {
                $agency = $this->db->name('agency')->where(['id' => $info['agency_id']])->find();
                if (!$agency || empty($agency['parents'])) {
                    $this->error('该设备您没有权利操作');
                }
                $agency['parents'] = explode(",", $agency['parents']);
                if (!in_array($this->auth_info['uid'], $agency['parents'])) {//不是属于自己下级
                    $this->error('该设备您没有权利操作');
                }
                if ($agency['is_self'] != 1) {//如果是购机代理，则不能操作数据
                    $info['can_edit'] = true;
                }
            }
        } else if ('employee' == $this->auth_info['role']) {//业务员
            if ($info['employee_id'] != $this->auth_info['uid']) {
                $this->error('该设备您没有权利操作');
            }
            $info['can_edit'] = true;
        } else if ('seller' == $this->auth_info['role']) {//店铺管理员
            $manager_id = $this->db->name('seller')->where(['id' => $info['sid']])->value('manager_id');
            if ($manager_id != $this->auth_info['uid']) {
                $this->error('该设备您没有权利操作');
            }
            $info['can_edit'] = true;
        }
        $info['can_edit'] && $open_lock = $this->db->name('agency')->where(['id' => $this->auth_info['uid']])->value('open_lock');
        $open_lock == 1 && $info['open_lock'] = true;
        return $info;
    }
}
