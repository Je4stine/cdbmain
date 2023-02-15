<?php

namespace app\common\logic;

/**
 * 管理员
 * @package app\common\logic
 */
class Admin extends Common
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function groupAdd()
    {
        $params = $this->_getGroupParams();
        if(isset($params['code'])){
            return ['code' =>0, 'msg' => $params['msg']];
        }
        if ($id = $this->db->name('auth_groups')->insertGetId($params)) {
            $this->operateLog($id, '添加系统角色');
            return ['code' => 1, 'msg' => lang('操作成功')];
        }
        return ['code' => 0, 'msg' => lang('操作失败')];
    }

    private function _getGroupParams($data = [])
    {
        $params = [
            'title' => input('post.title'),
            'description' => input('post.description'),
        ];
        if ($params['title'] == '') {
            return ['code' => 0, 'msg' => '请输入角色名称'];
        }
        $log = $this->db->name('auth_groups')->where(['title' => $params['title'], 'not_delete' => 1])->find();
        if ($log && $log['id'] == $data['id']) {
            $log = null;
        }
        if($log){
            return ['code' => 0, 'msg' => '角色已存在'];
        }
        return array_merge($params, $data);
    }

    public function groupEdit($info)
    {
        $params = $this->_getGroupParams(['id' => $info['id']]);
        if(isset($params['code'])){
            return ['code' =>0, 'msg' => $params['msg']];
        }
        if ($this->db->name('auth_groups')->update($params) !== false) {
            $this->operateLog($info['id'], '修改系统角色');
            return ['code' => 1, 'msg' => lang('操作成功')];
        }
        return ['code' => 0, 'msg' => lang('操作失败')];
    }

    public function groupCheck($id)
    {
        $info = $this->db->name('auth_groups')->where(['not_delete' => 1, 'id' => $id])->find();
        if(!$info){
            return ['code' => 0, 'msg' => '系统角色不存在'];
        }
        if($info['is_system']){
            return ['code' => 0, 'msg' => '禁止操作'];
        }
        return $info;
    }

    public function userAdd()
    {
        $params = $this->_getUserParams();
        if(isset($params['code'])){
            return ['code' =>0, 'msg' => $params['msg']];
        }
        if ($id = $this->db->name('operator_users')->insertGetId($params)) {
            $this->operateLog($id, '添加管理员');
            return ['code' => 1, 'msg' => lang('操作成功')];
        }
        return ['code' => 0, 'msg' => lang('操作失败')];
    }

    private function _getUserParams($data = [])
    {
        $password = input('post.password');
        $params = [
            'username' => input('post.username'),
            'name' => input('post.name'),
            'phone' => input('post.phone'),
            'status' => input('post.status', 0, 'intval'),
        ];
        if ($params['username'] == '') {
            return ['code' => 0, 'msg' => '请输入用户名'];
        }
        if (empty($data['id'])) {
            if('' == $password){
                return ['code' => 0, 'msg' => '请输入密码'];
            }
        }
        if ($password != '') {
            if(strlen($password) < 6){
                return ['code' => 0, 'msg' => '密码至少六位数'];
            }
            $params['password'] = md123($password);
        }
        if (empty($params['status']) || !in_array($params['status'], [1, 2])) {
            return ['code' => 0, 'msg' => '请选择状态'];
        }
        $log = $this->db->name('operator_users')->where(['username' => $params['title'], 'not_delete' => 1])->find();
        if ($log && $log['id'] == $data['id']) {
            $log = null;
        }
        if($log){
            return ['code' => 0, 'msg' => '用户名已存在'];
        }
        return array_merge($params, $data);
    }

    public function userEdit($info)
    {
        $params = $this->_getUserParams(['id' => $info['id']]);
        if(isset($params['code'])){
            return ['code' =>0, 'msg' => $params['msg']];
        }
        if ($this->db->name('operator_users')->update($params) !== false) {
            $this->operateLog($info['id'], '修改管理员');
            return ['code' => 1, 'msg' => lang('操作成功')];
        }
        return ['code' => 0, 'msg' => lang('操作失败')];
    }

    public function userCheck($id)
    {
        $info = $this->db->name('operator_users')->where(['not_delete' => 1, 'id' => $id])->find();
        if(!$info){
            return ['code' => 0, 'msg' => '管理员不存在'];
        }
        if($id == 1){
            return ['code' => 0, 'msg' => '超级管理员禁止操作'];
        } 
        return $info;
    }


}