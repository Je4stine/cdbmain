<?php


namespace app\common\behavior;

use think\Exception;
use think\Response;

class CronRun
{
    public function run(&$dispatch){
        $host_name = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : "*";
        $headers = [
            "Access-Control-Allow-Origin" => '*',
            "Access-Control-Allow-Credentials" => 'true',
            "Access-Control-Allow-Headers" => "token, Origin, X-Requested-With, Content-Type, Accept, Authorization,x-www-form-urlencoded,,time,client,openid,hash,ocode"
        ];
        if($dispatch instanceof Response) {
            $dispatch->header($headers);
        } else if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $dispatch['type'] = 'response';
            $response = new Response('', 200, $headers);
            $dispatch['response'] = $response;
        }
    }
}