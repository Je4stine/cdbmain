<?php

namespace app\common\logic;

use think\Request;
use think\Db;
use think\Session;
use think\Cookie;
use think\Controller;
use think\Config;

use app\common\service\Base;

class Common extends Base
{
    var $active_url = '';
    var $lang;




    public function _initialize()
    {
        parent::_initialize();
        $module = strtolower(Request::instance()->module());
        //搜索json串使用
        $lang_data = config('lang_data');
        $lang = cookie('think_var');
        if(!$lang || !isset($lang_data[$lang])){
            $lang = config('lang_default');
        }
        $this->lang = $lang_data[$lang]['short'];
        $this->active_url = "/" . $module . "/" . Request::instance()->controller();
    }


}	



