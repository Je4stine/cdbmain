<?php

namespace app\common\validate;

use think\Validate;
use think\Db;

class Agency extends Validate
{

    protected $rule = [
        'name' => 'require|checkName',
        'phone' => 'require|phoneExist',
        'password' => 'require|min:6',
        'is_self' => 'require',
        'parent_id' => 'checkParent',
        'brokerage' => 'require|number|between:0,100|checkBrokerage',
        'status' => 'require|egt:1',
    ];

    protected $message = [
        'name.require' => '代理商名称不得为空',
        'name.checkName' => '代理商名称不得为：无上级代理、无 等保留字',
        'phone.require' => '手机不得为空',
        'phone.phoneExist' => '手机号已存在',
        'password.require' => '密码不得为空',
        'password.min' => '密码至少6位数',
        'status.require' => '请选择状态',
        'status.egt' => '请选择状态',
        'brokerage.require' => '佣金百分比不得为空',
        'brokerage.number' => '佣金百分比为0-100整数',
        'brokerage.between' => '佣金百分比为0-100整数',
        'is_self.require' => '请选择代理模式',
    ];

    protected function checkPhone($value, $rule, $data)
    {
        return is_numeric($value) ? true : false;
    }

    protected function phoneExist($value, $rule, $data)
    {
        $where = ['phone' => $value, 'not_delete' => 1];
        $db = \think\Loader::model('Base', 'service')->getDatabase();
        $log = $db->name('agency')->where($where)->find();
        if ($log && $log['id'] == $data['id']) {
            $log = null;
        }
        return empty($log);
    }


    protected function checkName($value, $rule, $data)
    {
        return !in_array($value, ['无上级代理', '无']);
    }

    protected function checkBrokerage($value, $rule, $data)
    {
        //判断上级代理分成
        $where = ['id' => $data['parent_id'], 'not_delete' => 1, 'type' => 1];
        $db = \think\Loader::model('Base', 'service')->getDatabase();
        $info = $db->name('agency')->where($where)->find();
        if ($info && intval($info['brokerage']) < $value) {
            return "佣金百分比不能超过上级代理商值{$info['brokerage']}";
        }
        if (!$data['id']) {//新增
            return true;
        }
        //判断下级代理分成
        $where = ['parent_id' => $data['id'], 'not_delete' => 1, 'type' => 1, 'brokerage' => ['>', $value]];
        $num = $db->name('agency')->where($where)->count();
        if ($num) {
            return "{$num}个下级代理分成比大于{$value}%";
        }
        //判断下级业务员分成
        $where = ['parent_id' => $data['id'], 'not_delete' => 1, 'type' => 2, 'brokerage' => ['>', $value]];
        $num = $db->name('agency')->where($where)->count();
        if ($num) {
            return "{$num}个业务员分成比大于{$value}%";
        }
        //判断下级商户分成
        $where = ['agency_id' => $data['id'], 'not_delete' => 1];
        $num = $db->name('seller')->where($where)
            ->where('','exp',"employee_brokerage+brokerage > '{$value}'")
            ->count();
        if ($num) {
            return "下属{$num}个商户加业务分成比大于{$value}%";
        }
        return true;
    }

    protected function checkParent($value, $rule, $data)
    {
        $data['parent_id'] < 1 && $data['parent_id'] = 0;
        if (empty($data['parent_id'])) {//没有上级
            return true;
        }
        $db = \think\Loader::model('Base', 'service')->getDatabase();

        $log = $db->name('agency')->where(['id' => $data['parent_id'], 'not_delete' => 1])->find();
        if (!$log ) {
            return '上级代理商不存在';
        }
        if (!empty($data['id']) &&  $log['id'] == $data['id']) {
            return '上级代理商不存在';
        }
        if ($log['is_self'] != 1 && $data['is_self'] == 1) {
            return '上级代理商为设备投放商';
        }

        //是否有下级代理
        if (!empty($data['id'])) {
            $logic = \think\Loader::model('Agency', 'logic');
            $subIds = $logic->subAgencyIds($data['id']);
            if ($subIds && in_array($data['parent_id'], $subIds)) {
                return '不能选择自己的下级代理作为上级代理';
            }
        }

        return true;
    }

    protected $scene = [
        'edit' => ['name', 'phone', 'status','is_self', 'parent_id', 'brokerage'],
    ];

}