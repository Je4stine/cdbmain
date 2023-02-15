<?php
/**
 * Created by PhpStorm.
 * User: Watson
 * Date: 2018/10/29 0029
 * Time: 21:12
 */

namespace alipay;

require 'sdk/AopSdk.php';
require 'sdk/aop/AopClient.php';

class App extends Base
{
    private $appid = '2018102761912051';
    private $rsaPrivateKeyFilePath;
    public function __construct()
    {
        parent::__construct();
        $this->rsaPrivateKeyFilePath = __DIR__ . '/../../ali_pem/app/rsa_private_key.pem';
        $this->rsaPublicKeyFilePath = __DIR__ . '/../../ali_pem/app/rsa_public_key.pem';
    }

    public function tradeAppPay($total_amount=0, $body='共享充电宝', $subject='')
    {
        $param = array(
            'app_id' => $this->app_id,
            'method' => 'alipay.trade.app.pay',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA',
            'timestamp' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
            'version' => '1.0',
            'notify_url' => 'http://pb-admin.ikiji.cn/user/Pay/notify',
            'biz_content' => $this->_BuildJsonQuery([
                'body' => $body,
                'subject' => $subject,
                'out_trade_no' => md5(date('YmdHis')).md5(mt_rand(1000,9999)),
                'total_amount' => (string) $total_amount,
                'product_code' => 'QUICK_MSECURITY_PAY'
            ]),
        );
        $param['sign'] = $this->sign($this->getSignContent($param));

        echo $this->_BuildQueryByAll($param);exit;
    }

    public function tradeAppPay2($total_amount=0, $body='共享充电宝', $subject='')
    {
        $rsaPrivateKey = str_replace("\n", "", file_get_contents($this->rsaPrivateKeyFilePath));
        $rsaPrivateKey = str_replace("-----BEGIN RSA PRIVATE KEY-----", "", $rsaPrivateKey);
        $rsaPrivateKey = str_replace("-----END RSA PRIVATE KEY-----", "", $rsaPrivateKey);

        $alipayrsaPublicKey = str_replace("\n", "", file_get_contents($this->rsaPublicKeyFilePath));
        $alipayrsaPublicKey = str_replace("-----BEGIN PUBLIC KEY-----", "", $alipayrsaPublicKey);
        $alipayrsaPublicKey = str_replace("-----END PUBLIC KEY-----", "", $alipayrsaPublicKey);

        $aop = new \AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $this->app_id;
        $aop->rsaPrivateKey = $rsaPrivateKey;
        $aop->alipayrsaPublicKey=$alipayrsaPublicKey;
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new \AlipayTradeAppPayRequest ();

        $request->setNotifyUrl('http://pb-admin.ikiji.cn/user/Pay/notify');

        $request->setBizContent(json_encode([
            'body' => $body,
            'subject' => $subject,
            'out_trade_no' => md5(date('YmdHis')).md5(mt_rand(1000,9999)),
            'total_amount' => '0.01',
            'product_code' => 'QUICK_MSECURITY_PAY'
        ]));

        $result = $aop->pageExecute ( $request);
echo $result;exit;
        return $result;
    }
}

