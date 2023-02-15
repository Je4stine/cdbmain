<?php

namespace app\common\validate;

use think\Validate;
use think\Db;

/**
 * 业务员校验
 * @package app\common\validate
 */
class Employee extends Validate
{

    protected $rule = [
        'name' => 'require',
        'phone' => 'require|checkPhone|phoneExist',
        'password' => 'require',
        'brokerage' => 'require|number|between:0,100|checkBrokerage',
        'status' => 'require|egt:1',
        'parent_id' => 'checkAgency',
    ];


    protected $message = [
        'name.require' => '业务员名称不得为空',
        'phone.require' => '手机不得为空',
        'phone.checkPhone' => '手机格式错误',
        'phone.phoneExist' => '手机号已存在',
        'password.require' => '密码不得为空',
        'status.require' => '请选择状态',
        'status.egt' => '请选择状态',
        'brokerage.require' => '佣金百分比不得为空',
        'brokerage.number' => '佣金百分比为0-100整数',
        'brokerage.between' => '佣金百分比为0-100整数',
    ];

    protected function checkPhone($value, $rule, $data)
    {
        return preg_match("/^1[23456789]\d{9}$/i", $value) ? true : false;
    }

    protected function phoneExist($value, $rule, $data)
    {
        $db = \think\Loader::model('Base', 'service')->getDatabase();
        $where = ['phone' => $value, 'not_delete' => 1];
        $log = $db->name('agency')->where($where)->find();
        if ($log && $log['id'] == $data['id']) {
            $log = null;
        }
        return empty($log);
    }

    protected function checkBrokerage($value, $rule, $data)
    {
        //判断上级代理分成
        $db = \think\Loader::model('Base', 'service')->getDatabase();
        $where = ['id' => $data['parent_id'], 'not_delete' => 1, 'type' => 1];
        $info = $db->name('agency')->where($where)->find();
        if ($info && intval($info['brokerage']) < $value) {
            return "佣金百分比不能超过上级代理商值{$info['brokerage']}%";
        }
        return true;
    }

    protected function checkAgency($value, $rule, $data)
    {
        if (empty($value)) {//没有代理
            return '请选择所属上级代理商';
        }
        $db = \think\Loader::model('Base', 'service')->getDatabase();
        $log = $db->name('agency')->where(['id' => $value, 'not_delete' => 1, 'type' => 1])->find();
        if (!$log) {
            return '上级代理商不存在';
        }
        return true;
    }


    protected $scene = [
        'edit' => ['name', 'phone', 'status', 'parent_id', 'brokerage'],
    ];

}