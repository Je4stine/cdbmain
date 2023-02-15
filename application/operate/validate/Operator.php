<?php
namespace app\operate\validate;
use think\Validate;
class Operator extends Validate
{

    protected $rule=[
        'name'=>'unique:operator|require',
        'contacts'=>'require',
        'mobile'=>'require',
        'domain'=>'require',
        'addrs'=>'require',
        'deposit'=>'require',
        'amount'=>'require',
        'billingunit'=>'require',
        'billingtime'=>'require',
        'depositrefund'=>'require',
        'prepaidamount'=>'require',
    ];


    protected $message=[
        'name.require'=>'运营商名称不得为空！',
        'name.unique'=>'运营商名称不得重复！',
        'contacts.require'=>'联系人不得为空！',
        'mobile.require'=>'联系电话不得为空！',
        'domain.require'=>'域名不得为空！',
        'addrs.require'=>'地址不得为空！',
        'deposit.require'=>'押金不得为空！',
        'amount.require'=>'价格单位不得为空！',
        'billingunit.require'=>'计费单位不得为空！',
        'billingtime.require'=>'计费时长不得为空！',
        'depositrefund.require'=>'押金返还规则不得为空！',
        'prepaidamount.require'=>'可充值金额不得为空！',
    ];

    protected $scene=[
        'add'=>['name','contacts','mobile','domain','addrs','deposit','amount','billingunit','billingtime','depositrefund','prepaidamount'],
        'edit'=>['name','contacts','mobile','domain','addrs','deposit','amount','billingunit','billingtime','depositrefund','prepaidamount'],
    ];
}