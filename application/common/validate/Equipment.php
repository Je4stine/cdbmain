<?php

namespace app\common\validate;

use think\Db;
use think\Validate;

class Equipment extends Validate
{

    protected $rule = [
        //unique:user,status=1&account=’.$data[‘account’]
        ////require|unique:User,  'unique:charecabinet|not_delete=1',
        'cabinet_id' => 'require|alphaNum|nameExist',
        'model' => 'require',
        'device_num' => 'require',
        'qrcode' => 'require|alphaNum|length:5,18|codeExist',
    ];


    protected $message = [
        'cabinet_id.require' => '机柜号不得为空',
        'cabinet_id.alphaNum' => '机柜号只能由字母数字组成',
        'cabinet_id.nameExist' => '机柜号已存在',
        'model.require' => '机柜型号不得为空',
        'device_num.require' => '充电槽数量不得为空',
        'qrcode.require' => '二维码不得为空',
        'qrcode.alphaNum' => '二维码只能由字母数字组成',
        'qrcode.length' => '二维码长度为5-16位',
        'qrcode.codeExist' => '二维码已存在',
    ];


    protected function nameExist($value, $rule, $data)
    {
        $db = \think\Loader::model('Base', 'service')->getDatabase();
        $where = ['cabinet_id' => $value, 'not_delete' => 1];
        $log = $db->name('charecabinet')->where($where)->find();
        if ($log && $log['id'] == $data['id']) {
            $log = null;
        }
        return empty($log);
    }

    protected function codeExist($value, $rule, $data)
    {
        $db = \think\Loader::model('Base', 'service')->getDatabase();
        $where = ['qrcode' => $value, 'not_delete' => 1];
        $log = $db->name('charecabinet')->where($where)->find();
        if ($log && $log['id'] == $data['id']) {
            $log = null;
        }
        return empty($log);
    }

    protected $scene = [
        'add' => ['cabinet_id', 'model', 'device_num','qrcode'],
        'edit' => ['model', 'device_num','qrcode'],
    ];

}