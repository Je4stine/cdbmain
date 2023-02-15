<?php
/*
 * @Author: your name
 * @Date: 2020-10-20 16:47:34
 * @LastEditTime: 2020-11-19 11:32:43
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \malta\application\operate\controller\Common.php
 */

namespace app\operate\controller;

use think\Request;
use think\Db;
use think\Session;
use think\Cookie;
use think\Controller;
use think\Config;
use Godok\Org\Auth;

use app\common\service\Base;

class Common extends Base
{

    public function _initialize()
    {
        parent::_initialize();
        $this->_checkAuth();
    }


    private function _checkAuth()
    {
        $controller = strtolower(Request::instance()->controller());
        if (in_array($controller, ['auth', 'test'])) {
            return true;
        }

        $token = Request::instance()->header('token');
        //$token = '1234567890';
        $cache = cache("admin-token:{$this->oid}-{$token}");
        if (!$cache) {
            json(['code' => 401, 'msg' => '请先登录'])->send();
            exit;
        }
        //单点登录验证
        $unique = cache("admin-login:{$this->oid}-{$cache['uid']}");
        if (!$unique || $unique != $token) {
            json(['code' => 401, 'msg' => '请先登录'])->send();
            exit;
        }

       //搜索json串使用
        $lang_data = config('lang_data');
        $lang = cookie('think_var');
        if(!$lang || !isset($lang_data[$lang])){
            $lang = config('lang_default');
        }
        $this->lang = $lang_data[$lang]['short'];
        
        $cache['time'] = time();
        $this->auth_info = $cache;
        cache("admin-token:{$this->oid}-{$token}", $cache, 300 * 60);
        cache("admin-login:{$this->oid}-{$cache['uid']}", $token, 300 * 60);
        return true;
    }

}



