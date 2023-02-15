<?php

namespace app\agency\controller;

//角色管理
class Role extends Common
{
    public function _initialize()
    {
        parent::_initialize();
        if ('seller' == $this->auth_info['role']) {
            return $this->error('非法操作');
        }
    }

    public function userList()
    {
        $logic = \think\Loader::model('Seller', 'logic');
        $role = input('role', 'agency', 'trim');
        $parent_role = input('parent_role', 'agency', 'trim');
        $sub_id = input('sub_id', 0, 'intval');

        $uid = empty($sub_id) ? $this->auth_info['uid'] : $sub_id;
        if ('employee' == $this->auth_info['role']) {//业务只能查找店铺管理
            $role = 'seller';
            $parent_role = 'employee';
        }
        $page_size = input('page_size', 10, 'intval');
        $page_size < 1 && $page_size = 10;
        if ($this->auth_info['status'] != 1) {//禁止
            $query = $this->db->name('agency')->where(['id' => 0])->paginate($page_size);
        } else {
            switch ($role) {
                case 'agency':
                    $query = $this->db->name('agency')->where(['parent_id' => $uid, 'type' => 1, 'not_delete' => 1])->paginate($page_size);
                    break;
                case 'employee':
                    $query = $this->db->name('agency')->where(['parent_id' => $uid, 'type' => 2, 'not_delete' => 1])->paginate($page_size);
                    break;
                case 'seller':
                    if ('employee' == $parent_role) {//父级角色
                        $query = $this->db->name('agency')->where(['employee_id' => $uid, 'type' => 3, 'not_delete' => 1])->paginate($page_size);
                    } else {
                        $query = $this->db->name('agency')->where(['parent_id' => $uid, 'type' => 3, 'not_delete' => 1])->paginate($page_size);
                    }
                    break;
                default:
                    return $this->errorResponse(403, '非法请求');
            }
        }

        $total = $query->total();
        $list = [];
        $sub_ids = [];
        foreach ($query->all() as $v) {
            empty($v['avatar']) && $v['avatar'] = config('seller_img.avatar');
            $tmp = [
                'user_id' => $v['id'],
                'name' => $v['name'],
                'avatar' => $v['avatar'],
                'role' => $role,
                'rate' => $v['brokerage'],
                'can_edit' => $sub_id < 1,//直属下级可以修改
                'can_delete' => ($sub_id < 1 && $v['is_self']),
                'device' => [],
                'sub' => [],
            ];
            $sub_ids[] = $v['id'];
            //设备数据
            $where = [];
            switch ($role) {
                case 'agency':
                    $device_num = [
                        'device_all' => $this->db->name('device_agency')->where(['agency_id' => $v['id'], 'type' => 1])->count(),
                        'device_offline' => $this->db->name('charecabinet')->alias('a')->join("device_agency b", 'a.cabinet_id = b.device_code', 'LEFT')
                            ->where(['a.not_delete' => 1])
                            ->where(['a.heart_time' => ['<', time() - config('online_time')]])
                            ->where(['b.agency_id' => $v['id'], 'type' => 1])
                            ->count(),
                        'device_unbind' => $this->db->name('charecabinet')->alias('a')->join("device_agency b", 'a.cabinet_id = b.device_code', 'LEFT')
                            ->where(['a.not_delete' => 1, 'a.sid' => 0])
                            ->where(['a.heart_time' => ['<', time() - config('online_time')]])
                            ->where(['b.agency_id' => $v['id'], 'type' => 1])
                            ->count(),
                        'wired_all' => $this->db->name('device_agency')->where(['agency_id' => $v['id'], 'type' => 2])->count(),
                        'wired_unbind' => $this->db->name('charecabinet')->alias('a')->join("device_agency b", 'a.cabinet_id = b.device_code', 'LEFT')
                            ->where(['a.not_delete' => 1, 'a.sid' => 0])
                            ->where(['b.agency_id' => $v['id'], 'type' => 2])
                            ->count(),
                    ];
                    break;
                case 'employee':
                    $where = ['not_delete' => 1];
                    $where['employee_id'] = $v['id'];
                    break;
                case 'seller':
                    $where = ['not_delete' => 1];
                    $seller_Ids = $this->db->name('seller')->where(['not_delete' => 1, 'manager_id' => $v['id']])->column('id');
                    empty($seller_Ids) && $seller_Ids = ['-1'];
                    $where['sid'] = ['IN', $seller_Ids];
                    break;
            }
            if ('agency' != $role) {
                $device_num = [
                    'device_all' => $this->db->name('charecabinet')->where($where)->count(),
                    'device_offline' => $this->db->name('charecabinet')->where($where)->where(['heart_time' => ['<', time() - config('online_time')]])->count(),
                    'device_unbind' => $this->db->name('charecabinet')->where($where)->where(['sid' => 0])->count(),
                    'wired_all' => $this->db->name('wired_device')->where($where)->count(),
                    'wired_unbind' => $this->db->name('wired_device')->where($where)->where(['sid' => 0])->count(),
                ];
            }


            $tmp['device'][] = [
                'text' => '设备',
                'num' => $device_num['device_all'],
                'icon' => 'ic_device_all.png',
                'status' => 'all',
                'role' => $role,
                'id' => $v['id'],
                'type' => 'device',
            ];
            $tmp['device'][] = [
                'text' => '离线设备',
                'num' => $device_num['device_offline'],
                'icon' => 'ic_device_offline.png',
                'status' => 'offline',
                'role' => $role,
                'id' => $v['id'],
                'type' => 'device',
            ];
            $tmp['device'][] = [
                'text' => '未绑定设备',
                'num' => $device_num['device_unbind'],
                'icon' => 'ic_device_unbind.png',
                'status' => 'unbind',
                'role' => $role,
                'id' => $v['id'],
                'type' => 'device',
            ];
            $tmp['wired'][] = [
                'text' => '密码线',
                'num' => $device_num['wired_all'],
                'icon' => 'ic_device_all.png',
                'status' => 'all',
                'role' => $role,
                'id' => $v['id'],
            ];
            $tmp['wired'][] = [
                'text' => '未绑密码线',
                'num' => $device_num['wired_unbind'],
                'icon' => 'ic_device_unbind.png',
                'status' => 'unbind',
                'role' => $role,
                'id' => $v['id'],
            ];


            if ('agency' == $role) {
                $tmp['sub'][] = [
                    'text' => '代理商',
                    'icon' => 'ic_role_agency.png',
                    'role' => 'agency',
                    'id' => $v['id'],
                    'num' => $this->db->name('agency')->where(['type' => 1, 'not_delete' => 1, 'parent_id' => $v['id']])->count(),
                ];
                $tmp['sub'][] = [
                    'text' => '业务员',
                    'icon' => 'ic_role_employee.png',
                    'role' => 'employee',
                    'id' => $v['id'],
                    'num' => $this->db->name('agency')->where(['type' => 2, 'not_delete' => 1, 'parent_id' => $v['id']])->count(),
                ];
                $tmp['sub'][] = [
                    'text' => '店铺管理',
                    'icon' => 'ic_role_seller.png',
                    'role' => 'seller',
                    'id' => $v['id'],
                    'num' => $this->db->name('agency')->where(['type' => 3, 'not_delete' => 1, 'parent_id' => $v['id']])->count(),
                ];
            } else if ('employee' == $role) {
                $tmp['sub'][] = [
                    'text' => '店铺管理',
                    'icon' => 'ic_role_seller.png',
                    'role' => 'seller',
                    'id' => $v['id'],
                    'num' => $this->db->name('agency')->where(['type' => 3, 'not_delete' => 1, 'employee_id' => $v['id']])->count(),
                ];
            }

            $list[] = $tmp;
        }

        $data = ['total' => $total, 'list' => $list];
        return $this->successResponse($data, '用户列表');
    }


    function selectList()
    {
        $role = input('role', '', 'trim');
        if ('employee' == $this->auth_info['role']) {//业务只能查找店铺管理
            $role = 'manager';
        }
        $name = input('name', '', 'trim');
        $page_size = input('page_size', 10, 'intval');
        $page_size < 1 && $page_size = 10;
        if ($this->auth_info['status'] != 1) {//禁止
            $where = ['id' => 0];
        } else {
            switch ($role) {
                case 'agency':
                    $where = ['parent_id' => $this->auth_info['uid'], 'type' => 1, 'not_delete' => 1];
                    break;
                case 'employee':
                    $where = ['parent_id' => $this->auth_info['uid'], 'type' => 2, 'not_delete' => 1];
                    break;
                case 'manager':
                    if ('employee' == $this->auth_info['role']) {
                        $where = ['employee_id' => $this->auth_info['uid'], 'type' => 3, 'not_delete' => 1];
                    } else {
                        $where = ['parent_id' => $this->auth_info['uid'], 'type' => 3, 'not_delete' => 1];
                    }
                    break;
                default:
                    return $this->errorResponse(403, '非法请求');
            }
        }
        if('' != $name){
            $where['name'] = ['LIKE', "%{$name}%"];
        }
        $query = $this->db->name('agency')->where($where)->paginate($page_size);
        $total = $query->total();
        $list = [];
        foreach ($query->all() as $v) {
            empty($v['avatar']) && $v['avatar'] = config('seller_img.avatar');
            $list[] = [
                'user_id' => $v['id'],
                'name' => $v['name'],
                'role' => $role,
                'avatar' => $v['avatar'],
            ];
        }
        $data = ['total' => $total, 'list' => $list];
        return $this->successResponse($data, '选择用户');
    }

    //角色详情
    public function detail()
    {
        $this->checkForbid();
        $user_id = input('user_id');
        $info = $this->db->name('agency')->where(['id' => $user_id, 'not_delete' => 1])->find();
        if (!$info) {
            return $this->errorResponse(0, '用户不存在');
        }

        if ('employee' == $this->auth_info['role']) {//业务员
            if ($info['employee_id'] != $this->auth_info['uid']) {
                return $this->errorResponse(0, '用户不存在');
            }
        } else if ($info['parent_id'] != $this->auth_info['uid']) {
            return $this->errorResponse(0, '用户不存在');
        }

        empty($info['avatar']) && $info['avatar'] = config('seller_img.avatar');
        $info = [
            'id' => $info['id'],
            'name' => $info['name'],
            'phone' => $info['phone'],
            'brokerage' => $info['brokerage'],
            'avatar' => $info['avatar'],
            'role' => $info['type'],
            'status' => $info['status'],
            'open_lock' => $info['open_lock'],
            'is_self' => $info['is_self'],
            'is_vip' => $info['is_vip'],
            'type' => $info['type'],
            'employee_id' => $info['employee_id'],
            'employee_list' => [],
            'show_employee' => false,
        ];
        if ('agency' == $this->auth_info['role'] && $info['type'] == config('user_type.seller')) {
            $info['show_employee'] = true;
            $query = $this->db->name('agency')
                ->where(['parent_id' => $this->auth_info['uid'], 'not_delete' => 1, 'type' => 2])
                ->field('id,name')
                ->select();
            foreach ($query as $v) {
                $v['checked'] = false;
                if (!empty($info['employee_id']) && $info['employee_id'] == $v['id']) {
                    $v['checked'] = true;
                }
                $info['employee_list'][] = $v;
            }
        }
        return $this->successResponse($info, '角色详情');
    }

    //添加角色
    public function add()
    {
        $this->checkForbid();
        $role = input('role', '');
        $code = input('code', '');
        $phone = input('phone', '');
        if ('employee' == $this->auth_info['role']) {//业务员只能添加店铺管理
            if ($role != 3) {
                return $this->errorResponse(0, '角色错误');
            }
        }
        //手机验证码是否正确
        /*
          $result = \think\Loader::model('ValidateToken', 'logic')
              ->checkCode($phone, 'role', $code, true);
          if (1 != $result['code']) {
              !isset($result['data']) && $result['data'] = [];
              return $this->errorResponse($result['code'], $result['msg'], $result['data']);
          }*/
        if ('employee' == $this->auth_info['role']) {//业务员
            $agency = $this->db->name('agency')->where(['id' => $this->auth_info['uid']])->find();
            $params = ['parent_id' => $agency['parent_id'], 'employee_id' => $this->auth_info['uid']];
        } else {
            $params = ['parent_id' => $this->auth_info['uid']];
        }


        if (1 == $role) {//代理
            $params['is_self'] = input('is_self', 0, 'intval');
            $params['profits_rate'] = 70;
            $result = \think\Loader::model('Agency', 'logic')->agencyAdd($params);
        } else if (2 == $role) {//业务
            $result = \think\Loader::model('Employee', 'logic')->employeeAdd($params);
        } else if (3 == $role) {//店铺管理
            if ('employee' == $this->auth_info['role']) {
                $params['employee_id'] = $this->auth_info['uid'];
            } else {
                $params['employee_id'] = input('employee_id', 0, 'intval');
            }
            $result = \think\Loader::model('ShopManager', 'logic')->add($params);
        }
        if (1 != $result['code']) {
            return $this->errorResponse(0, $result['msg']);
        }
        return $this->successResponse([], '添加成功');
    }

    //修改角色
    public function edit()
    {
        $this->checkForbid();
        $role = input('role', '');
        $id = input('id', 'intval');

        if ('employee' == $this->auth_info['role']) {//业务员只能添加店铺管理
            if ($role != 3) {
                return $this->errorResponse(0, '角色错误');
            }
        }
        $info = $this->db->name('agency')->where(['id' => $id, 'not_delete' => 1])->find();
        if (!$info) {
            return $this->errorResponse(0, '用户不存在');
        }
        if ('employee' == $this->auth_info['role']) {//业务员
            if ($info['employee_id'] != $this->auth_info['uid']) {
                return $this->errorResponse(0, '用户不存在');
            }
        } else if ($info['parent_id'] != $this->auth_info['uid']) {
            return $this->errorResponse(0, '用户不存在');
        }
        $role = $info['type'];
        $params = [];

        if ('employee' == $this->auth_info['role']) {//业务员
            $agency = $this->db->name('agency')->where(['id' => $this->auth_info['uid']])->find();
            $params = ['parent_id' => $agency['parent_id'], 'employee_id' => $this->auth_info['uid']];
        } else {
            $params = ['parent_id' => $this->auth_info['uid']];
        }

        if (1 == $role) {//代理
            $is_self = input('is_self', 0, 'intval');
            (1 == $info['is_self']) && $is_self = 1;
            $params['is_self'] = $is_self;
            $params['profits_rate'] = $info['profits_rate'];
            $result = \think\Loader::model('Agency', 'logic')->agencyEdit($info, $params);
        } else if (2 == $role) {//业务
            $result = \think\Loader::model('Employee', 'logic')->employeeEdit($info, $params);
        } else if (3 == $role) {//店铺管理
            if ('employee' == $this->auth_info['role']) {
                $params['employee_id'] = $this->auth_info['uid'];
            } else {
                $params['employee_id'] = input('employee_id', 0, 'intval');
            }
            $result = \think\Loader::model('ShopManager', 'logic')->edit($info, $params);
        }
        if (1 != $result['code']) {
            return $this->errorResponse(0, $result['msg']);
        }
        return $this->successResponse([], '修改成功');
    }

    //删除角色
    public function delete()
    {
        $this->checkForbid();
        $id = input('id', 'intval');
        $info = $this->db->name('agency')->where(['id' => $id, 'not_delete' => 1])->find();
        if (1 == $info['type']) {//代理
            if ($info['parent_id'] != $this->auth_info['uid'] || $info['is_self'] == 1) {
                return $this->errorResponse(0, '无权删除该代理商');
            }
            $result = \think\Loader::model('Agency', 'logic')->appAgencyDelete($id);
        } else if (2 == $info['type']) {//业务员
            if ($info['parent_id'] != $this->auth_info['uid']) {
                return $this->errorResponse(0, '无权删除该业务员');
            }
            $result = \think\Loader::model('Employee', 'logic')->employeeDelete($id);
        } else if (3 == $info['type']) {//店铺管理
            if ($this->auth_info['uid'] != $info['parent_id'] && $this->auth_info['uid'] != $info['employee_id']) {
                return $this->errorResponse(0, '无权删除该店铺管理员');
            }
            $result = \think\Loader::model('ShopManager', 'logic')->delete($id);
        }
        if (1 != $result['code']) {
            return $this->errorResponse(0, $result['msg']);
        }
        return $this->successResponse([], '删除成功');
    }

    //发送添加角色短信
    public function sms()
    {
        $code = input('code', '', 'trim');
        $phone = input('phone', '', 'trim');
        $key = input('key', '', 'trim');
        $this->checkForbid();

        if ('' == $phone || !preg_match("/^1[23456789]\d{9}$/i", $phone)) {
            return $this->errorResponse(0, lang("请输入正确的手机号码"));
        }
        $code = strtolower($code);
        if ($code != cache("captcha:{$key}")) {
            return $this->errorResponse(0, lang("验证码错误"));
        }
        $info = $this->db->name('agency')->where(['phone' => $phone, 'not_delete' => 1])->find();
        if ($info) {
            return $this->errorResponse(0, '帐号已存在');
        }

        $result = \think\Loader::model('ValidateToken', 'logic')
            ->sendSmsCode($phone, 'role', $this->auth_info['user_type'], $this->auth_info['uid']);
        if (1 != $result['code']) {
            !isset($result['data']) && $result['data'] = [];
            return $this->errorResponse($result['code'], $result['msg'], $result['data']);
        }

        return $this->successResponse(['time' => 60], $result['msg']);
    }

    function subAgency()
    {
        $keyword = input('keyword', '', 'trim');
        $page_size = input('page_size', 15, 'intval');
        $page_size < 1 && $page_size = 15;
        $uid = $this->auth_info['uid'];
        $query = $this->db->name('agency')
            ->where(['not_delete' => 1, 'type' => 1])
            ->where('parents', 'exp', "	REGEXP '[[:<:]]" . $uid . "[[:>:]]'")
            ->paginate($page_size);
        if ('' != $keyword) {
            $query->where(['name' => ['LIKE', "%{$keyword}%"]]);
        }
        $total = $query->total();
        $list = [];
        foreach ($query->all() as $v) {
            empty($v['avatar']) && $v['avatar'] = config('seller_img.avatar');
            $list[] = [
                'user_id' => $v['id'],
                'name' => $v['name'],
                'avatar' => $v['avatar'],
            ];
        }
        $data = ['total' => $total, 'list' => $list];
        return $this->successResponse($data, '下级代理商列表');
    }

}
