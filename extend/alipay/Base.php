<?php
/**
 * Created by PhpStorm.
 * User: Watson
 * Date: 2018/10/31 0031
 * Time: 21:18
 */

namespace alipay;

class Base
{
    protected $app_id = '2018102761912051'; //应用id
    protected $callback; //回调地址
    protected $rsaPrivateKey;
    protected $postCharset = 'UTF-8';
    protected $fileCharset = 'UTF-8';
    protected $request_url; //公共请求链接
    protected $rsa_private_key_path;
    public function __construct($callback='', $app_id='')
    {
        $this->callback = $callback;
        if($app_id)
            $this->app_id = $app_id;

        $this->rsa_private_key_path = __DIR__.'/../../ali_pem/app/rsa_private_key.pem';
        $this->rsaPublicKeyFilePath = __DIR__.'/../../ali_pem/app/rsa_public_key.pem';
        $this->rsaPrivateKey = file_get_contents($this->rsa_private_key_path);
        $this->request_url = 'https://openapi.alipay.com/gateway.do';
    }

    /**
     * 获取授权访问令牌失败
     * @param string $param
     * @param string $grant_type
     * @return string
     */
    public function getAccessToken($param='', $grant_type='authorization_code')
    {
        $data = $this->publicParamInit('alipay.system.oauth.token');

        $data['grant_type'] = $grant_type;
        $data['code'] = $param;

        $data['sign'] = $this->sign($this->getSignContent($data));

        $res = $this->_curlRequest($this->request_url, $data);

        $res = json_decode($res, true);

        if($res['error_response'])
            return [
                'status' => 0,
                'msg' => '获取授权访问令牌失败: '.$res['error_response']['msg'].'('.$res['error_response']['code'].$res['error_response']['sub_msg'].')'
            ];

        return [
            'status' => 1,
            'msg' => '获取成功',
            'data' => [
                'access_token' => $res['alipay_system_oauth_token_response']['access_token'],
                'return_data' => $res
            ]
        ];
    }

    /**
     * 商户请求的唯一标志，
     * 64位长度的字母数字下划线组合。
     * 该标识作为对账的关键信息，商户要保证其唯一性，
     * 对于用户使用相同transaction_id的查询，
     * 芝麻在一天（86400秒）内返回首次查询数据，
     * 超过有效期的查询即为无效并返回异常，
     * 有效期内的重复查询不重新计费
     */
    protected function _getTransactionId()
    {
        return md5(time()).md5(mt_rand(1000,9999));
    }

    /**
     * 签名数据排序
     * @param $params
     * @return string
     */
    public function getSignContent($params) {
        ksort($params);

        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
                $v = $this->characet($v, $this->postCharset);

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }

        unset ($k, $v);
        return $stringToBeSigned;
    }

    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    public function characet($data, $targetCharset) {

        if (!empty($data)) {
            $fileType = $this->fileCharset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //				$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }

        return $data;
    }

    /**
     * 签名
     * @param $data
     * @param string $signType
     * @return string
     */
    protected function sign($data, $signType = "RSA2") {
        $priKey = file_get_contents($this->rsa_private_key_path);

        /*$res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');*/

        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $priKey, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $priKey);
        }

        $sign = base64_encode($sign);

        return $sign;
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

    protected function getMillisecond() {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    /**
     * 公共参数初始化
     * @param string $method
     * @return array
     */
    protected function publicParamInit($method='')
    {
        return [
            'app_id' => $this->app_id,
            'method' => $method,
            'format' => 'JSON',
            'charset' => 'UTF-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0'
        ];
    }

    /**
     * curl请求
     * @param $url
     * @param array $postFields
     * @return mixed
     * @throws Exception
     */
    protected function _curlRequest($url, $postFields = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $postBodyString = "";
        $encodeArray = Array();
        $postMultipart = false;


        if (is_array($postFields) && 0 < count($postFields)) {

            foreach ($postFields as $k => $v) {
                if ("@" != substr($v, 0, 1)) //判断是不是文件上传
                {

                    $postBodyString .= "$k=" . urlencode($this->characet($v, $this->postCharset)) . "&";
                    $encodeArray[$k] = $this->characet($v, $this->postCharset);
                } else //文件上传用multipart/form-data，否则用www-form-urlencoded
                {
                    $postMultipart = true;
                    $encodeArray[$k] = new \CURLFile(substr($v, 1));
                }

            }
            unset ($k, $v);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postMultipart) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $encodeArray);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
            }
        }

        if ($postMultipart) {
            $headers = array('content-type: multipart/form-data;charset=' . $this->postCharset . ';boundary=' . $this->getMillisecond());
        } else {

            $headers = array('content-type: application/x-www-form-urlencoded;charset=' . $this->postCharset);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);




        $reponse = curl_exec($ch);

        if (curl_errno($ch)) {

            throw new \Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new \Exception($reponse, $httpStatusCode);
            }
        }

        curl_close($ch);

        return $reponse;
    }

    /**
     * 查询参数排序 a-z
     * @access private
     * @param $query
     * @return null|string
     */
    protected function _BuildQueryByAll( $query )
    {
        if ( !$query ) {
            return null;
        }

        //将要 参数 排序
        ksort( $query );

        //重新组装参数
        $params = array();
        foreach($query as $key => $value){
            if( $value != "" && !is_array($value) ){
                $params[] = $key .'='. urlencode($value) ;
            }
        }
        $data = implode('&', $params);

        return $data;
    }

    /**
     * 查询参数排序 a-z
     * @access private
     * @param $query
     * @return null|string
     */
    protected function _BuildJsonQuery( $query )
    {
        if ( !$query ) {
            return null;
        }

        //将要 参数 排序
        ksort( $query );

        //重新组装参数
        $params = array();
        foreach($query as $key => $value){
            if($key != "sign" && $key != "sign_type" && $value != "" && !is_array($value)){
                $params[$key] = $value;
            }
        }
        $data = json_encode($params);

        return $data;
    }
}