<?php

namespace app\common\validate;

use think\Validate;
use think\Db;

class Feedback extends Validate
{
    protected $rule = [
        'device_code' => 'require',
//        'content' => 'require',
    ];

}