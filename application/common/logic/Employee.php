<?php

namespace app\common\logic;

use think\Request;

/**
 * 业务员
 * @package app\common\logic
 */
class Employee extends Common
{

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 添加
     */
    public function employeeAdd($data = [])
    {

        $params = $this->_getParams($data);
        $validate = \think\Loader::validate('Employee');
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
                ['user_type' => config('user_type.employee'), 'relation_id' => $id, 'create_time' => time()]
            );
            $this->operateLog($id, '添加业务员');
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            save_log('sql', $e->getMessage());
            return ['code' => 0, 'msg' =>lang('操作失败')];
        }
        return ['code' => 1, 'msg' => lang('操作成功')];
    }

    private function _getParams($data = [])
    {
        $params = [
            'type' => 2,
            'name' => input('post.name'),
            'phone' => input('post.phone'),
            'password' => input('post.password'),
            'parent_id' => input('post.parent_id', 0, 'intval'),
            'brokerage' => input('post.brokerage', '', 'trim'),
            'open_lock' => input('post.open_lock', 0, 'intval'),
            'status' => input('post.status', 0, 'intval'),
        ];
        $params['parent_id'] < 1 && $params['parent_id'] = 0;
        return array_merge($params, $data);

    }

    /**
     * 修改
     */
    public function employeeEdit($info, $data = [])
    {
        if (!$info) {
            return ['code' => 0, 'msg' => lang('信息不存在')];
        }
        $data['id'] = $info['id'];
        $params = $this->_getParams($data);
        $validate = \think\Loader::validate('Employee');
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
        $params['parent_id'] = $info['parent_id'];//不能修改上级归属
        //$parents = \think\Loader::model('Agency', 'logic')->getParentIds($params['parent_id']);
        //$params['parents'] = implode(",", $parents);

        if ($this->db->name('agency')->update($params)!== false) {
            $this->operateLog($info['id'], '修改业务员');
            ($params['status'] != 1) && \think\Loader::model('Agency', 'logic')->offline($info['id']);
            return ['code' => 1, 'msg' => lang('操作成功')];
        }
        return ['code' => 0, 'msg' => lang('操作失败')];
    }


    /**
     * 删除
     * @param $id 主键id
     * @param array $condition 查找条件
     * @return array|void
     */
    public function employeeDelete($id, $condition = [])
    {
        $where = ['id' => $id, 'not_delete' => 1, 'type' => 2];
        $where = array_merge($where, $condition);
        $info = $this->db->name('agency')->where($where)->find();
        if (!$info) {
            return $this->error(lang('信息不存在'));
        }
        $check = \think\Loader::model('Agency', 'logic')->deleteAccount($id);
        if (1 != $check['code']) {
            return $this->error($check['msg']);
        }

        // 启动事务
        $this->db->startTrans();
        try {

            $this->db->name('agency')->update(['id' => $id, 'not_delete' => 0, 'update_time' => time()]);
            //商户
            $this->db->name('seller')->where(['employee_id' => $id])->update(['employee_id' => 0, 'employee_brokerage' => 0, 'update_time' => time()]);
            //设备
            $this->db->name('charecabinet')->where(['employee_id' => $id, 'not_delete' => 1])->update(['employee_id' => 0, 'update_time' => time()]);
            $this->db->name('wired_device')->where(['employee_id' => $id, 'not_delete' => 1])->update(['employee_id' => 0, 'update_time' => time()]);
            //管理员
            $this->db->name('agency')->where(['employee_id' => $id, 'type' => 3, 'not_delete' => 1])->update(['employee_id' => 0, 'update_time' => time()]);

            $this->operateLog($id, '删除业务员' );
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            save_log('sql', $e->getMessage());
            return ['code' => 0, 'msg' => lang('操作失败')];
        }
        \think\Loader::model('Agency', 'logic')->offline($id);
        return ['code' => 1, 'msg' => lang('操作成功')];


    }


    /**
     * 列表
     */
    public function employeeList($condition = [], $pages = 20, $isReturn = false)
    {
        $pages = intval($pages);
        $pages < 1 && $pages = 20;
        $where = ['not_delete' => 1, 'type' => 2];
        $pageParam = ['query' => []];

        $model = \think\Loader::model('Agency');
        $allAgency = $model->allAgency();

        //判断用户状态
        $status = input('status', 0, 'intval');
        if (!empty($status)) {
            $where['status'] = $status;
            $pageParam['query']['status'] = $status;
        }

        //代理
        $agency = input('agency', 0, 'intval');
        if (!empty($agency)) {
            $sub = $model->getSubs($allAgency, $agency);
            $sub = array_column($sub, 'id');
            $sub[] = $agency;
            $where['parent_id'] = ['IN', $sub];
            $pageParam['query']['agency'] = $agency;
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

        foreach ($list as $k => $v) {
            $v['status'] = $status[$v['status']];
            $v['agency_name'] = '';
            if (!empty($v['parent_id'])) {
                $parent = $model->getParents($allAgency, $v['parent_id']);
                $parent = array_column($parent, 'name');
                $v['agency_name'] = implode(" > ", $parent);
            }
            unset($v['password'], $v['not_delete']);
            $list[$k] = $v;
        }
        if ($isReturn) {
            return ['total' => $total, 'list' => $list];
        }

        //有查询则赋值
        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
        $this->assign('statusList', $statusList);
        $this->assign('list', $list);
        $this->assign('paginate', $paginate);
        $this->assign('title', lang('业务员列表'));
    }

}