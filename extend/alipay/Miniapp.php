<?php
/**
 * Created by PhpStorm.
 * User: Watson
 * Date: 2018/10/25 0025
 * Time: 21:58
 */

namespace alipay;

use think\Exception;

require 'sdk/AopSdk.php';
require 'sdk/aop/AopClient.php';

class Miniapp
{
    private $appid = '2018102161772161';
    private $seller_id = '2088821273320303';
    private $rsaPrivateKeyFilePath;
    public function __construct()
    {
        $this->rsaPrivateKeyFilePath = __DIR__.'/../../ali_pem/miniapp/rsa_private_key.pem';
        $this->rsaPublicKeyFilePath = __DIR__.'/../../ali_pem/miniapp/rsa_public_key.pem';
    }

    public function systemOauthToken($code='')
    {
        $rsaPrivateKey = str_replace("\n", "", file_get_contents($this->rsaPrivateKeyFilePath));
        $rsaPrivateKey = str_replace("-----BEGIN RSA PRIVATE KEY-----", "", $rsaPrivateKey);
        $rsaPrivateKey = str_replace("-----END RSA PRIVATE KEY-----", "", $rsaPrivateKey);

        $alipayrsaPublicKey = str_replace("\n", "", file_get_contents($this->rsaPublicKeyFilePath));
        $alipayrsaPublicKey = str_replace("-----BEGIN PUBLIC KEY-----", "", $alipayrsaPublicKey);
        $alipayrsaPublicKey = str_replace("-----END PUBLIC KEY-----", "", $alipayrsaPublicKey);

        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $this->appid;
        $aop->rsaPrivateKey = $rsaPrivateKey;
        $aop->alipayrsaPublicKey=$alipayrsaPublicKey;
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new \AlipaySystemOauthTokenRequest ();
        $request->setGrantType("authorization_code");
        $request->setCode($code);
//        $request->setRefreshToken("201208134b203fe6c11548bcabd8da5bb087a83b");

        try {
            $result = $aop->execute ( $request);
            return [
                'status' => 1,
                'message' => 'success',
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }

    public function alipayTradeCreate($total_amount='', $subject='', $buyer_id='')
    {
        $rsaPrivateKey = str_replace("\n", "", file_get_contents($this->rsaPrivateKeyFilePath));
        $rsaPrivateKey = str_replace("-----BEGIN RSA PRIVATE KEY-----", "", $rsaPrivateKey);
        $rsaPrivateKey = str_replace("-----END RSA PRIVATE KEY-----", "", $rsaPrivateKey);

        $alipayrsaPublicKey = str_replace("\n", "", file_get_contents($this->rsaPublicKeyFilePath));
        $alipayrsaPublicKey = str_replace("-----BEGIN PUBLIC KEY-----", "", $alipayrsaPublicKey);
        $alipayrsaPublicKey = str_replace("-----END PUBLIC KEY-----", "", $alipayrsaPublicKey);

        $aop = new \AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $this->appid;
        $aop->rsaPrivateKey = $rsaPrivateKey;
        $aop->alipayrsaPublicKey=$alipayrsaPublicKey;
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $aop->notify_url = url('user/Alipay/notify', '', false, true);
        $request = new \AlipayTradeCreateRequest ();
        $request->setBizContent(json_encode([
            'out_trade_no' => md5(time()).md5(mt_rand(1000,9999)),
            'total_amount' => (string) $total_amount,
            'subject' => (string) $subject,
            'buyer_id' => (string) $buyer_id
        ]));
        $result = $aop->execute ( $request);

        return json_decode($result, true);
    }
}