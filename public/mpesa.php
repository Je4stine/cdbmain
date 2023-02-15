<?php
exit;
$obj = new Mpesa();
$token = $obj->b2cToken();
echo $token;
exit;


class Mpesa
{
    var $config = [
        'key' => 'IVzNZOObywCSm8E8Xu1RpqpjPjKhSope',
        'secret' => 't4b9K5ZI9NwIc1gE',
        'code' => 4086903,
        'initiator' => 'webapi',
        'password' => '@Jkhs8214%!',
        'certificate' => '/data/wwwroot/cdb/extend/ProductionCertificate.cer',
    ];

    public function b2cToken()
    {
        $endpoint = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        $credentials = base64_encode("WBBzUTXRxT478Fp00ZUdCSmyMcrIzGHA:p6Y0YtUdY5jNeI2U");
        $curl        = curl_init();
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials));
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $curl_response = curl_exec($curl);
        $result        = json_decode($curl_response);
        return isset($result->access_token) ? $result->access_token : '';
    }


    /**
     * Transact between an M-Pesa short code to a phone number registered on M-Pesa
     */
    function b2c()
    {
        $this->config = [
            'key' => 'WBBzUTXRxT478Fp00ZUdCSmyMcrIzGHA',
            'secret' => 'p6Y0YtUdY5jNeI2U',
            'code' => 3030631,
            'initiator' => 'b2capi',
            'password' => '@Jkhs8214%!',
            'certificate' => '/data/wwwroot/cdb/extend/ProductionCertificate.cer',
        ];

        $password = $this->_securityCredential();
        $params = [
            "InitiatorName" => $this->config['initiator'],
            "SecurityCredential" => $password,
            "CommandID" => "BusinessPayment",
            "Amount" => 1,
            "PartyA" => $this->config['code'],
            "PartyB" => 254758555738,
            "Remarks" => "test",
            "QueueTimeOutURL" => "https://cdb.mapro.co.ke/app/callback/b2cQueue",
            "ResultURL" => "https://cdb.mapro.co.ke/app/callback/b2cResult",
            "Occassion" => "test",
        ];
        $this->_curl('https://api.safaricom.co.ke/mpesa/b2c/v1/paymentrequest', $params);
    }



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
        return $this->_curl('https://api.safaricom.co.ke/mpesa/transactionstatus/v1/query', $params);
    }





    private function _curl($api_url, $params)
    {
        $sign = base64_encode($this->config['key'] . ":" . $this->config['secret']);
        $ch = curl_init('https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $sign]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        $token = '';
        if (is_array($data) && isset($data['access_token'])) {
            $token = $data['access_token'];
        }
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

    private function _securityCredential()
    {
        $certificate = file_get_contents($this->config['certificate']);
        openssl_public_encrypt($this->config['password'], $encrypted, $certificate, OPENSSL_PKCS1_PADDING);
        $password = base64_encode($encrypted);
        return $password;
    }


}
