<?php
/**
 * Created by PhpStorm.
 * User: Watson
 * Date: 2018/11/4 0004
 * Time: 17:20
 */

namespace pay;

use PayU\ApplePay\ApplePayDecodingServiceFactory;
use PayU\ApplePay\ApplePayValidator;
use PayU\ApplePay\Exception\DecodingFailedException;
use PayU\ApplePay\Exception\InvalidFormatException;

class ApplePay
{
    /**
     * https://github.com/PayU/apple-pay/blob/master/examples/decode_token.php
     */
    public function applePay($paymentData='')
    {
        // private key used to create the CSR
        $privateKey = 'MIIBATCBpgIBADBEMSIwIAYJKoZIhvcNAQkBFhMxNTgyMDU4NjkyOUAxNjMuY29tMREwDwYDVQQDDAhzaW1wbGVlbTELMAkGA1UEBhMCQ04wWTATBgcqhkjOPQIBBggqhkjOPQMBBwNCAASN6Xk4mtPs6PSGXFljF9UHkmOLHwrpvulFD0dUR7+4/KyNvh2f/Rb3vYHi7XOhfzHJIbBcjHTxQg48NW0cTPJMoAAwDAYIKoZIzj0EAwIFAANIADBFAiEAn9UpCwMrHZKA2NKGoOb/lWND3L86rRzerxjZWvndCO0CIAiMdfpsX3/+di6ezUp5MKPfOwhMzHiyh6BNv+Vpqcpk';
        // merchant identifier from Apple Pay Merchant Account
        $appleId = 'merchant.com.qiji.mopow';
        // payment token data received from Apple Pay
//        $paymentData = '{"version":"EC_v1","data":"fDSLcKGTMNUYhrITFvGJ7cbO4ZQtpmCq2dDLJvaSw2ZxolhIoATSbckgj1FSTn2Nq6VOxGK2KrkmULGS4tYMwFs2v4M1Jho0xbEZsejD5AswmFEVi11qXc7Db1Lp012qptEPGgqj5OtCPrwM/skvVcpgtrVitbrRU77itpqxWgHbHnzatQbmebf4dvU5FFNBPD9wT8XnCX5wMhM6Ys5sST3jKYa1doPArdDgI1n0WeTCS5ZkW+GRycLW+qDY5lbz1hFG6rF1VYuPp3Zy86gyZr8NeNbEYLREiGIFUhY7zMns3GKBrA06awxO5o9istOTi6lwenx5gamY7jnXUt9fdzEZyqjZ+ngw1pPUB8nF8/lag9TzP5EQ+qUnBDZt+iuASRBlLKm1WLE2RELyBcefawThUvt2QtjrrDkVYqoYl5cK+3zSJ1XRf0WmIossiTV6LQOs3Su88WBimQs0gIR5/GlisU3+sz86xUjOaAEsE3O3ydOxcH7xTJpufl7pbIOWaycZzpbD8ooL7qWNtWZenc9kfH/mmY9OTvytVaEL8MO8qYVX/iL51WesySoSNfE3xuYAfcyrZgQgHyy9DCcBmsF06PqOoX02K6cJGw3nBDlBOE567fuCgQw=","signature":"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","header":{"ephemeralPublicKey":"MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEGitrkyQvoOXZtyw5Mw1tRF6U0g9iTURw4cI9Ihf3CikL30r+owNbTbHCS1PuP2d9uaeZlFZlRsAFsfIEIqK6nQ==","publicKeyHash":"de2Q0F5xDhPCs1QOMxr6mtKUAmO6k1b8nflnE9gZ2gU=","transactionId":"4d66d60a2be9d38525758cd9c5c56c684292083e533d7c5c8e7fe374122724ef"}}';
        // how many seconds should the token be valid since the creation time.
        $expirationTime = 315360000; // It should be changed in production to a reasonable value (a couple of minutes)
        $rootCertificatePath = __DIR__ . '/apple_pay-2.pem';
        $applePayDecodingServiceFactory = new ApplePayDecodingServiceFactory();
        $applePayDecodingService = $applePayDecodingServiceFactory->make();
        $applePayValidator = new ApplePayValidator();
        $paymentData = json_decode($paymentData, true);
        try {
            $applePayValidator->validatePaymentDataStructure($paymentData);
            $decodedToken = $applePayDecodingService->decode($privateKey, $appleId, $paymentData, $rootCertificatePath, $expirationTime);
            echo json_encode([
                'token' => $decodedToken
            ]);exit;
            echo 'Decoded token is: '.PHP_EOL.PHP_EOL;
            var_dump($decodedToken);
        } catch(DecodingFailedException $exception) {
            echo json_encode([
                'failed' => $exception->getMessage()
            ]);exit;
            echo 'Decoding failed: '.PHP_EOL.PHP_EOL;
            echo $exception->getMessage();
        } catch(InvalidFormatException $exception) {
            echo json_encode([
                'failed' => $exception->getMessage()
            ]);exit;
            echo 'Invalid format: '.PHP_EOL.PHP_EOL;
            echo $exception->getMessage();
        }
    }

    /**
     * 验证AppStore内付
     * @param  string $receipt_data 付款后凭证
     * @return array                验证是否成功
     */
    function validate_apple_pay($receipt_data=''){
        // 验证参数
        if (strlen($receipt_data)<20){
            $result=array(
                'status'=>0,
                'message'=>'非法参数',
                'data' => []
            );
            return $result;
        }
        // 请求验证
        $html = $this->acurl($receipt_data);
        $data = json_decode($html,true);

        // 如果是沙盒数据 则验证沙盒模式
        if($data['status']=='21007'){
            // 请求验证
            $html = $this->acurl($receipt_data, 1);
            $data = json_decode($html,true);
            $data['sandbox'] = '1';
        }

        if (isset($_GET['debug'])) {
            exit(json_encode($data));
        }

        // 判断是否购买成功
        if(intval($data['status'])===0){
            $result=array(
                'status'=>1,
                'message'=>'购买成功',
                'data' => $data
            );
        }else{
            $result=array(
                'status'=>0,
                'message'=>'购买失败 status:'.$data['status'],
                'data' => []
            );
        }
        return $result;
    }

    /**
     * 21000 App Store不能读取你提供的JSON对象
     * 21002 receipt-data域的数据有问题
     * 21003 receipt无法通过验证
     * 21004 提供的shared secret不匹配你账号中的shared secret
     * 21005 receipt服务器当前不可用
     * 21006 receipt合法，但是订阅已过期。服务器接收到这个状态码时，receipt数据仍然会解码并一起发送
     * 21007 receipt是Sandbox receipt，但却发送至生产系统的验证服务
     * 21008 receipt是生产receipt，但却发送至Sandbox环境的验证服务
     */
    public function acurl($receipt_data='', $sandbox=0){
        //小票信息
        $POSTFIELDS = json_encode([
            'receipt-data' => $receipt_data
        ]);

        //正式购买地址 沙盒购买地址
        $url_buy     = "https://buy.itunes.apple.com/verifyReceipt";
        $url_sandbox = "https://sandbox.itunes.apple.com/verifyReceipt";
        $url = $sandbox ? $url_sandbox : $url_buy;

        //简单的curl
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $POSTFIELDS);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}