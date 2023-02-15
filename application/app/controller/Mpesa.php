<?php

namespace app\app\controller;

use think\Request;


/**
 * Mpesa
 * @package app\app\controller
 */
class Mpesa extends Common
{
    var $config = [
        'key' => 'IVzNZOObywCSm8E8Xu1RpqpjPjKhSope',
        'secret' => 't4b9K5ZI9NwIc1gE',
        'code' => 4086903,
        'initiator' => 'webapi',
        'password' => '@Jkhs8214%!',
        'certificate' => '/data/wwwroot/cdb/extend/ProductionCertificate.cer',
    ];


    /**
     * Register validation and confirmation URLs on M-Pesa
     */
    function register()
    {
        $params = [
            "ShortCode" => $this->config['code'],
            "ResponseType" => "Completed",
            "ConfirmationURL" => "https://cdb.mapro.co.ke/app/callback/complatedConfirmation",
            "ValidationURL" => "https://cdb.mapro.co.ke/app/callback/complatedValidation",
        ];
        $this->_curl('https://api.safaricom.co.ke/mpesa/c2b/v2/registerurl', $params);
    }


    /**
     * Check the status of a transaction
     */
    function query()
    {
        $password = $this->_securityCredential();
        $params = [
            "Initiator" => $this->config['initiator'],
            "SecurityCredential" => $password,
            "CommandID" => "TransactionStatusQuery",
            "TransactionID" => "QER2OSCX8W",
            "PartyA" => $this->config['code'],
            "IdentifierType" => 4,
            "ResultURL" => "https://cdb.mapro.co.ke/app/callback/queryResult",
            "QueueTimeOutURL" => "https://cdb.mapro.co.ke/app/callback/queryQueue",
            "Remarks" => "test",
            "Occassion" => "",
        ];
        $this->_curl('https://api.safaricom.co.ke/mpesa/transactionstatus/v1/query', $params);
    }


    /**
     * Reverses an M-Pesa transaction.
     */
    function reversal()
    {
        $password = $this->_securityCredential();
        $params = [
            "CommandID" => "TransactionReversal",
            "ReceiverParty" => $this->config['code'],
            "ReceiverIdentifierType" => '254758555738',
            "Remarks" => "test",
            "Initiator" => $this->config['initiator'],
            "SecurityCredential" => $password,
            "QueueTimeOutURL" => "https://cdb.mapro.co.ke/app/callback/reversalQueue",
            "ResultURL" => "https://cdb.mapro.co.ke/app/callback/reversalResult",
            "TransactionID" => "QER3OM0QN3",
            "Occassion" => "",
        ];
        $this->_curl('https://api.safaricom.co.ke/mpesa/reversal/v1/request', $params);
    }


    /**
     * Transact between an M-Pesa short code to a phone number registered on M-Pesa
     */
    function b2c()
    {
        $password = $this->_securityCredential();

        $config = [
            'key' => 'IVzNZOObywCSm8E8Xu1RpqpjPjKhSope',
            'secret' => 't4b9K5ZI9NwIc1gE',
            'code' => 4086903,
            'initiator' => 'webapi',
            'password' => '@Jkhs8214%!',
            'certificate' => '/data/wwwroot/cdb/extend/ProductionCertificate.cer',
        ];

        $params = [
            "InitiatorName" => 'webapi',
            "SecurityCredential" => $password,
            "CommandID" => "BusinessPayment",
            "Amount" => 1,
            "PartyA" => $this->config['code'],
            "PartyB" => '0758555738',
            "Remarks" => "test",
            "QueueTimeOutURL" => "https://cdb.mapro.co.ke/app/callback/b2cQueue",
            "ResultURL" => "https://cdb.mapro.co.ke/app/callback/b2cResult",
            "Occassion" => "test",
        ];
        echo '<pre>';
        print_r($params);
        $this->_curl('https://api.safaricom.co.ke/mpesa/b2c/v1/paymentrequest', $params);
    }


    private function _curl($api_url, $params)
    {
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
        echo $response;
    }


    private function _getToken()
    {
        $token = cache('mpesa_token');
        if ($token) {
            return $token;
        }
        $config = config('mpesa');
        $sign = base64_encode($config['key'] . ":" . $config['secret']);
        $ch = curl_init('https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $sign]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        if (is_array($data) && isset($data['access_token'])) {
            $token = $data['access_token'];
            cache('mpesa_token', $token, 3590);
        }
        return $token;
    }

    private function _securityCredential()
    {
        $this->config = [
            'key' => 'WBBzUTXRxT478Fp00ZUdCSmyMcrIzGHA',
            'secret' => 'p6Y0YtUdY5jNeI2U',
            'code' => 3030631,
            'initiator' => 'b2capi',
            'password' => '@Jkhs8214%!',
            'certificate' => '/data/wwwroot/cdb/extend/ProductionCertificate.cer',
        ];


        $certificate = file_get_contents($this->config['certificate']);
        openssl_public_encrypt($this->config['password'], $encrypted, $certificate, OPENSSL_PKCS1_PADDING);
        $password = base64_encode($encrypted);
        return $password;
    }


    function test()
    {
        exit;
        $password = $this->_securityCredential();

        $params = [
            "InitiatorName" => 'b2capi',
            "SecurityCredential" => $password,
            "CommandID" => "BusinessPayment",
            "Amount" => 10,
            "PartyA" => "3030631",
            "PartyB" => 254721437989,
            "Remarks" => "test",
            "QueueTimeOutURL" => "https://cdb.mapro.co.ke/app/callback/b2cQueue",
            "ResultURL" => "https://cdb.mapro.co.ke/app/callback/b2cResult",
            "Occassion" => "test123",
        ];

        $api_url = 'https://api.safaricom.co.ke/mpesa/b2c/v1/paymentrequest';

        $token = $this->_getToken2();


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
        echo $response;

    }


    public function token()
    {

// $config = [
//         'key' => 'IVzNZOObywCSm8E8Xu1RpqpjPjKhSope',
//         'secret' => 't4b9K5ZI9NwIc1gE',
//         'code' => 4086903,
//         'initiator' => 'webapi',
//         'password' => '@Jkhs8214%!',
//         'certificate' => '/data/wwwroot/cdb/extend/ProductionCertificate.cer',
//     ];


        $endpoint = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        $credentials = base64_encode("WBBzUTXRxT478Fp00ZUdCSmyMcrIzGHA:p6Y0YtUdY5jNeI2U");
        // $credentials = base64_encode("IVzNZOObywCSm8E8Xu1RpqpjPjKhSope:t4b9K5ZI9NwIc1gE");
        $curl        = curl_init();
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials));
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $curl_response = curl_exec($curl);
        $result        = json_decode($curl_response);
//print_r($curl_response);
        return isset($result->access_token) ? $result->access_token : '';
    }


    private function _getToken2()
    {
        $sign = base64_encode( "WBBzUTXRxT478Fp00ZUdCSmyMcrIzGHA:p6Y0YtUdY5jNeI2U" );

        //$sign = 'cFJZcjZ6anEwaThMMXp6d1FETUxwWkIzeVBDa2hNc2M6UmYyMkJmWm9nMHFRR2xWOQ==';
        $ch = curl_init('https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $sign]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (is_array($data) && isset($data['access_token'])) {
            $token = $data['access_token'];
        }
        return $token;
    }

}
