<?php

namespace app\agency\controller;

//商户管理
class Seller extends Common
{

    public function sellerList()
    {
        $this->lang = "'$.{$this->lang}'";
        $page_size = input('page_size', 10, 'intval');
        $page_size < 1 && $page_size = 10;
        $is_sub = input('is_sub', '', 'trim');
        $keyword = input('keyword', '', 'trim');
        $uid = $this->auth_info['uid'];
        $where = ['a.not_delete' => 1];
        '' != $keyword && $where['a.name'] = ['LIKE', "%{$keyword}%"];

        if ($this->auth_info['status'] != 1) {//禁止
            $where['a.id'] = 0;
        } else {
            if ('agency' == $this->auth_info['role']) {
                if ($is_sub == 'true') {
                    $ids = \think\Loader::model('Agency', 'logic')->subAgencyIds($uid);
                    empty($ids) && $ids = ['-1'];
                    $where['a.agency_id'] = ['IN', $ids];
                } else {
                    $where['a.agency_id'] = $uid;
                }
            } else if ('employee' == $this->auth_info['role']) {
                $where['a.employee_id'] = $uid;
            } else if ('seller' == $this->auth_info['role']) {
                $where['a.manager_id'] = $uid;
            }
        }

        $query = $this->db->name('seller')
            ->alias('a')
            ->field("a.*, JSON_UNQUOTE(a.name->$this->lang) name")
            ->where($where)
            ->order('a.id desc')
            ->paginate($page_size);

        $total = $query->total();
        $list = [];
        foreach ($query->all() as $v) {
            empty($v['logo']) && $v['logo'] = config('seller_img.logo');
            if ('agency' == $this->auth_info['role']) {
                $can_edit = $v['agency_id'] == $this->auth_info['uid'];
            } else if ('employee' == $this->auth_info['role']) {
                $can_edit = $v['employee_id'] == $this->auth_info['uid'];
            } else if ('seller' == $this->auth_info['role']) {
                $can_edit = $v['manager_id'] == $this->auth_info['uid'];
            }
            $list[] = [
                'seller_id' => $v['id'],
                'name' => $v['name'],
                'rate' => !empty($v['brokerage_show'])? $v['brokerage_show'] : $v['brokerage'],
                'logo' => $v['logo'],
                'can_edit' => $can_edit,
            ];
        }

        $data = ['total' => $total, 'list' => $list];
        return $this->successResponse($data, '商户列表');
    }


    function detail()
    {
        $this->lang = "'$.{$this->lang}'";
        $this->checkForbid();
        $seller_id = input('seller_id', 1, 'intval');
        $logic = \think\Loader::model('Seller', 'logic');

        $seller = $this->db->name('seller')->alias('a')->field("a.*,  JSON_UNQUOTE(a.name->$this->lang) name")->where(['id' => $seller_id, 'not_delete' => 1])->find();
        if (!$seller) {
            return $this->errorResponse(0, '数据不存在');
        }
        $seller['can_edit'] = true;

        if ($seller['agency_id']) {
            $agency = $this->db->name('agency')->where(['id' => $seller['agency_id']])->find();
            $seller['agency_brokerage'] = $agency['brokerage'];
            $seller['agency_name'] = $agency['name'];
            $seller['maxCharge'] = $agency['brokerage'];
        }

        if ('agency' == $this->auth_info['role']) {//代理

            if ($seller['agency_id'] != $this->auth_info['uid']) {
                $seller['can_edit'] = false;//不能操作下级数据

                $parents = empty($agency['parents']) ? [] : explode(',', $agency['parents']);
                if (!in_array($this->auth_info['uid'], $parents)) {
                    return $this->errorResponse(0, '商户不存在或无权查看');
                }
            }


        } else if ('employee' == $this->auth_info['role']) {//业务
            if ($seller['employee_id'] != $this->auth_info['uid']) {
                return $this->errorResponse(0, '商户不存在或无权查看');
            }
        } else {
            $seller['can_edit'] = false;
            if ($seller['manager_id'] != $this->auth_info['uid']) {
                return $this->errorResponse(0, '商户不存在或无权查看');
            }
        }
        if ($seller['employee_id']) {
            $employee = $this->db->name('agency')->where(['id' => $seller['employee_id']])->find();
            $seller['employee_name'] = $employee['name'];
        }
        if ($seller['manager_id']) {
            $manager = $this->db->name('agency')->where(['id' => $seller['manager_id']])->find();
            $seller['manager_name'] = $manager['name'];
        }

        if ($seller['billing_type'] == 1) {
            $billing_sys = $logic->getOperatorBilling();
        } else {
            $billing_sys = json_decode($seller['billing_set'], true);
        }
        foreach ($billing_sys as $k => $v) {
            $seller[$k] = $v;
        }
        $seller['shop_start'] = $logic->getTime($seller['shop_start']);
        $seller['shop_end'] = $logic->getTime($seller['shop_end']);
        unset($seller['password'], $seller['billing_type'], $seller['billing_set']);
        !empty($seller['logo']) && $seller['logo'] =  $seller['logo'];
        !empty($seller['picture']) && $seller['picture'] =  $seller['picture'];
//        echo '<pre>';
//        print_r($seller);
        return $this->successResponse($seller, '收费设置');

    }


    function billing()
    {
        $billing_sys = \think\Loader::model('Seller', 'logic')->getOperatorBilling();
        $info = [];
        foreach ($billing_sys as $k => $v) {
            $info[$k] = $v;
        }
        if ('agency' == $this->auth_info['role']) {
            $user = $this->db->name('agency')->where(['id' => $this->auth_info['uid']])->find();
            $info['agency_id'] = $user['id'];
            $info['agency_name'] = $user['name'];
            $info['agency_brokerage'] = $user['brokerage'];
        } else if ('employee' == $this->auth_info['role']) {
            $user = $this->db->name('agency')->where(['id' => $this->auth_info['uid']])->find();
            $agency = $this->db->name('agency')->where(['id' => $user['parent_id']])->find();
            $info['agency_id'] = $agency['id'];
            $info['agency_name'] = $agency['name'];
            $info['agency_brokerage'] = $agency['brokerage'];
            $info['employee_id'] = $user['id'];
            $info['employee_name'] = $user['name'];
            $info['employee_brokerage'] = $user['brokerage'];
        } else {
            return $this->errorResponse(403, '非法请求');
        }
        return $this->successResponse($info, '收费设置');
    }


    /**
     * 添加商户
     */
    public function add()
    {
        $this->checkForbid();
        $logic = \think\Loader::model('Seller', 'logic');
        $data = ['billing_type' => 2];
        if ('agency' == $this->auth_info['role']) {
            $data['agency_id'] = (int)$this->auth_info['uid'];
        } else if ('employee' == $this->auth_info['role']) {
            $data['employee_id'] = $this->auth_info['uid'];
            $agency = $this->db->name('agency')->where(['id' => $this->auth_info['uid']])->find();
            $data['agency_id'] = (int)$agency['parent_id'];
            $data['employee_brokerage'] = (int)$agency['brokerage'];
        } else {
            return $this->errorResponse(403, '非法请求');
        }
        return $logic->add($data);
    }


    function edit()
    {
        $this->checkForbid();
        $seller_id = input('id', 0, 'intval');
        $data = ['billing_type' => 2];
        $where = ['id' => $seller_id, 'not_delete' => 1];
        if ('agency' == $this->auth_info['role']) {
            $where['agency_id'] = $this->auth_info['uid'];
            $data['agency_id'] = (int)$this->auth_info['uid'];
        } else if ('employee' == $this->auth_info['role']) {
            $where['employee_id'] = $this->auth_info['uid'];
            $data['employee_id'] = $this->auth_info['uid'];
            $data['agency_id'] = (int)$this->db->name('agency')->where(['id' => $this->auth_info['uid']])->value('parent_id');
        } else {
            return $this->errorResponse(403, '非法请求');
        }
        $seller = $this->db->name('seller')->where($where)->find();
        if (!$seller) {
            return $this->errorResponse(0, '数据不存在');
        }
        ('employee' == $this->auth_info['role']) && $data['employee_brokerage'] = (int)$seller['employee_brokerage'];
        return \think\Loader::model('Seller', 'logic')->edit($seller, $data);
    }


    /**
     *删除商户
     */
    public function delete()
    {
        $this->checkForbid();
        $seller_id = input('seller_id', 0, 'intval');
        $where = ['id' => $seller_id];
        if ('agency' == $this->auth_info['role']) {
            $where['agency_id'] = $this->auth_info['uid'];
        } else if ('employee' == $this->auth_info['role']) {
            $where['employee_id'] = $this->auth_info['uid'];
        } else {
            return $this->errorResponse(403, '非法请求');
        }
        return \think\Loader::model('Seller', 'logic')->delete($seller_id, $where);
    }


    /**
     * 代理数据
     */
    public function agencyData()
    {
        $id = input('id', 0, 'intval');
        $result = \think\Loader::model('Agency', 'logic')->agencyData($id);
        if (1 != $result['code']) {
            return $this->errorResponse(0, $result['msg']);
        }
        return $this->successResponse($result['data'], '代理数据');
    }

    /**
     * 实时上传图片
     */
    public function fileUpload()
    {
        $this->checkForbid();
        $logic = \think\Loader::model('Upload', 'logic');
        $result = $logic->uploadImage('img_file');
        if (1 != $result['code']) {
            return $this->errorResponse(0, $result['msg']);
        }
        return $this->successResponse(['id' => $result['msg']], '上传图片');
    }

    /**
     * 微信上传图片
     */
    public function wechatUpload()
    {
        $this->checkForbid();
        $media_id = input('id', '', 'trim');
        if (empty($media_id)) {
            return $this->errorResponse(0, '请上传图片');
        }
        $logic = \think\Loader::model('Upload', 'logic');
        $result = $logic->wxImage($media_id);
        if (1 != $result['code']) {
            return $this->errorResponse(0, $result['msg']);
        }
        return $this->successResponse(['id' => $result['msg']], '上传图片');
    }


    public function deviceList()
    {
        $page_size = input('page_size', 10, 'intval');
        $page_size < 1 && $page_size = 10;
        $sid = input('id', 0, 'intval');
        $result = $this->_checkInfo($sid);
        if (1 != $result['code']) {
            return $this->errorResponse($result['code'], $result['msg']);
        }

        $where = ['sid' => $sid, 'not_delete' => 1];
        if ($this->auth_info['status'] != 1) {//禁止
            return $where['id'] = 0;
        }
        $query = $this->db->name('charecabinet')
            ->field('cabinet_id as device_id,device_num,heart_time,is_online')
            ->where($where)
            ->paginate($page_size);
        $total = $query->total();
        $list = [];

        $storage = \think\Loader::model('Storage', 'service');
        $time = time();
        foreach ($query->all() as $v) {
            $tmp = [
                'device_id' => $v['device_id'],
                'heart_time' => empty($v['heart_time']) ? '' : date("Y-m-d H:i:s", $v['heart_time']),
                'is_online' => true,
                'seller_name' => empty($v['seller_name']) ? '' : $v['seller_name'],
                'battery' => [],
            ];
            $cache = $this->getEquipmentCache($v['device_id']);
            if (!$cache || ($time - $v['heart_time']) > config('online_time')) {
                $tmp['is_online'] = false;
            }
            if ($cache && $cache['details']) {
                foreach ($cache['details'] as $v) {
                    $tmp['battery'][] = [
                        'battery_id' => $v['bid'],
                        'power' => $v['power'],
                        'lock_id' => $v['lock'],
                        'status' => 'borrow'
                    ];
                }
            }
            $list[] = $tmp;
        }
        $data = ['total' => $total, 'list' => $list];
        return $this->successResponse($data, '设备列表');
    }

    private function _checkInfo($sid)
    {
        $seller = $this->db->name('seller')->where(['id' => $sid, 'not_delete' => 1])->find();
        if (!$seller) {
            return $this->errorResponse(0, '数据不存在');
        }
        $seller['can_edit'] = true;
        if ('agency' == $this->auth_info['role']) {//代理
            $agency = $this->db->name('agency')->where(['id' => $seller['agency_id']])->find();
            if ($seller['agency_id'] != $this->auth_info['uid']) {
                $seller['can_edit'] = false;//不能操作下级数据
                $parents = empty($agency['parents']) ? [] : explode(',', $agency['parents']);
                if (!in_array($this->auth_info['uid'], $parents)) {
                    return $this->errorResponse(0, '商户不存在或无权查看');
                }
            }
        } else if ('employee' == $this->auth_info['role']) {//业务
            if ($seller['employee_id'] != $this->auth_info['uid']) {
                return $this->errorResponse(0, '商户不存在或无权查看');
            }
        } else {
            if ($seller['manager_id'] != $this->auth_info['uid']) {
                return $this->errorResponse(0, '商户不存在或无权查看');
            }
        }
        return ['code' => 1, 'data' => $seller];
    }

    function wiredList()
    {
        $page_size = input('page_size', 10, 'intval');
        $page_size < 1 && $page_size = 10;
        $sid = input('id', 0, 'intval');
        $result = $this->_checkInfo($sid);
        if (1 != $result['code']) {
            return $this->errorResponse($result['code'], $result['msg']);
        }
        $where = ['sid' => $sid, 'not_delete' => 1];
        if ($this->auth_info['status'] != 1) {//禁止
            return $where['id'] = 0;
        }
        $query = $this->db->name('wired_device')
            ->field('code as qrcode')
            ->where($where)
            ->paginate($page_size);
        $total = $query->total();
        $list = $query->all();

        $data = ['total' => $total, 'list' => $list];
        return $this->successResponse($data, '密码线列表');
    }

}
