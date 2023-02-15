<?php
namespace app\common\model;
use think\model;

class Message extends model{

    static $success = '成功';

    public function getMessage($msg){
        return lang($msg);
    }
}