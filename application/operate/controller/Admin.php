<?php

namespace app\operate\controller;

class Admin extends Common
{
    public function _initialize()
    {
        parent::_initialize();
        $this->logic = \think\Loader::model('Admin', 'logic');
    }

    public function groupList()
    {
        $list = $this->db->name('auth_groups')->where(['not_delete' => 1])->order("id DESC")->select();
        $this->successResponse($list, lang('系统角色'));
    }

    public function groupAdd()
    {
        $ret =  $this->logic->groupAdd();
        if ($ret['code'] === 0) {
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }

    public function groupEdit()
    {
        $id = input('id', 'intval', 0);
        $info = $this->logic->groupCheck($id);
        if(isset($info['code'])){
            $this->errorResponse(0, $info['msg']);
        }
        $ret =  $this->logic->groupEdit($info);
        if ($ret['code'] === 0) {
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }

    public function groupDetail()
    {
        $id = input('id', 2, 'intval');
        $info = $this->logic->groupCheck($id);
        if(isset($info['code'])){
            $this->errorResponse(0, $info['msg']);
        }
        $result = \think\Loader::model('Auth', 'logic')->getAllNodes($id);
        $info['permissions'] = $result['ids'];
        $info['tree'] = $result['nodes'];
        unset($info['rules']);
        $this->successResponse($info, '系统角色');
    }

    public function groupRule()
    {
        $id = input('id', 2, 'intval');
        $info = $this->logic->groupCheck($id);
        if(isset($info['code'])){
            $this->errorResponse(0, $info['msg']);
        }
        $rules = input('post.rule/a', []);
        if (!is_array($rules) || empty($rules)) {
            return $this->errorResponse(0, lang('请选择权限'));
        }
        if ($this->db->name('auth_groups')->where(['id' => $id])->update(['rules' => implode(",", $rules)]) !== false) {
            $this->operateLog($id, '设置角色权限');
            $this->successResponse([], lang('操作成功'));
        }
        $this->errorResponse(0, lang('操作失败'));
    }

    public function groupDelete()
    {
        $id = input('id', 0, 'intval');
        $info = $this->logic->groupCheck($id);
        if(isset($info['code'])){
            $this->errorResponse(0, $info['msg']);
        }
        if ($this->db->name('auth_groups')->where(['id' => $id])->update(['not_delete' => 0]) !== false) {
            $this->operateLog($id, '删除系统角色');
            $this->successResponse([], lang('操作成功'));
        }
        $this->errorResponse(0, lang('操作失败'));
    }


    public function userAdd()
    {
        $ret =  $this->logic->userAdd();
        if ($ret['code'] === 0) {
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }

    public function userDetail()
    {
        $id = input('id', 3, 'intval');
        $info = $this->db->name('operator_users')->where(['not_delete' => 1, 'id' => $id])->find();
        !$info && $this->error(lang('管理员不存在'));
        $info['auth_groups'] = empty($info['auth_groups']) ? [] : explode(",", $info['auth_groups']);
        $data = [
            'id' => $info['id'],
            'username' => $info['username'],
            'name' => $info['name'],
            'phone' => !empty($info['phone']) ? $info['phone'] : '',
            'status' => $info['status'],
            'last_login_ip' => $info['last_login_ip'],
            'last_login_time' => empty($info['last_login_time']) ? '' : date("Y-m-d H:i:s", $info['last_login_time']),
            'auth_groups' => $info['auth_groups'],
            'roles' => [],
        ];
        !empty($info['auth_groups']) && $data['roles'] = $this->db->name('auth_groups')->where(['id' => ['IN', $info['auth_groups']], 'not_delete' => 1])->column('title');
        $data['groups'] = $this->db->name('auth_groups')->field('id,title,description')->where(['not_delete' => 1])->order('id asc')->select();
        $this->successResponse($data, lang('管理员'));
    }

    public function userEdit()
    {
        $id = input('id', 'intval', 0);
        $info = $this->logic->userCheck($id);
        if(isset($info['code'])){
            $this->errorResponse(0, $info['msg']);
        }
        $ret =  $this->logic->userEdit($info);
        if ($ret['code'] === 0) {
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }

    public function userDelete()
    {
        $id = input('id', 0, 'intval');
        $info = $this->logic->userCheck($id);
        if(isset($info['code'])){
            $this->errorResponse(0, $info['msg']);
        }
        if ($this->db->name('operator_users')->where(['id' => $id])->update(['not_delete' => 0]) !== false) {
            $this->operateLog($id, '删除管理员');
            $this->successResponse([], lang('操作成功'));
        }
        $this->errorResponse(0, lang('操作失败'));
    }

    public function userRule()
    {
        $id = input('id', 0, 'intval');
        $info = $this->logic->userCheck($id);
        if(isset($info['code'])){
            $this->errorResponse(0, $info['msg']);
        }
        $rules = input('post.rule/a', []);
        if (!is_array($rules) || empty($rules)) {
            return $this->errorResponse(0, lang('请选择角色'));
        }
        if ($this->db->name('operator_users')->where(['id' => $id])->update(['auth_groups' => implode(",", $rules)]) !== false) {
            $this->operateLog($id, '设置管理员权限');
            $this->successResponse([], lang('操作成功'));
        }
        $this->errorResponse(0, lang('操作失败'));
    }

    public function userList()
    {
        $list = $this->db->name('operator_users')->field('id,username,name,phone')->where(['not_delete' => 1])->order("id DESC")->select();
        $this->successResponse($list, lang('管理员列表'));
    }
}