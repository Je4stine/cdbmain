<?php

namespace app\operate\validate;
use think\Validate;

class AddBattery extends Validate
{
	//微信支付自动验证
	protected $rule=[
        'device_id'=>'require|unique:portablebattery',
    ];


    protected $message=[
        'device_id.require'=>'充电宝id不得为空！',
        'device_id.unique'=>'充电宝已注册，如需更改请联系管理员',
    ];

    protected $scene=[
        'add'=>['device_id'],
    ];

}