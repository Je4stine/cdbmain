<?php

namespace app\common\validate;

use think\Validate;
use think\Db;

class Seller extends Validate
{

    protected $rule = [
        'name' => 'require|json',
        'manager_id' => 'require|checkManager',
        'brokerage' => 'require|number|between:0,100|checkBrokerage',
//        'area' => 'require',
        'address' => 'require',
        'status' => 'require|egt:1',
        'billing_type' => 'require',
        'billing_set' => 'require|checkBilling',
        'average_price' => 'number|between:0,999999999'
    ];


    protected $message = [
        'name.require' => '商铺名称不得为空',
        'manager_id.require' => '请选择店铺管理员',
        'status.require' => '请选择状态',
        'status.egt' => '请选择状态',
        'brokerage.require' => '佣金百分比不得为空',
        'brokerage.number' => '佣金百分比为0-100整数',
        'brokerage.between' => '佣金百分比为0-100整数',
//        'area.require' => '请选择状态区域',
        'address.require' => '地址不得为空',
        'average_price.number' => '人均消费为大于0的正整数',
        'average_price.between' => '人均消费最大值为999999999',
        'billing_type.require' => '请选择计费方式',
        'billing_set.require' => '请选择计费方式',
        'address.json'          => ':attribute不是json格式',
    ];

    protected function checkManager($value, $rule, $data)
    {
        $db = \think\Loader::model('Base', 'service')->getDatabase();
        if(empty($value)){
            return "请选择店铺管理员";
        }
        $admin = $db->name('agency')->where(['id' => $value, 'not_delete' => 1, 'type' => 3])->find();
        if (!$admin) {//管理员
            return "管理员不存在";
        }
        $agency_id = ('-1' == $data['agency_id']) ? '0' : intval($data['agency_id']);
        if ($admin['parent_id'] != $agency_id) {
            return "上级代理不存在";
        }
        $employee_id = intval($data['employee_id']);
        if ($admin['employee_id'] != $employee_id) {
            return "上级业务员不存在";
        }
        return true;
    }

    protected function checkBrokerage($value, $rule, $data)
    {
        $db = \think\Loader::model('Base', 'service')->getDatabase();
        //判断上级代理分成
        $where = ['id' => $data['agency_id'], 'not_delete' => 1, 'type' => 1];
        $info = $db->name('agency')->where($where)->find();
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

        $brokerage = intval($data['employee_brokerage']) + $value;
        if ($max_brokerage < $brokerage) {
            return "业务员与店铺提成比总和{$brokerage}%大于代理商提成比{$max_brokerage}%";
        }
        return true;
    }

    protected function checkBilling($value, $rule, $data)
    {
        if (1 == $data['billing_type']) {//运营商收费标准
            return true;
        }
        $set = $data['billing_set'];
        if ($set['billingunit'] != 1 && $set['billingunit'] != 2) {
            return '请选择计费单位';
        }
        if (!preg_match("/^[0-9]+$/", $set['billingtime']) || empty($set['billingtime'])) {
            return '输入正确的计费时间';
        }
        if (!preg_match("/^[0-9]+(.[0-9]{1,2})?$/", $set['amount']) || empty($set['amount'])) {
            return '输入正确的计费金额';
        } else if ($set['amount'] < 1 || $set['amount'] > 1000) {
            return '计费金额范围为1-1000';
        }
        if (!empty($set['freetime'])) {
            if (!preg_match("/^[0-9]+$/", $set['freetime'])) {
                return '输入正确的免费时长';
            }
            if ($set['freetime'] > 60) {
                return '免费时长最多为60分钟';
            }
        }

        if (!empty($set['ceiling']) && !preg_match("/^[0-9]+(.[0-9]{1,2})?$/", $set['ceiling'])) {
            return '输入正确的每日最高消费';
        }
        return true;
    }

    protected function json($value, $rule, $data, $title){
        if(!is_array(json_decode($value, true))){
            return $title.'不是json格式';
        }
        return true;
    }

    protected $scene = [
        'edit' => ['name', 'manager_id', 'status', 'brokerage', 'area', 'address', 'billing_set', 'average_price'],
    ];

}