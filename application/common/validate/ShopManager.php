<?php

namespace app\common\validate;

use think\Validate;
use think\Db;

/**
 * 店铺管理员校验
 * @package app\common\validate
 */
class ShopManager extends Validate
{

    protected $rule = [
        'name' => 'require',
        'phone' => 'require|phoneExist',
        'password' => 'require',
        'brokerage' => 'require|number|between:0,100|checkBrokerage',
        'status' => 'require|egt:1',
        'parent_id' => 'checkAgency',
    ];


    protected $message = [
        'name.require' => '管理员名称不得为空',
        'phone.require' => '手机不得为空',
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

        $info = $db->name('agency')->where(['id' => $data['parent_id'], 'not_delete' => 1, 'type' => 1])->find();
        if ($info && intval($info['brokerage']) < $value) {
            return "佣金百分比不能超过上级代理商值{$info['brokerage']}%";
        }

        $max_brokerage = intval($info['brokerage']);
        $employee_id = intval($data['employee_id']);
        if (!$employee_id) {//业务员
            return true;
        }
        $employee = $db->name('agency')->where(['id' => $employee_id, 'not_delete' => 1, 'type' => 2])->find();
        if (!$employee) {//业务员
            return "业务员不存在";
        }
        $brokerage = $employee['brokerage']+$value;
        if($max_brokerage < $brokerage){
            return "业务员与管理员提成比总和{$brokerage}%大于代理商提成比{$max_brokerage}%";
        }
        return true;
    }

    protected function checkAgency($value, $rule, $data)
    {
        if (empty($value)) {//没有代理
            return true;
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