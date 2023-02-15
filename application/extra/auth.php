<?php
// +----------------------------------------------------------------------
// | goAuth config
// +----------------------------------------------------------------------

return [
    // 权限开关
    'auth_on'           => 1,
    // 认证方式，0为登录认证；1为实时认证；n为n分钟更新一次权限。
    'auth_type'         => 10,
    // 用户组数据不带前缀表名
    'table_group'        => 'auth_group',
    // 用户-用户组关系不带前缀表
    'table_group_relation' => 'auth_group_relation',  
    // 权限规则不带前缀表
    'table_rule'         => 'auth_rule',
    // 用户信息不带前缀表
    'table_user'         => 'operator_users',
	
    //以下是总管理平台下的表设置
	// 管理员组数据不带前缀表名
    'admin_group'        => 'admin_auth_group',
    // 管理-用户组关系不带前缀表
    'admin_group_relation' => 'admin_auth_group_relation',  
    // 管理员权限规则不带前缀表
    'admin_rule'         => 'admin_auth_rule',
	// 管理员信息不带前缀表
    'admin_user'         => 'admin',
];
