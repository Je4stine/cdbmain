<?php

namespace app\app\controller;

use think\Controller;
use think\Cookie;
use think\Request;
use think\Db;
use app\common\service\Base;

class Common extends Base
{
    var $app_key;
    var $auth_info;
    var $openid;
    var $client;
    var $lang;

    public function _initialize()
    {
        parent::_initialize();
        $this->app_key = $this->getOperatorConfig('client_secret');
        $this->checkAuth();
        //搜索json串使用
        $lang_data = config('lang_data');
        $lang = Request::instance()->param('lang', '', 'trim');
        if(!$lang || !isset($lang_data[$lang])){
            $lang = config('lang_default');
        }
        $this->lang = $lang_data[$lang]['short'];
    }

    function getTableNo($name, $type, $ext = '', $str = '')
    {
        if ($type == 'date') {
            return $name . "_" . $ext;
        }
        $table = substr(md5($str), 0, 2);
        if ($ext == 256) {
            return $name . "_" . $table;
        }
        if ($ext != 64 && $ext != 16 && $ext != 4) {
            die('系统繁忙');
        }
        $ext = 256 / $ext;
        $table = hexdec($table) / $ext;
        $table = dechex($table);
        $table = str_pad($table, 2, "0", STR_PAD_LEFT);
        return $name . "_" . $table;
    }

    /*
    移除Emoji表情
 */
    function removeEmoji($value)
    {

        $value = json_encode($value);
        $value = preg_replace("/\\\u[ed][0-9a-f]{3}\\\u[ed][0-9a-f]{3}/", "*", $value);//替换成*
        $value = json_decode($value);

        return $value;
    }


    public function checkAuth()
    {

        return true;
    }


    public function getCurrentHost()
    {
        try {
            return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        } catch (\Exception $e) {
            return '';
        }
    }


    public function sendRequest($data, $url)
    {
        $resp_data = array();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-length:' . strlen($data)));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $resp = curl_exec($ch);
        $resp_data = urldecode($resp);
        curl_close($ch);
        return $resp_data;
    }
}

class Security
{
    public static function encrypt($input, $key)
    {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $input = Security::pkcs5_pad($input, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = utf8_encode(base64_encode($data));
        return $data;
    }

    private static function pkcs5_pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    public static function decrypt($sStr, $sKey)
    {
        $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $sKey, base64_decode(str_replace(" ", "+", $sStr)), MCRYPT_MODE_ECB);
        $dec_s = strlen($decrypted);
        $padding = ord($decrypted[$dec_s - 1]);
        $decrypted = substr($decrypted, 0, -$padding);
        return $decrypted;
    }
}
