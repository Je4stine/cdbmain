<?php

namespace app\common\service;

use think\Request;


/**
 * Mpesa
 */
class Mpesa extends Base
{
    var $config = [
//        'key' => 'IVzNZOObywCSm8E8Xu1RpqpjPjKhSope',
//        'secret' => 't4b9K5ZI9NwIc1gE',
//        'code' => 4086903,
//        'initiator' => 'webapi',
//        'password' => '@Mapro8214',
        'key' => 'k1vLib6tHtMYsWiLjLaA0uYvA1dYCAE3',
        'secret' => 'SXVyOH3MQ1M7esmI',
        'code' => 444333,
        'initiator' => 'peterwanglei',
        'password' => 'Mopawa*221026',
        // 'initiator' => 'ppyutian',
        // 'password' => 'Ppyutian*0313',
        
        'certificate' => '/data/wwwroot/cdb/extend/ProductionCertificate.cer',
        'callback' => 'https://cdb.mopawa.co.ke/app/callback'
    ];

    public function __construct()
    {
        parent::_initialize();
        $this->config['password'] = $this->getOperatorConfig('c2b');
    }

    /**
     * Register validation and confirmation URLs on M-Pesa
     */
    function register()
    {
        $params = [
            "ShortCode" => $this->config['code'],
            "ResponseType" => "Completed",
            "ConfirmationURL" => $this->config['callback'] . "/complatedConfirmation",
            "ValidationURL" => $this->config['callback'] . "/complatedValidation",
        ];
        return $this->_curl('https://api.safaricom.co.ke/mpesa/c2b/v2/registerurl', $params);
    }


    /**
     * Check the status of a transaction
     */
    function query($trade_no)
    {
        $password = $this->_securityCredential();
        $params = [
            "Initiator" => $this->config['initiator'],
            "SecurityCredential" => $password,
            "CommandID" => "TransactionStatusQuery",
            "TransactionID" => $trade_no,
            "PartyA" => $this->config['code'],
            "IdentifierType" => 4,
            "ResultURL" => $this->config['callback'] . "/queryResult",
            "QueueTimeOutURL" => $this->config['callback'] . "/queryQueue",
            "Remarks" => "test",
            "Occassion" => "",
        ];
        return $this->_curl('https://api.safaricom.co.ke/mpesa/transactionstatus/v1/query', $params);
    }


    /**
     * Reverses an M-Pesa transaction.
     */
    function reversal($trade_no, $identifier)
    {
        $password = $this->_securityCredential();
        $params = [
            "CommandID" => "TransactionReversal",
            "ReceiverParty" => $this->config['code'],
            "ReceiverIdentifierType" => $identifier,
            "Remarks" => "test",
            "Initiator" => $this->config['initiator'],
            "SecurityCredential" => $password,
            "QueueTimeOutURL" => $this->config['callback'] . "/reversalQueue",
            "ResultURL" => $this->config['callback'] . "/reversalResult",
            "TransactionID" => $trade_no,
            "Occassion" => "",
        ];
        return $this->_curl('https://api.safaricom.co.ke/mpesa/reversal/v1/request', $params);
    }


    /**
     * Transact between an M-Pesa short code to a phone number registered on M-Pesa
     */
    function b2c($account, $amount, $remark = '', $occassion = '')
    {
        $this->config = [
            'key' => 'CyI81uWPAZNxHiZU7BZwZuR7QFA66vGZ',
            'secret' => 'dO5TvAg0Tk7XuGxf',
//            'code' => 3030631,
//            'initiator' => 'b2capi',
//            'password' => '@Mapro8214',
            'code' => 3035609,
            'initiator' => 'ppyutian',
            'password' => $this->getOperatorConfig('b2c'),
            'certificate' => '/data/wwwroot/cdb/extend/ProductionCertificate.cer',
            'callback' => 'https://cdb.mopawa.co.ke/app/callback'
        ];
        $password = $this->_securityCredential();
        $params = [
            "InitiatorName" => $this->config['initiator'],
            "SecurityCredential" => $password,
            "CommandID" => "BusinessPayment",
            "Amount" => $amount,
            "PartyA" => $this->config['code'],
            "PartyB" => $account,
            "Remarks" => 'refund',
            "QueueTimeOutURL" => $this->config['callback'] . "/b2cQueue",
            "ResultURL" => $this->config['callback'] . "/b2cResult",
            "Occassion" => $occassion,
        ];
        return $this->_curl('https://api.safaricom.co.ke/mpesa/b2c/v1/paymentrequest', $params);
    }


    private function _curl($api_url, $params)
    {
        try {
            $token = $this->_getToken();
            $ch = curl_init($api_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);
            save_log('mpesa_curl', $api_url);
            save_log('mpesa_curl', $params);
            save_log('mpesa_curl', $response);
            return $response;
        } catch (\Exception $e) {
            save_log('mpesa_error', $api_url);
            save_log('mpesa_error', $e->getMessage());
            return false;
        }

    }


    private function _getToken()
    {
        $code = $this->config['code'];
        $token = cache('mpesa_token' . $code);
        if ($token) {
            return $token;
        }
        $sign = base64_encode($this->config['key'] . ":" . $this->config['secret']);
        $ch = curl_init('https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $sign]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        if (is_array($data) && isset($data['access_token'])) {
            $token = $data['access_token'];
            cache('mpesa_token' . $code, $token, 3590);
        }
        return $token;
    }


    private function _securityCredential()
    {
        $certificate = file_get_contents($this->config['certificate']);
        openssl_public_encrypt($this->config['password'], $encrypted, $certificate, OPENSSL_PKCS1_PADDING);
        $password = base64_encode($encrypted);
        return $password;
    }
}
