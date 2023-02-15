<?php

namespace app\agency\controller;

use think\Controller;
use think\Request;
use app\common\service\Base;

class Common extends Base
{
    var $app_key;


    public function _initialize()
    {
        parent::_initialize();
        $controller =  strtolower(Request::instance()->controller()) ;
        $action = strtolower(Request::instance()->action());
        if('auth' ==  $controller &&  in_array($action,['captcha','version','logout','wechatsign'])){
            return true;
        }

        $this->app_key = $this->getOperatorConfig('merchant_secret');
        $this->_checkAuth();
        //搜索json串使用
        $lang_data = config('lang_data');
        $lang = cookie('think_var');
        if(!$lang || !isset($lang_data[$lang])){
            $lang = config('lang_default');
        }
        $this->lang = $lang_data[$lang]['short'];
    }


    /**
     * 验证hash
     */
    public function verifyHash()
    {
        return true;
        $time = Request::instance()->header('time');
        $hash = Request::instance()->header('hash');
        $md5 = md5($this->app_key . $time);
        if ($md5 == $hash) {
            return true;
        }
        return false;
    }


    private function _checkAuth()
    {

        $controller =  strtolower(Request::instance()->controller()) ;
        if ('auth' == $controller ) {
            return true;
        }
        $check = $this->verifyHash();
        if (!$check) {
            json(['code' => 403, 'msg' => "非法请求{$controller}" ])->send();
            exit;
        }

        $token = Request::instance()->header('token');
        //$token = '1234567890';
        $cache = cache("merchant-token:{$this->oid}-{$token}");
        if (!$cache) {
            json(['code' => 401, 'msg' => '请先登录'])->send();
            exit;
        }
        //单点登录验证
        $unique = cache("merchant-login:{$this->oid}-{$cache['uid']}");
        if (!$unique || $unique != $token) {
            json(['code' => 401, 'msg' => '请先登录'])->send();
            exit;
        }
        $cache['time'] = time();
        $this->auth_info = $cache;
        cache("merchant-token:{$this->oid}-{$token}", $cache, 30 * 86400);
        cache("merchant-login:{$this->oid}-{$cache['uid']}", $token, 30 * 86400);
        return true;
    }

    function checkForbid()
    {
        if ($this->auth_info['status'] != 1) {//禁止
            return $this->errorResponse(0, '账号已被禁止操作，如有疑问请联系上级代理或系统管理员');
        }
        return true;
    }

}
