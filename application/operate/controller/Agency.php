<?php

namespace app\operate\controller;

use think\Request;

/**
 * 代理商
 * @package app\operate\controller
 */
class Agency extends Common
{

    /**
     * 列表
     */
    public function index()
    {
        $page_size = input('page_size', 20, 'intval');
        $page_size < 1 && $page_size = 20;
        $where = ['not_delete' => 1];
        $pageParam = ['query' => []];

        //判断用户类型
        $type = input('type', 0, 'intval');
        if (!empty($status)) {
            $where['type'] = $type;
            $pageParam['query']['type'] = $type;
        }

        //判断用户状态
        $status = input('status', 0, 'intval');
        if (!empty($status)) {
            $where['status'] = $status;
            $pageParam['query']['status'] = $status;
        }

        //判断是否有按名查询
        $name = input('name', '', 'trim');
        if ('' != $name) {
            $where['name'] = ['LIKE', "%{$name}%"];
            $pageParam['query']['name'] = $name;
        }

        //判断手机
        $phone = input('phone', '', 'trim');
        if ('' != $phone) {
            $where['phone'] = ['LIKE', "%{$phone}%"];
            $pageParam['query']['phone'] = $phone;
        }

        $query = $this->db->name('agency')
            ->where($where)
            ->order('id desc')
            ->paginate($page_size, false, $pageParam);

        $paginate = $query->render();
        $list = $query->all();
        $total = $query->total();

        $statusList = [["id" => 1, "name" => lang('正常')], ["id" => 2, "name" => lang('禁用')]];
        $status = array_column($statusList, 'name', 'id');
        $user_type = config('user_type_name');
        foreach ($list as $k => $v) {
            $v['status'] = $status[$v['status']];
            $v['type_text'] = $user_type[$v['type']];
            unset($v['password'], $v['not_delete']);
            $list[$k] = $v;
        }
        $data = ['total' => $total, 'list' => $list];
        return $this->successResponse($data, '代理商列表');

    }


    /**
     * 代理商列表
     */
    public function agencyList()
    {
        $page_size = input('page_size', 20, 'intval');
        $data = \think\Loader::model('Agency', 'logic')->agencyList([], $page_size, true);
        return $this->successResponse($data, '代理商列表');
    }


    /**
     * 代理商列表弹框
     */
    public function agencyDialog()
    {
        $page_size = input('page_size', 20, 'intval');
        $logic = \think\Loader::model('Agency', 'logic');
        $data = $logic->agencyList([], $page_size, true);
        return $this->successResponse($data, '代理商列表');
    }

    /**
     * 添加代理商
     */
    public function agencyAdd()
    {
        $logic = \think\Loader::model('Agency', 'logic');
        if(!Request::instance()->isPost()){
            return ['code' => 0, 'msg' => lang('非法请求')];
        }
        $ret =  $logic->agencyAdd([]);
        if($ret['code'] === 0){
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }

    /**
     * 代理商详情
     */
    public function agencyDetail($id)
    {
        !Request::instance()->isPost() && $this->error(lang('非法请求'));
        $where = ['id' => $id, 'not_delete' => 1, 'type' => 1];
        $info = $this->db->name('agency')->where($where)->find();
        if (!$info) {
            return $this->error(lang('信息不存在'));
        }
        $info['parent_name'] = '';
        if ($info['parent_id']) {
            $parent = $this->db->name('agency')->where(['id' => $info['parent_id']])->find();
            $parent && $info['parent_name'] = $parent['name'];
        }
        $info['device_num'] = $this->db->name('device_agency')->where(['agency_id' => $info['id'], 'type' => 1])->count();
        $info['wired_num'] = $this->db->name('device_agency')->where(['agency_id' => $info['id'], 'type' => 2])->count();
        unset($info['password'], $info['not_delete'], $info['update_time']);
        $this->successResponse($info, '代理商详情');
    }


    /**
     * 修改代理商
     */
    public function agencyEdit($id)
    {
        !Request::instance()->isPost() && $this->error(lang('非法请求'));
        $where = ['id' => $id, 'not_delete' => 1, 'type' => 1];
        $info = $this->db->name('agency')->where($where)->find();
        if (!$info) {
            return $this->error(lang('信息不存在'));
        }
        $ret = \think\Loader::model('Agency', 'logic')->agencyEdit($info, ['id' => $id]);
        if($ret['code'] === 0){
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }


    /**
     * 删除代理商
     */
    public function agencyDelete($id)
    {
        !Request::instance()->isPost() && $this->error(lang('非法请求'));
        $ret = \think\Loader::model('Agency', 'logic')->agencyDelete($id, []);
        if($ret['code'] === 0){
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }


    /**
     * 业务员列表
     */
    public function employeeList()
    {
        $page_size = input('page_size', 20, 'intval');
        $data = \think\Loader::model('Employee', 'logic')->employeeList([], $page_size, true);
        return $this->successResponse($data, '业务员列表');
    }


    /**
     * 添加业务员
     */
    public function employeeAdd()
    {
        !Request::instance()->isPost() && $this->error(lang('非法请求'));
        return \think\Loader::model('Employee', 'logic')->employeeAdd([]);
    }

    /**
     * 业务员详情
     */
    public function employeeDetail($id)
    {
        !Request::instance()->isPost() && $this->error(lang('非法请求'));
        $where = ['id' => $id, 'not_delete' => 1, 'type' => 2];
        $info = $this->db->name('agency')->where($where)->find();
        if (!$info) {
            return $this->error(lang('信息不存在'));
        }
        $info['agency_name'] = '';
        $info['max_brokerage'] = 100;
        if ($info['parent_id']) {
            $agency = $this->db->name('agency')->where(['id' => $info['parent_id'], 'type' => 1])->find();
            $agency && $info['agency_name'] = $agency['name'];
            $info['max_brokerage'] = $agency['brokerage'];
        }
        unset($info['password'], $info['not_delete'], $info['update_time']);
        $this->successResponse($info, '业务员详情');
    }


    /**
     * 修改业务员
     */
    public function employeeEdit($id)
    {
        !Request::instance()->isPost() && $this->error(lang('非法请求'));
        $where = ['id' => $id, 'not_delete' => 1, 'type' => 2];
        $info = $this->db->name('agency')->where($where)->find();
        if (!$info) {
            return $this->error(lang('信息不存在'));
        }
        $ret =  \think\Loader::model('Employee', 'logic')->employeeEdit($info, ['id' => $id]);
        if($ret['code'] === 0){
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }


    /**
     * 删除业务员
     */
    public function employeeDelete($id)
    {
        !Request::instance()->isPost() && $this->error(lang('非法请求'));
        $ret =  \think\Loader::model('Employee', 'logic')->employeeDelete($id);
        if($ret['code'] === 0){
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }


    /**
     * 店铺管理员列表
     */
    public function sellerList()
    {
        $page_size = input('page_size', 20, 'intval');
        $data = \think\Loader::model('ShopManager', 'logic')->managerList([], $page_size, true);
        return $this->successResponse($data, '商户管理员列表');
    }


    /**
     * 添加店铺管理员
     */
    public function sellerAdd()
    {
        !Request::instance()->isPost() && $this->error(lang('非法请求'));
        $ret =  \think\Loader::model('ShopManager', 'logic')->add([]);
        if($ret['code'] === 0){
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
        
    }

    /**
     * 商户管理详情
     */
    public function sellerDetail($id)
    {
        !Request::instance()->isPost() && $this->error(lang('非法请求'));
        $where = ['id' => $id, 'not_delete' => 1, 'type' => 3];
        $info = $this->db->name('agency')->where($where)->find();
        if (!$info) {
            return $this->error(lang('信息不存在'));
        }
        $info['agency_name'] = '';
        $info['max_brokerage'] = 100;
        $info['employee_brokerage'] = 0;
        if ($info['parent_id']) {
            $agency = $this->db->name('agency')->where(['id' => $info['parent_id'], 'type' => 1])->find();
            $agency && $info['agency_name'] = $agency['name'];
            $info['max_brokerage'] = $agency['brokerage'];
        }
        if (!empty($info['employee_id'])) {
            $employee = $this->db->name('agency')->where(['id' => $info['employee_id'], 'type' => 2])->find();
            $info['employee_brokerage'] = intval($employee['brokerage']);
        }
        unset($info['password'], $info['not_delete'], $info['update_time']);
        $this->successResponse($info, '商户管理员详情');
    }


    /**
     * 修改店铺管理员
     */
    public function sellerEdit()
    {
        !Request::instance()->isPost() && $this->error(lang('非法请求'));
        $id = input('id', 0, 'intval');
        $logic = \think\Loader::model('ShopManager', 'logic');
        $where = ['id' => $id, 'not_delete' => 1, 'type' => 3];
        $info = $this->db->name('agency')->where($where)->find();
        if (!$info) {
            return $this->error(lang('信息不存在'));
        }
        $ret =  $logic->edit($info, ['id' => $id]);
        if($ret['code'] === 0){
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }

    /**
     * 删除业务员
     */
    public function sellerDelete($id)
    {
        !Request::instance()->isPost() && $this->error(lang('非法请求'));
        $ret =  \think\Loader::model('ShopManager', 'logic')->delete($id);
        if($ret['code'] === 0){
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }


    /**
     * 管理员列表弹框
     */
    public function sellerDialog()
    {
        $page_size = input('page_size', 20, 'intval');
        $data = \think\Loader::model('ShopManager', 'logic')->managerList([], $page_size, true);
        return $this->successResponse($data, '商户管理员列表');
    }


    //代理商机柜
    function agencyDevice()
    {
        $agency_id = input('agency_id', 0, 'intval');
        $page_size = input('page_size', 20, 'intval');
        $data = \think\Loader::model('Equipment', 'logic')->agencyDevice($agency_id, $page_size);
        return $this->successResponse($data, '机柜列表');
    }

    //代理商商户
    function agencySeller()
    {
        $agency_id = input('agency_id', 0, 'intval');
        $page_size = input('page_size', 20, 'intval');
        $page_size < 1 && $page_size = 20;
        if(empty($agency_id)){
            $this->errorResponse(0,lang('代理不存在'));
        }
        $where = ['s.agency_id' => $agency_id, 'a.not_delete' => 1];
        //判断用户状态
        $status = input('status', 0, 'intval');
        if (!empty($status)) {
            $where['a.status'] = $status;
        }
        //判断是否有按店铺名查询
        $name = input('name', '', 'trim');
        if ('' != $name) {
            $where['a.name'] = ['LIKE', "%{$name}%"];
        }

        //判断区域查询
        $area = input('area', '', 'trim');
        if ('' != $area) {
            $where['a.area'] = ['LIKE', "%{$area}%"];
        }
        $query = $this->db->name('seller_agency')
            ->alias('s')
            ->join('seller a', 's.sid=a.id', 'LEFT')
            ->where($where)
            ->field('a.*')
            ->order('a.id desc')
            ->paginate($page_size);
        //echo $this->db->name('seller_agency')->getlastsql(); exit;
        $paginate = $query->render();
        $list = $query->all();

        if($list){
            $statusList = [["id" => 1, "name" => lang('正常')], ["id" => 2, "name" => lang('禁用')]];
            $status = array_column($statusList, 'name', 'id');
            $model = \think\Loader::model('Agency');
            $allAgency = $model->allAgency();
            $employee_ids = array_column($list, 'employee_id');
            $employees = $this->db->name('agency')
                ->field('id,name')
                ->where(['not_delete' => 1, 'type' => 2, 'id' => ['IN', $employee_ids]])
                ->select();
            $employees = array_column($employees, 'name', 'id');
            foreach ($list as $k => $v) {
                $v['status'] = $status[$v['status']];
                $v['agency'] = '';
                if (!empty($v['agency_id'])) {
                    $parent = $model->getParents($allAgency, $v['agency_id']);
                    $parent = array_column($parent, 'name');
                    $v['agency'] = implode(" > ", $parent);
                }
                $v['employee_name'] = isset($employees[$v['employee_id']]) ? $employees[$v['employee_id']] : '';
                $list[$k] = $v;
            }
        }


        $total = $query->total();
        return $this->successResponse(['total' => $total, 'list' => $list], '商户列表');
    }

}
