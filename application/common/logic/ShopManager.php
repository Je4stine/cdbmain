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
 * 店铺管理员
 * @package app\common\logic
 */
class ShopManager extends Common
{

    public function _initialize()
    {
        parent::_initialize();
    }


    private function _getParams($data = [])
    {
        $params = [
            'type' => 3,
            'name' => input('post.name'),
            'phone' => input('post.phone'),
            'password' => input('post.password'),
            'parent_id' => input('post.parent_id', 0, 'intval'),
            'employee_id' => input('post.employee_id', 0, 'intval'),
            'brokerage' => input('post.brokerage', '', 'trim'),
            'open_lock' => input('post.open_lock', 0, 'intval'),
            'status' => input('post.status', 0, 'intval'),
            'is_vip' => input('post.is_vip', 0, 'intval'),
        ];
        $params['parent_id'] < 1 && $params['parent_id'] = '0';//平台自营
        return array_merge($params, $data);

    }

    /**
     * 添加
     */
    public function add($data = [])
    {

        $params = $this->_getParams($data);
        $validate = \think\Loader::validate('ShopManager');
        if (!$validate->check($params)) {
            $this->error(lang($validate->getError()));
        }
        $params['password'] = Request::instance()->post('password', '', 'Godok\Org\Filter::password');
        if (empty($params['password'])) {
            return ['code' => 0, 'msg' => lang('密码至少6位数')];
        }

        $params['password'] = md123($params['password']);
        $params['create_time'] = time();
        $parents = \think\Loader::model('Agency', 'logic')->getParentIds($params['parent_id']);
        $params['parents'] = implode(",", $parents);


        $this->db->startTrans();
        try {
            $id = $this->db->name('agency')->insertGetId($params);
            $this->db->name('account')->insert(
                ['user_type' => config('user_type.seller'), 'relation_id' => $id, 'create_time' => time()]
            );
            $this->operateLog($id, '添加店铺管理员');
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            save_log('sql', $e->getMessage());
            return ['code' => 0, 'msg' => lang('添加店铺管理员信息失败')];
        }
        return ['code' => 1, 'msg' => lang('添加店铺管理员信息成功')];
    }


    /**
     * 修改
     */
    public function edit($info, $data = [])
    {
        if (!$info) {
            return ['code' => 0, 'msg' => lang('店铺管理员信息不存在！')];
        }
        $data['id'] = $info['id'];
        $params = $this->_getParams($data);
        $validate = \think\Loader::validate('ShopManager');
        if (!$validate->scene('edit')->check($params)) {
            $this->error(lang($validate->getError()));
        }

        if ('' != $params['password']) {
            $params['password'] = Request::instance()->post('password', '', 'Godok\Org\Filter::password');
            if (empty($params['password'])) {
                return ['code' => 0, 'msg' => lang('密码至少6位数')];
            }
            $params['password'] = md123($params['password']);
        } else {
            unset($params['password']);
        }

        $params['update_time'] = time();
        $params['id'] = $info['id'];
        $parents = \think\Loader::model('Agency', 'logic')->getParentIds($params['parent_id']);
        $params['parents'] = implode(",", $parents);

        //下属店铺
        $sids = $this->db->name('seller')->where(['manager_id' => $info['id'], 'not_delete' => 1])->column('id');

        $this->db->startTrans();
        try {
            $this->db->name('agency')->update($params);
            if($sids){
                //更换了代理商、业务员
                $this->db->name('seller')->where(['id' => ['IN', $sids]])->update(['agency_id' => $params['parent_id'],'employee_id' => $params['employee_id'], 'update_time' => time()]);
                //更换了代理商，解绑设备
                if($info['parent_id'] != $params['parent_id']){
                    $this->db->name('charecabinet')->where(['agency_id' => $info['parent_id'],'sid' => ['IN', $sids]])->update(['sid' => 0, 'update_time' => time()]);
                    $this->db->name('wired_device')->where(['agency_id' => $info['parent_id'],'sid' => ['IN', $sids]])->update(['sid' => 0, 'update_time' => time()]);
                }
                //更换了业务员，解绑设备
                else if ($info['employee_id'] != $params['employee_id']) {
                    $this->db->name('charecabinet')->where(['employee_id' => $info['employee_id'], 'sid' => ['IN', $sids]])->update(['sid' => 0, 'update_time' => time()]);
                    $this->db->name('wired_device')->where(['employee_id' => $info['employee_id'], 'sid' => ['IN', $sids]])->update(['sid' => 0, 'update_time' => time()]);
                }
            }

            $this->operateLog($info['id'], '修改店铺管理员');
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            save_log('sql', $e->getMessage());
            return ['code' => 0, 'msg' => lang('修改店铺管理员信息失败')];
        }
        ($params['status'] != 1) && \think\Loader::model('Agency', 'logic')->offline($info['id']);
        return ['code' => 1, 'msg' => lang('修改店铺管理员信息成功')];
    }


    /**
     * 删除
     * @param $id 主键id
     * @param array $condition 查找条件
     * @return array|void
     */
    public function delete($id, $condition = [])
    {
        $where = ['id' => $id, 'not_delete' => 1, 'type' => 3];
        $where = array_merge($where, $condition);
        $info = $this->db->name('agency')->where($where)->find();
        if (!$info) {
            return $this->error(lang('店铺管理员信息不存在'));
        }

        $seller = $this->db->name('seller')->where(['manager_id' => $id, 'not_delete' => 1])->count();
        if ($seller > 0) {
            return $this->error(lang('店铺管理员存在'.$seller.lang('个商户，请先删除')));
        }
        $check = \think\Loader::model('Agency', 'logic')->deleteAccount($id);
        if (1 != $check['code']) {
            return $this->error($check['msg']);
        }
        // 启动事务
        $this->db->startTrans();
        try {
            $this->db->name('agency')->update(['id' => $id, 'not_delete' => 0, 'parent_id' => 0, 'parents' => '', 'employee_id' => 0, 'update_time' => time()]);
            $querystring = lang('删除店铺管理员') . $id . lang('信息');
            $this->operateLog($id, '删除店铺管理员');
            // 提交事务
            $this->db->commit();
        } catch (\Exception $e) {
            save_log('sql', $e->getMessage());
            $this->db->rollback();
            return ['code' => 0, 'msg' => lang('删除店铺管理员失败')];
        }
        \think\Loader::model('Agency', 'logic')->offline($id);
        return ['code' => 1, 'msg' =>lang( '删除店铺管理员成功')];


    }


    /**
     * 列表
     */
    public function managerList($condition = [], $pages = 20, $isReturn = false)
    {
        $where = ['not_delete' => 1, 'type' => 3];
        $pageParam = ['query' => []];

        //判断用户状态
        $status = input('status', 0, 'intval');
        if (!empty($status)) {
            $where['status'] = $status;
            $pageParam['query']['status'] = $status;
        }

        //代理商
        $agency_id = input('agency_id', 0, 'intval');
        isset($condition['agency_id']) && $agency_id = $condition['agency_id'];
        if (!empty($agency_id)) {
            $pageParam['query']['agency_id'] = $agency_id;
            $pageParam['query']['agency_name'] = input('agency_name');
            ($agency_id == '-1') && $agency_id = 0;//直营
            $where['parent_id'] = $agency_id;
        }

        //业务员
        $employee_id = input('employee_id', 0, 'intval');
        isset($condition['employee_id']) && $employee_id = $condition['employee_id'];
        if (!empty($employee_id)) {
            $where['employee_id'] = $employee_id;
            $pageParam['query']['employee_id'] = $employee_id;
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
            ->paginate($pages, false, $pageParam);

        $paginate = $query->render();
        $list = $query->all();
        $total = $query->total();

        $statusList = [["id" => 1, "name" => lang('正常')], ["id" => 2, "name" => lang('禁用')]];
        $status = array_column($statusList, 'name', 'id');

        if ($list) {
            $employee_ids = array_column($list, 'employee_id');
            $employees = $this->db->name('agency')
                ->field('id,name')
                ->where(['not_delete' => 1, 'type' => 2, 'id' => ['IN', $employee_ids]])
                ->select();
            $employees = array_column($employees, 'name', 'id');


            $model = \think\Loader::model('Agency');
            $allAgency = $model->allAgency();

            foreach ($list as $k => $v) {
                $v['status'] = $status[$v['status']];
                $v['agency_name'] = '';
                if (!empty($v['parent_id'])) {
                    $parent = $model->getParents($allAgency, $v['parent_id']);
                    $parent = array_column($parent, 'name');
                    $v['agency_name'] = implode(" > ", $parent);
                    $v['employee_name'] = isset($employees[$v['employee_id']]) ? $employees[$v['employee_id']] : '';
                }
                $list[$k] = $v;
            }
        }

        if ($isReturn) {
            return ['total' => $total, 'list' => $list];
        }

        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
        $this->assign('statusList', $statusList);
        $this->assign('list', $list);
        $this->assign('paginate', $paginate);
        $this->assign('title', lang( '店铺管理员列表'));
    }

}