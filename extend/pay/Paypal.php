<?php
/**
 * Created by PhpStorm.
 * User: Watson
 * Date: 2018/11/4 0004
 * Time: 11:09
 */

namespace pay;

use Xxtime\PayTime\PayTime;

class Paypal
{
    public function paypal()
    {
        $payTime = new PayTime('Apple_Pay');

        $payTime->setOption(
            array(
                'app_id'      => 123456,
                'private_key' => '/path/to/privateKey.pem',
                'public_key'  => '/path/to/publicKey.pem',
                'return_url'  => 'http://host/returnUrl',
                'notify_url'  => 'http://host/notifyUrl',
            )
        );

        $payTime->purchase([
            'transactionId' => 2016121417340937383,
            'amount'        => 0.05,
            'currency'      => 'CNY',
            'productId'     => 'com.xxtime.product.1',
            'productDesc'   => '测试产品',
            'custom'        => '自定义',   // 选填
            'userId'        => '123456'   // 选填
        ]);


        try {
            $response = $payTime->send();

            // 个别渠道需要单独处理，例如:MyCard需要存储单号后跳转(其回调无单号)
            // start call service process, only MyCard can get here now
            // do something
            // end call

            if (isset($response['redirect'])) {
                $payTime->redirect();
            }
        } catch (\Exception $e) {
            // TODO :: error log
            echo $e->getMessage();
        }
    }
}