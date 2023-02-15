<?php

namespace app\common\service;

use think\Controller;
use think\Request;
use think\Db;
use think\exception\HttpResponseException;
use think\Response;

class Base extends Controller
{
    var $oid;
    var $oCode;
    var $db;
    var $auth_info;

    public function _initialize()
    {

        $module = strtolower(Request::instance()->module());
        $code = config('ocode');
        $this->oCode = $code;

        $this->oid = 19;

        //链接数据库
        $this->db = Db::connect();
    }

    function getDatabase()
    {
        return $this->db;
    }


    function getOperatorConfig($type)
    {
        $code = $this->oCode;

        empty($code) && die('error config code');
        //cache("operator-config:{$code}", null);
        $cache = cache("operator-config:{$code}");
        if (!$cache) {
            $configs = $this->db->name('config')->select();
            $configs = array_column($configs, 'data', 'type');
            unset($configs['registration_agreement'], $configs['recharge_agreement'], $configs['user_agreement'], $configs['privacy_agreement']);
            cache("operator-config:{$code}", $configs, 3600);
        } else {
            $configs = $cache;
        }

        !isset($configs[$type]) && die('error config ' . $type);
        $config = $configs[$type];
        return $config;
    }


    function successResponse($data = [], $msg = '')
    {
        if(empty($data)){
            $data = null;
        }
        $result =  ['code' => 1, 'data' => $data, 'msg' => $msg];

        $headers = [
            "Access-Control-Allow-Origin" => '*',
            "Access-Control-Allow-Credentials" => 'true',
            "Access-Control-Allow-Headers" => "token, Origin, X-Requested-With, Content-Type, Accept, Authorization,x-www-form-urlencoded,,time,client,openid,hash,ocode"
        ];

        $response = Response::create($result, 'json')->header([]);
        throw new HttpResponseException($response);
    }

    function errorResponse($code = 0, $msg = '', $data = [])
    {
        if(empty($data)){
            $data = null;
        }
        if ('403' == $code) {
            save_log('auth', $_SERVER['REQUEST_URI']);
        }
        $result = ['code' => $code, 'msg' => $msg, 'data' => $data];
        $response = Response::create($result, 'json')->header([]);
        throw new HttpResponseException($response);
    }

    /**
     * 操作日志
     * @param $querystring
     */
    public function operateLog($id, $querystring)
    {
        $module = Request::instance()->module();
        $table = '';
        if ('operate' == $module) {//运营商
            $token = Request::instance()->header('token');
            $cache = cache("admin-token:{$this->oid}-{$token}");
            $uid = $cache['uid'];
            $table = 'operator_log';
        } else if ('agency' == $module) {//代理商
            $token = Request::instance()->header('token');
            $cache = cache("merchant-token:{$this->oid}-{$token}");
            $uid = $cache['uid'];
            $table = 'agency_log';
        }
        if (!$table) {
            return;
        }
        $params = [
            'module' => $module,
            'controller' => Request::instance()->controller(),
            'action' => Request::instance()->action(),
            'ip' => get_ip(),
            'os' => get_os(),
            'user_id' => $uid,
            'relation_id' => $id,
            'querystring' => $querystring,
            'create_time' => time(),
        ];
        $this->db->name($table)->insert($params);
    }


    function getCustomerNick($nick)
    {
        if (empty($nick)) {
            return lang(config('customer.nick'));
        }
        return base64_decode($nick);
    }

    function getEquipmentCache($device)
    {
        if(config('is_debug')){
            return ['heart_time' => time()];
        }
        $cache = \think\Loader::model('Storage', 'service')->getEquipment($device);
        return $cache;
    }

    function tableExist($table) {
        $query = $this->db->query("SHOW TABLES LIKE '{$table}'");
        return $query;
    }


    public static function getCertificate($filepath) {
        return openssl_x509_read( file_get_contents( $filepath ) );
    }
}

