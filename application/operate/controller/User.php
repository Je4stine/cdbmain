<?php

namespace app\operate\controller;

//用户信息修改，登录用户都可操作
class User extends Common
{
    //用户详情
    public function detail()
    {
        $user = $this->db->name('operator_users')->field('id,username,name,phone,avatar')->where(['id' => $this->auth_info['id']])->find();
        $user['avatar'] = !empty($user['avatar']) ? config('site.resource_url') . $user['avatar'] : config('site.resource_url') . 'images/a1.png';
        $this->successResponse($user, '用户信息');
    }

    //修改密码
    public function password()
    {
        $old_password = input('old_password');
        $password = input('password');
        $repassword = input('repassword');
        '' == $old_password && $this->error(lang('请输入旧密码'));
        '' == $password && $this->error(lang('请输入新密码'));
        strlen($password) < 6 && $this->error(lang('密码至少六位数'));
        $password != $repassword && $this->error(lang('两次密码不一致'));
        $password = md123($password);
        $user = $this->db->name('operator_users')->where(['id' => $this->auth_info['id']])->find();
        md123($old_password) != $user['password'] && $this->error(lang('旧密码错误'));
        if ($this->db->name('operator_users')->where(['id' => $this->auth_info['id']])->update(['password' => $password]) === false) {
            return $this->errorResponse(0, lang('操作失败'));
        }
        $this->operateLog($this->auth_info['id'], '修改密码');
        return $this->successResponse([], lang('操作成功'));
    }

    //修改资料
    public function profile()
    {
        $params = [
            'name' => input('name'),
            'phone' => input('phone'),
        ];
        '' == $params['name'] && $this->error(lang('请输入姓名'));
        if ($this->db->name('operator_users')->where(['id' => $this->auth_info['id']])->update($params) === false) {
            return $this->errorResponse(0, lang('操作失败'));
        }
        $this->operateLog($this->auth_info['id'], '修改资料');
        return $this->successResponse([], lang('操作成功'));
    }

    //修改头像
    public function avatar()
    {
        $image = input('image', 2079, 'intval');
        $log = $this->db->name('upload_files')->where(['id' => $image])->find();
        !$log && $this->error(lang('图片上传失败'));
        $image = ROOT_PATH . 'public' . DS . 'uploads' . DS . $log['path'];
        $image = str_replace("\\", "/", $image);
        $info = pathinfo($image);
        $avatar =  'images' . DS . 'avatar' . DS . time() . rand(1000, 9999) . "." . $info['extension'];
        $avatar = str_replace("\\", "/", $avatar);
        if (!copy($image, ROOT_PATH . 'public' .DS . 'statics' . DS . $avatar)) {
            return ['code' => 0, 'msg' => lang('上传失败')];
        }
        if ($this->db->name('operator_users')->where(['id' => $this->auth_info['id']])->update(['avatar' => $avatar]) === false) {
            return $this->errorResponse(0, lang('操作失败'));
        }
        $this->operateLog($this->auth_info['id'], '上传头像');
        return $this->successResponse([], lang('操作成功'));
    }

}
