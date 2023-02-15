<?php

namespace app\common\logic;

/**
 * 商户
 * @package app\common\logic
 */
class Seller extends Common
{

    /**
     * 添加
     */
    public function add($data = [])
    {
        $params = $this->_getParams($data);
        $validate = \think\Loader::validate('Seller');
        if (!$validate->check($params)) {
            return ['code' => 0, 'msg' => $validate->getError()];
        }
        if (!empty($params['logo'])) {//有上传logo
            $uploadLogic = \think\Loader::model('Upload', 'logic');
            $file = $uploadLogic->useFile($params['logo']);
            if ('1' != $file['code']) {
                return ['code' => 0, 'msg' => lang('logo上传失败，请重新选择图片')];
            }
            $params['logo_height'] = getimagesize(config('qcloudurl').$file['picture'])[1];
            $params['logo'] = $file['qrcode'];
        } else {
            $params['logo_height'] = 170;
        }
        if (!empty($params['picture'])) {//有上传图片
            $uploadLogic = \think\Loader::model('Upload', 'logic');
            $file = $uploadLogic->useFile($params['picture'], 'seller');
            if ('1' != $file['code']) {
                return ['code' => 0, 'msg' => lang('图片上传失败，请重新选择图片')];
            }
            $params['picture_height'] = getimagesize(config('qcloudurl').$file['picture'])[1];
            $params['picture'] = $file['qrcode'];
        } else {
            $params['picture_height']= 459;
        }

        1 == $params['billing_type'] && $params['billing_set'] = [];//运营商计费标准

        $params['billing_set'] = json_encode($params['billing_set'], JSON_UNESCAPED_UNICODE);
        $params['create_time'] = time();


        $this->db->startTrans();
        try {
            $id = $this->db->name('seller')->insertGetId($params);
            $params['id'] = $id;
            $this->agencyRelation($params, $params['agency_id']);
            $this->operateLog($id, '添加商户');
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            save_log('sql', $e->getMessage());
            return ['code' => 0, 'msg' => lang('添加商户信息失败')];
        }
        return ['code' => 1, 'msg' => '添加商户信息成功'];
    }

    private function _getParams($data = [])
    {
        $params = [
            'name' => input('post.name'),
            'manager_id' => input('post.manager_id', 0, 'intval'),
            'agency_id' => input('post.agency_id', 0, 'intval'),
            'employee_id' => input('post.employee_id', 0, 'intval'),
            'employee_brokerage' => input('post.employee_brokerage', 0, 'intval'),
            'brokerage' => input('post.brokerage', '', 'trim'),
            'logo' => input('post.logo', 0, 'intval'),
            'picture' => input('post.picture', 0, 'intval'),
            'area' => input('post.area'),
            'province_code' => input('post.province_code'),
            'city_code' => input('post.city_code'),
            'district_code' => input('post.district_code'),
            'address' => input('post.address'),
            'longitude' => input('post.longitude'),
            'latitude' => input('post.latitude'),
            'tel' => input('post.tel'),
            'shop_start' => intval(str_replace(':', '', input('shop_start'))),
            'shop_end' => intval(str_replace(':', '', input('shop_end'))),
            'average_price' => input('post.average_price'),
            'status' => input('post.status', 0, 'intval'),
            'billing_type' => input('post.billing_type', 0, 'intval'),
            'billing_set' => [
                'billingunit' => input('post.billingunit', 0, 'intval'),
                'billingtime' => input('post.billingtime', 0, 'intval'),
                'amount' => input('post.amount', 0, 'floatval'),
                'ceiling' => input('post.ceiling', 0, 'floatval'),
                'freetime' => input('post.freetime', 0, 'intval'),
            ],
        ];

        $params = array_merge($params, $data);
        $params['agency_id'] < 1 && $params['agency_id'] = 0;//平台自营
        empty($params['employee_id']) && $params['employee_brokerage'] = 0;

        //线充
        $params['billing_set']['wired_amount'] = [];
        $wired_amount = input('post.wired_amount/a', []);
        $wireds = input('post.wireds');
        $wireds = json_decode($wireds, true);
        if (is_array($wireds) && !empty($wireds)) {
            $wired_amount = array_column($wireds, 'amount', 'time');
        }
        $times = [1 => '1' . lang('小时'), 2 => '2' . lang('小时'), 3 => '3' . lang('小时'), 4 => '4' . lang('小时'), 5 => '5' . lang('小时'), 6 => '6' . lang('小时'), 12 => '12' . lang('小时')];
        foreach ($times as $k => $v) {
            $params['billing_set']['wired_amount'][] = ['time' => $k, 'text' => $v, 'amount' => $wired_amount[$k]];
        }
        $params['province_code'] = intval($params['province_code']);
        $params['city_code'] = intval($params['city_code']);
        $params['district_code'] = intval($params['district_code']);
        return $params;
    }

    function agencyRelation($info, $agency_id = 0)
    {
        if (empty($agency_id)) {//没有代理，删除店铺数据
            $this->db->name('seller_agency')->where(['sid' => $info['id']])->delete();
            return;
        }
        if (!empty($info['agency_id'])) {//原来有代理
            $this->db->name('seller_agency')->where(['sid' => $info['id']])->delete();
        }

        $agency = $this->db->name('agency')->field('parents')
            ->where(['not_delete' => 1, 'id' => $agency_id, 'type' => 1])
            ->value('parents');
        $agency && $agency = explode(",", $agency);
        !$agency && $agency = [];
        $params[] = [
            'agency_id' => $agency_id,
            'sid' => $info['id'],
            'is_self' => 1
        ];
        foreach ($agency as $v) {
            $v = intval($v);
            if (empty($v)) {
                continue;
            }
            $params[] = [
                'agency_id' => $v,
                'sid' => $info['id'],
                'is_self' => 0
            ];
        }
        $this->db->name('seller_agency')->insertAll($params);

    }

    /**
     * 修改
     */
    public function edit($info, $data = [])
    {
        if (!$info) {
            return ['code' => 0, 'msg' => lang('商户信息不存在')];
        }
        $params = $this->_getParams($data);
        $validate = \think\Loader::validate('Seller');
        if (!$validate->scene('edit')->check($params)) {
            $this->error(lang($validate->getError()));
        }

        $uploadLogic = \think\Loader::model('Upload', 'logic');
        if (!empty($params['logo'])) {//有上传图片
            $file = $uploadLogic->useFile($params['logo']);
            if ('1' != $file['code']) {
                return ['code' => 0, 'msg' => lang('logo上传失败，请重新选择图片')];
            }
            $params['logo'] = $file['qrcode'];
//            !empty($info['logo']) && $uploadLogic->deleteFile($info['logo']);
        } else {
            unset($params['logo']);
        }

        if (!empty($params['picture'])) {//有上传图片
            $file = $uploadLogic->useFile($params['picture']);
            if ('1' != $file['code']) {
                return ['code' => 0, 'msg' => lang("图片上传失败，请重新选择图片")];
            }
            $params['picture'] = $file['qrcode'];
//            !empty($info['picture']) && $uploadLogic->deleteFile($info['picture']);
        } else {
            unset($params['picture']);
        }


        $params['update_time'] = time();
        1 == $params['billing_type'] && $params['billing_set'] = [];//运营商计费标准
        $params['billing_set'] = json_encode($params['billing_set'], JSON_UNESCAPED_UNICODE);
        $params['id'] = $info['id'];

        $this->db->startTrans();
        try {
            $this->db->name('seller')->update($params);
            $this->agencyRelation($info, $params['agency_id']);
            if ($info['agency_id'] != $params['agency_id']) {//更换了代理商，解绑设备
                $this->db->name('charecabinet')->where(['agency_id' => $info['agency_id'], 'sid' => $info['id']])->update(['sid' => 0, 'update_time' => time()]);
                $this->db->name('wired_device')->where(['agency_id' => $info['agency_id'], 'sid' => $info['id']])->update(['sid' => 0, 'update_time' => time()]);
            } else if ($info['employee_id'] != $params['employee_id']) {//更换了业务员，解绑设备
                $this->db->name('charecabinet')->where(['employee_id' => $info['employee_id'], 'sid' => $info['id']])->update(['sid' => 0, 'update_time' => time()]);
                $this->db->name('wired_device')->where(['employee_id' => $info['employee_id'], 'sid' => $info['id']])->update(['sid' => 0, 'update_time' => time()]);
            }

            $querystring = lang('修改商户') . $info['id'] . lang('信息');
            $this->operateLog($info['id'], "修改商户");

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            save_log('sql', $e->getMessage());
            return ['code' => 0, 'msg' => lang('修改商户信息失败')];
        }
        return ['code' => 1, 'msg' => lang('修改商户信息成功')];
    }

    /**
     * 删除
     * @param $id 商户id
     * @param array $condition 查找条件
     * @return array|void
     */
    public function delete($id, $condition = [])
    {
        $where = ['id' => $id, 'not_delete' => 1];
        $where = array_merge($where, $condition);
        $info = $this->db->name('seller')->where($where)->find();
        if (!$info) {
            return $this->error('商户信息不存在');
        }

        // 启动事务
        $this->db->startTrans();
        try {
            $this->db->name('seller')->update(['id' => $info['id'], 'not_delete' => 0, 'update_time' => time()]);
            $this->db->name('charecabinet')->where(['sid' => $id])->setField('sid', 0);
            $this->db->name('wired_device')->where(['sid' => $id])->setField('sid', 0);
            $this->agencyRelation($info, 0);
            $this->operateLog($id, '删除商户');
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            save_log('sql', $e->getMessage());
            return ['code' => 0, 'msg' => lang('删除商户失败')];
        }
        return ['code' => 1, 'msg' => lang('删除商户成功')];


    }

    /**
     * 列表
     */
    public function sellerList($condition = [], $pages = 20, $isReturn = false)
    {
        $this->lang = "'$.{$this->lang}'";
      
        $where = ['a.not_delete' => 1];
        $pageParam = ['query' => []];

        //代理商
        $agency_id = input('agency_id', 0, 'intval');
        isset($condition['agency_id']) && $agency_id = $condition['agency_id'];//传入参数优先于获取参数
        if (!empty($agency_id)) {
            $pageParam['query']['agency_id'] = $agency_id;
            $pageParam['query']['agency_name'] = input('agency_name');
            $agency_id == '-1' && $agency_id = 0;//平台自营
            $where['a.agency_id'] = $agency_id;
        }
        //业务员
        $employee_id = input('employee_id', 0, 'intval');
        isset($condition['employee_id']) && $employee_id = $condition['employee_id'];
        if (!empty($employee_id)) {
            $where['a.employee_id'] = $employee_id;
            $pageParam['query']['employee_id'] = $employee_id;
        }
        //管理员
        $manager_id = input('manager_id', 0, 'intval');
        isset($condition['manager_id']) && $manager_id = $condition['manager_id'];
        if (!empty($manager_id)) {
            $where['a.manager_id'] = $manager_id;
            $pageParam['query']['manager_id'] = $manager_id;
            $pageParam['query']['manager_name'] = input('manager_name');
        }
        //判断用户状态
        $status = input('status', 0, 'intval');
        if (!empty($status)) {
            $where['a.status'] = $status;
            $pageParam['query']['status'] = $status;
        }
        //判断是否有按店铺名查询
        $name = input('name', '', 'trim');
        if ('' != $name) {
            $where['a.name'] = ['LIKE', "%{$name}%"];
            $pageParam['query']['name'] = $name;
        }

        //判断区域查询
        $area = input('area', '', 'trim');
        if ('' != $area) {
            $where['a.area'] = ['LIKE', "%{$area}%"];
            $pageParam['query']['area'] = $area;
        }

        $query = $this->db->name('seller')
            ->alias('a')
            ->join('agency b', 'a.employee_id=b.id', 'LEFT')
            ->join('agency c', 'a.manager_id=c.id', 'LEFT')
            ->where($where)
            ->field("a.*,  JSON_UNQUOTE(a.name->$this->lang) name, address, b.name as employee, c.name as manager_name")
            ->order('a.id desc')
            ->paginate($pages, false, $pageParam);

        //echo $this->db->name('seller')->getlastsql();
        $paginate = $query->render();
        $list = $query->all();
        $total = $query->total();

        $statusList = [["id" => 1, "name" => lang('正常')], ["id" => 2, "name" => lang('禁用')]];
        $status = array_column($statusList, 'name', 'id');

        $model = \think\Loader::model('Agency');
        $allAgency = $model->allAgency();
        foreach ($list as $k => $v) {
            $v['shop_start'] = $this->getTime($v['shop_start']);
            $v['shop_end'] = $this->getTime($v['shop_end']);
            $v['status'] = $status[$v['status']];
            $v['agency'] = '';
            if (!empty($v['agency_id'])) {
                $parent = $model->getParents($allAgency, $v['agency_id']);
                $parent = array_column($parent, 'name');
                $v['agency'] = implode(" > ", $parent);
            }
            $list[$k] = $v;
        }
        return ['total' => $total, 'list' => $list];
    }

    function getTime($time)
    {
        $time = str_pad($time, 4, 0, STR_PAD_LEFT);
        return insertToStr($time, 2, ":");
    }

    /**
     * TOOD 获取商家详情
     */
    function getDetail($info)
    {
        $info['shop_start'] = $this->getTime($info['shop_start']);
        $info['shop_end'] = $this->getTime($info['shop_end']);

        if (empty($info['longitude']) || empty($info['latitude'])) {
            $info['longitude'] = '114.05792895335253';
            $info['latitude'] = '22.544036046778864';
        }
        //运营商设置
        $info['billing_sys'] = $this->getOperatorBilling();
        $info['billing_set'] = json_decode($info['billing_set'], true);
        empty($info['billing_set']) && $info['billing_set'] = [];
        $tmp = (1 == $info['billing_type']) ? $info['billing_sys'] : $info['billing_set'];
        foreach ($tmp as $k => $v) {
            $info[$k] = $v;
        }
        $info['maxCharge'] = $info['agency_brokerage'] = 100;//最大分成比
        empty($info['average_price']) && $info['average_price'] = '';//人均消费
        if (!empty($info['agency_id'])) {
            $agency = $this->db->name('agency')
                ->field('brokerage,name')
                ->where(['id' => $info['agency_id'], 'type' => 1])
                ->find();
            $info['agency_name'] = $agency['name'];
            $info['maxCharge'] = $info['agency_brokerage'] = $agency['brokerage'];
            if (!empty($info['employee_id'])) {
                $info['employee_name'] = $this->db->name('agency')
                    ->where(['id' => $info['employee_id'], 'type' => 2])
                    ->value('name');
            }
        }
        if (!empty($info['manager_id'])) {
            $manager = $this->db->name('agency')
                ->field('name,brokerage')
                ->where(['id' => $info['manager_id'], 'type' => 3])
                ->find();
            $info['manager_name'] = $manager['name'];
            $info['manager_brokerage'] = $manager['brokerage'];
        }
        return $info;
    }

    function getOperatorBilling()
    {
        $operator = $this->db->name('config')
            ->where('type', 'charge_info')
            ->find();
        $operator = json_decode($operator['data'], true);

        $info = [
            'amount' => $operator['amount'],
            'billingunit' => $operator['billingunit'],
            'billingtime' => $operator['billingtime'],
            'ceiling' => $operator['ceiling'],
            'freetime' => $operator['freetime'],
            'wired_amount' => $operator['wired_amount'],
        ];
        $info['ceiling'] < 0.01 && $info['ceiling'] = '';
        $info['freetime'] = intval($info['freetime']);
        return $info;
    }
}