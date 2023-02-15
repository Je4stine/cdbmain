<?php

namespace app\common\service;


use think\Request;
use think\File;
use think\Session;
use think\Cookie;
use think\Cache;
use think\Model;
use think\Db;
use think\Log;
use Memcached;
use EasyWeChat\Foundation\Application;
use Doctrine\Common\Cache\MemcachedCache;

/**
 * 支付相关
 * @package app\common\service
 */
class Payment extends Base
{


    public function __construct()
    {

    }



    //下单接口
    function generateTrade($oid,$pay_type,$order)
    {
        switch ($pay_type){
            case 'wechat' :
                $result =  \think\Loader::model('WechatApi', 'logic')->generateTrade($oid,$order);
                break;
            case 'apipay' :
                $result =  \think\Loader::model('AlipayApi', 'logic')->generateTrade($oid,$order);
                break;
        }
        return $result;
    }



}