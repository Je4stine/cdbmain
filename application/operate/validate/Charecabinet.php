<?php
namespace app\operate\validate;

use think\Db;
use think\Validate;
class Charecabinet extends Validate
{
    //微信支付自动验证
    protected $rule=[
        //unique:user,status=1&account=’.$data[‘account’]
        ////require|unique:User,  'unique:charecabinet|not_delete=1',
        'cabinet_id'=>'require|checkName',
        'model'=>'require',
        'device_num'=>'require',
//        'sid'=>'require',
    ];


    protected $message=[
        'cabinet_id.require'=>'机柜号不得为空！',
        'cabinet_id.checkName'=>'机柜号已存在！',
        'model.require'=>'模型不得为空！',
        'device_num.require'=>'充电槽数量不得为空！',
//        'sid.require'=>'商户名称不得为空！',
    ];

    protected function checkName($value,$rule,$data){
        $checkWhere = [
            'cabinet_id'=>$value,
            'not_delete'=> 1
        ];
        $res = $this->db->name('charecabinet')->where($checkWhere)->find();
        //$res = M('Charecabinet')->where($checkWhere)->getField('id');
        //var_dump($res);die;
        return !empty($res) ? false:true;
    }

    protected $scene=[
        'add'=>['cabinet_id','model','device_num'],
        'edit'=>['model','device_num'],
    ];

}