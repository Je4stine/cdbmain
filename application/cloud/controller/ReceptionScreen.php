<?php

namespace Application\YourApp;

use GatewayWorker\Lib\Gateway;

require_once("functions.php");

/**
 * 中电响应
 * @package Application\YourApp
 */
class ReceptionScreen
{

    //根据机柜发送过来的消息判断消息类型，并转交到相应的方法处理
    public function process($client_id, $message)
    {
        // 根据类型执行不同的业务
        !isset($message['cmd']) && $message['cmd'] = '';
        echo "\n {$message['cmd']} \n";
        $data = $message;
        //print_r($message);
        switch ($message['cmd']) {
            case 'login': //设备注册
                self::login($client_id, $data);
                break;
            case 'heart': // 心跳响应
                self::heartbeat($client_id, $data);
                break;
            case 'refresh': //刷新广告
                self::detailup($client_id, $data);
                break;
            case 'updata': // 升级
                self::updata($client_id, $data);
                break;
        }
    }

    //登录
    public static function login($client_id, $message)
    {
        $cabinet_id = $message['sn'];
        $md5 = $message['md5'];

        $data = [];
        $data['cabinet_id'] = $cabinet_id;
        $data['client_id'] = $client_id;
        $data['md5'] = $md5;
        $data['version'] = $message['version'];
        $data['stamp'] = $message['stamp'];
        $data['model'] = $message['model'];
        echo $cabinet_id . "\n";

        //登录
        $result = curl('screenLogin', $data);  //给服务器返回消息，服务器把信息存进缓存中
        print_r($result);

        if (1 != $result['status']) {
            echo '登陆失败';
            saveLog($client_id, "screen-" . $cabinet_id, 2, '登录失败', $message, $result);
            $content = self::sendCommand($client_id, 'login', ['status' => 2, 'msg' => $result['msg']]);
            Gateway::closeClient($client_id);
            return;
        }


        //解绑原有的
        $clients = Gateway::getClientIdByUid("screen-" . $cabinet_id);
        if (is_array($clients) && !empty($clients)) {
            foreach ($clients as $v) {
                Gateway::unbindUid($v, "screen-" . $cabinet_id);
                Gateway::closeClient($v);
            }
        }

        echo " $cabinet_id 绑定 $client_id";
        Gateway::bindUid($client_id, "screen-" . $cabinet_id);
        Gateway::setSession($client_id, ['cabinet_id' => $cabinet_id, 'type' => 'screen', 'code' => $result['ocode']]);

        $params = [
            'status' => 1,
            'sn' => $cabinet_id,
            'intro' => $result['intro'],
            'qrcode' => $result['qrcode'],
        ];
        $content = self::sendCommand($client_id, 'login', $params);
        saveLog($client_id, "screen-" . $cabinet_id, 2, '登录', $message, $content);
    }


    //发送指令

    public static function sendCommand($client_id, $cmd, $data = [])
    {
        $params = $data;
        $params['cmd'] = $cmd;
        $params['stamp'] = time();
        $content = "$##" . json_encode($params, JSON_UNESCAPED_UNICODE) . "##$";
        $content = str_replace("\\/", "/", $content);
        self::output(" cmd:$content");
        Gateway::sendToClient($client_id, $content);
        return $content;
    }


    public static function output($content)
    {
        echo " {$content} ;";
    }


    //心跳
    public static function heartbeat($client_id, $message)
    {
        echo ' heartbeat ';

        $sess = self::getSession($client_id);
        if (!$sess) {
            return;
        }
        $content = self::sendCommand($client_id, 'heart');
        $cabinet_id = $sess['cabinet_id'];
        saveLog($client_id, "screen-" . $cabinet_id, 2, '心跳', $message, $content);
    }


    //获取session
    public static function getSession($client_id)
    {
        $sess = Gateway::getSession($client_id);
        if (!isset($sess['cabinet_id'])) {
            echo "\n\n找不到session,断开连接";
            Gateway::closeClient($client_id);
            Gateway::sendToGroup('listen', $client_id . '->找不到session,断开连接');
            return false;
        }
        return $sess;
    }


    public function processCommand($client_id, $params)
    {
        $result = null;
        $type = '';
        switch ($params['cmd']) {
            case 'refresh'://刷新
                $result = self::sendCommand($client_id, 'refresh');
                $type = '重启';
                break;
            case 'update': //升级
                $result = self::sendCommand($client_id, 'updata', ['version' => $params['aims'], 'url' => $params['url']]);
                $type = '升级';
                break;
        }
        if ($result) {
            saveLog($client_id, "screen-" . $params['device_id'], 1, $type, '', $result);
        }
    }

}