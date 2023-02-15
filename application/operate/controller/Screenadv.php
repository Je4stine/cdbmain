<?php

namespace app\operate\controller;

use think\Request;

/**
 * 屏幕广告
 * Class ScreenAdv
 * @package app\operate\controller
 */
class ScreenAdv extends Common
{
    public function _initialize()
    {
        parent::_initialize();
        $this->logic = \think\Loader::model('ScreenAdv', 'logic');
        $this->playbtn = 'https://zdoss-1258296805.cos.ap-guangzhou.myqcloud.com/screen/201910/15707834752008.png';
    }

    //查看图片
    public function viewMaterial()
    {
        $url = input('url', '', 'trim');
        $type = input('type', '', 'trim');
        $vtype = pathinfo($url, PATHINFO_EXTENSION);

        $this->assign('url', $url);
        $this->assign('type', $type);
        $this->assign('vtype', $vtype);

        return $this->fetch('screenadv/viewMaterial');
    }

    public function materialList()
    {
        $page_size = input('page_size', '20', 'intval');
        $agency_id = input('agency_id', 0, 'intval');
        $data = $this->logic->materialList($agency_id, $page_size, true);
        $this->successResponse($data, lang('获取成功'));
    }


    //待审核列表
    public function auditList()
    {
        $page_size = input('page_size', 0, 'intval');
        $page_size < 1 && $page_size = 20;
        $query = $this->db->name('ad_material_audit')
            ->where(['not_delete' => 1, 'status' => 1])
            ->order(['id' => 'DESC'])
            ->paginate($page_size, false, []);
        $total = $query->total();
        $list = $query->all();
        if ($list) {
            $aids = array_column($list, 'agency_id');
            $results = $this->db->name('agency')->field('id,type,name')->where(['id' => ['IN', $aids]])->select();
            $agency = array_column($results, NULL, 'id');
            $roles = config('user_type_name');
            foreach ($list as $k => $v) {
                $list[$k]['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
                $list[$k]['update_time'] = empty($v['update_time']) ? '' : date("Y-m-d H:i:s", $v['update_time']);
                $list[$k]['file'] = config('website') . "/uploads/" . $v['file'];
                $list[$k]['agency'] = $agency[$v['agency_id']]['name'];
                $list[$k]['role'] = $roles[$agency[$v['agency_id']]['type']];
            }
        }
        $this->successResponse(['total' => $total, 'list' => $list], lang('获取成功'));
    }

    public function auditMaterial()
    {
        $id = input('id', 0, 'intval');
        $status = input('status', 1, 'intval');
        $remark = input('remark');
        $info = $this->db->name('ad_material_audit')->where(['id' => $id])->find();
        if (!$info) {
            $this->errorResponse(0, lang('数据不存在'));
        }
        if ($status != 1) {
            $this->operateLog($this->auth_info['uid'],'修改素材');
            $this->db->name('ad_material_audit')->where(['id' => $id])->update(['status' => 3, 'remark' => $remark]);
            $this->successResponse([], lang('操作成功'));
        }

        $file = \think\Loader::model('Upload', 'logic')->uploadFileCloud($info['file'], 'screen', true);
        if (!$file) {
            $this->errorResponse(0, lang('上传图片失败，请重新上传'));
        }

        $params = [
            'name' => $info['name'],
            'type' => $info['type'],
            'agency_id' => $info['agency_id'],
            'file_id' => $info['upload_id'],
            'not_delete' => 1,
        ];
        $result = $this->logic->addMaterial($info['agency_id'], $params);
        if ($result['code'] == 1) {
            $this->db->name('ad_material_audit')->where(['id' => $id])->update(['status' => 2, 'not_delete' => 0, 'remark' => $remark]);
        }
        $this->operateLog($this->auth_info['uid'],'添加素材');
        $this->successResponse([], lang('操作成功'));
    }


    public function materialDialog()
    {
        $agency_id = input('agency_id', 0, 'intval');
        $result = $this->logic->materialList($agency_id, 15, true);
        $id = input('id', 0, 'intval');
      


        $list = [];
        $range = ceil($result['total'] / 5);
        for ($i = 0; $i < $range; $i++) {
            $list[] = array_splice($result['list'], 0, 5);
        }
        if (!empty($list) && $result['total'] < 5) {
            for ($i = 0; $i < (5 - $result['total']); $i++) {
                $list[0][] = [];
            }
        }


        $this->assign('params', $result['query']);
        $this->assign('list', $list);
        $this->assign('playbtn', $this->playbtn);
        $this->assign('paginate', $result['paginate']);
        $this->assign('title', lang('广告素材') . lang('列表'));
        return $this->fetch('screenadv/materialDialog');
    }

    public function addMaterial()
    {
        !Request::instance()->isPost() && $this->error(lang('非法请求'));
        $ret =  $this->logic->addMaterial(0);
        if($ret['code'] === 0){
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->operateLog($this->auth_info['uid'],'添加素材');
        $this->successResponse([], lang($ret['msg']));
    }


    public function deleteMaterial($id = '')
    {
        $agency_id = input('agency_id', 0, 'intval');
        $ret =  $this->logic->deleteMaterial($id, $agency_id);
        if($ret['code'] === 0){
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->operateLog($this->auth_info['uid'],'删除素材');
        $this->successResponse([], lang($ret['msg']));
    }


    /**
     * 实时上传图片
     */
    public function fileUpload($type = 'image')
    {
        set_time_limit(300);
        $logic = \think\Loader::model('Upload', 'logic');

        if ($type == 'video') {
            $result = $logic->uploadVideo('img_file');
        } else {
            $result = $logic->uploadImage('img_file');
        }

        if ($result['code'] == 1) {
            $file = $logic->uploadFileCloud($result['path'], 'screen', true);
            if (!$file) {
                return '';
            }

            $result['path'] = $file;
        }
        return json($result);
    }


    public function groupList()
    {
        $page_size = input('page_size', '20', 'intval');
        $agency_id = input('agency_id', 0, 'intval');
        $data = $this->logic->groupList(['agency_id' => $agency_id], $page_size, true);
        $this->successResponse($data, lang('获取成功'));
    }


    public function groupDialog()
    {
        $page_size = input('page_size', '20', 'intval');
        $agency_id = input('agency_id', 0, 'intval');
        $data =  $this->logic->groupList(['agency_id' => $agency_id], $page_size, true);
        $this->successResponse($data, lang('获取成功'));
    }


    public function addGroup()
    {
        !Request::instance()->isPost() && $this->error(lang('非法请求'));
        $agency_id = input('agency_id', 0, 'intval');
        $ret =  $this->logic->addGroup($agency_id);
        if($ret['code'] === 0){
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->operateLog($this->auth_info['uid'],'添加分组');
        $this->successResponse([], lang($ret['msg']));
    }

    public function groupDetail()
    {
        $id = input('id', 0, 'intval');
        $info = $this->db->name('ad_group')->where(['id' => $id, 'not_delete' => 1])->find();
        if (!$info) {
            $this->error(lang('信息不存在'));
        }
        $materials = $this->db->name('ad_material')->where(['id' => ['IN', $info['material_ids']], 'not_delete' => 1])->select();
        $materials = array_column($materials, NULL, 'id');
        $details = json_decode($info['details'], true);
        foreach ($details as $k => $v) {
            if (!isset($materials[$v['material_id']])) {
                unset($details[$k]);
                continue;
            }
            $material = $materials[$v['material_id']];
            $v['name'] = $material['name'];
            $v['type'] = $material['type'];
            $v['file'] = $material['file'];
            $v['size'] = $this->logic->fileSizeToStr($material['size']);
            $v['duration'] = $material['duration'];
            $details[$k] = $v;
        }
        $info['details'] = array_values($details);
        return $this->successResponse($info, lang('获取成功'));
    }


    public function editGroup($id = '0')
    {
        !Request::instance()->isPost() && $this->error(lang('非法请求'));
        $info = $this->db->name('ad_group')->where(['id' => $id, 'not_delete' => 1])->find();
        if (!$info) {
            $this->error(lang('信息不存在'));
        }
        $ret =  $this->logic->editGroup();
        if($ret['code'] === 0){
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->operateLog($this->auth_info['uid'],'修改分组');
        $this->successResponse([], lang($ret['msg']));
    }

    public function deleteGroup($id = '')
    {
        $agency_id = input('agency_id', 0, 'intval');
        $ret =  $this->logic->deleteGroup($id, $agency_id);
        if($ret['code'] === 0){
            return $this->errorResponse(0, lang($ret['msg']));
        }
        $this->operateLog($this->auth_info['uid'],'删除分组');
        return $this->successResponse([], lang($ret['msg']));
    }

    public function planList()
    {
        $page_size = input('page_size', '20', 'intval');
        $agency_id = input('agency_id', 0, 'intval');
        $data = $this->logic->planList(['agency_id' => $agency_id], $page_size, true);
        $this->successResponse($data, lang('获取成功'));
    }


    public function planDevice()
    {
        $page_size = input('page_size', '20', 'intval');
        $plan_id = input('id', 0, 'intval');
        $data = $this->logic->planDevice($plan_id, $page_size, true);
        $this->successResponse($data, lang('获取成功'));
    }

    public function addPlan()
    {
        if (!Request::instance()->isPost()) {
            $this->error(lang('非法请求'));
        }
        $agency_id = input('agency_id', 0, 'intval');
        return $this->logic->addPlan($agency_id);
    }

    public function planDetail()
    {
        $id = input('id', 0, 'intval');
        $info = $this->db->name('ad_plan')->where(['id' => $id, 'not_delete' => 1])->find();
        if (!$info) {
            $this->error(lang('信息不存在'));
        }
        $hours = $minutes = [];
        for ($i = 0; $i < 24; $i++) {
            $tmp = ($i < 10) ? '0' . $i : $i;
            $hours[] = $tmp;
        }
        for ($i = 0; $i < 60; $i++) {
            $tmp = ($i < 10) ? '0' . $i : $i;
            $minutes[] = $tmp;
        }
//            $info['week'] = [];
//            $cycle = json_decode($info['cycle'], true);
//            count($cycle)>6 && $info['week'][0] = true;
//            foreach($cycle as $v){
//                $info['week'][$v] = true;
//            }
        $info['details'] = json_decode($info['details'], true);
        $group_ids = array_column($info['details'], 'group_id');
        $groups = $this->db->name('ad_group')->where(['id' => ['in', $group_ids], 'not_delete' => 1])->select();
        $groups = array_column($groups, NULL, 'id');
        foreach ($info['details'] as $k => $v) {
            $v['size'] = $this->logic->fileSizeToStr($groups[$v['group_id']]['material_size']);
            $v['duration'] = $this->logic->timeFormat($groups[$v['group_id']]['seconds']);
            $info['details'][$k] = $v;
        }
        $equipment_group = [];


        //选择代理
        if (1 == $info['channel']) {
            $aids = $this->db->name('ad_plan_agency')->field('data_id')->where(['plan_id' => $id, 'not_delete' => 1])->column('data_id');
            $query = $this->db->name("charecabinet")
                ->field("count(*) as num, agency_id")
                ->where(['not_delete' => 1, 'device_num' => ['>', 23], 'agency_id' => ['IN', $aids]])
                ->group("agency_id")
                ->select();
            $equipment = array_column($query, 'num', 'agency_id');

            $query = $this->db->name('agency')->field('id,name')->where(['id' => ['IN', $aids], 'not_delete' => 1])->select();
            $agency = array_column($query, 'name', 'id');
            $agency[0] = "平台自营";

            foreach ($aids as $v) {
                if (!isset($agency[$v])) {
                    continue;
                }
                $equipment_group[] = [
                    'data_id' => $v,
                    'name' => $agency[$v],
                    'num' => intval($equipment[$v]),
                ];
            }
        } else {
            $aids = $this->db->name('ad_plan_charecabinet')->field('data_id')->where(['plan_id' => $id, 'not_delete' => 1])->column('data_id');
            $query = $this->db->name('charecabinet')->field('cabinet_id,device_num')->where(['cabinet_id' => ['IN', $aids], 'not_delete' => 1])->select();
            foreach ($query as $v) {
                $equipment_group[] = [
                    'data_id' => $v['cabinet_id'],
                    'name' => $v['cabinet_id'],
                    'num' => intval($v['device_num']),
                ];
            }
        }
        $info['equipment_group'] = $equipment_group;
        return $this->successResponse($info, lang('获取成功'));
    }

    public function editPlan($id = '0')
    {
        if (!Request::instance()->isPost()) {
            $this->error(lang('非法请求'));
        }
        $info = $this->db->name('ad_plan')->where(['id' => $id, 'not_delete' => 1])->find();
        if (!$info) {
            $this->error(lang('信息不存在'));
        }
        return $this->logic->editPlan();
    }

    public function deletePlan($id = '')
    {
        $agency_id = input('agency_id', 0, 'intval');
        return $this->logic->deletePlan($id, $agency_id);
    }


    public function size()
    {
        $id = input('id', 0, 'intval');
        $info = $this->db->name('ad_position')->where(['id' => $id])->find();
        if ($info) {
            $info['data'] = json_decode($info['data'], true);
            foreach ($info['data'] as $k => $v) {
                $info[$k] = $v;
            }
            unset($info['data']);
        } else {
            $info = ['top_height' => 0, 'middle_height' => 0, 'plan_height' => 1730, 'bottom_height' => 190,];
        }
        //预览1/4比例显示
//        $info['top_show'] = $info['top_height']/4;
//        $info['middle_show'] = $info['middle_height']/4;
//        $info['plan_show']= $info['plan_height']/4;
//        $info['bottom_show']= $info['bottom_height']/4;
        $this->successResponse($info, lang('获取成功'));
    
    }

    public function addSize()
    {
        if (!Request::instance()->isPost()) {
            return $this->fetch('screenadv/addSize');
        }
        return $this->logic->sizeSet();
    }

    //默认广告图
    function defaultImg()
    {
        $text = [
            'default_vertical' => lang('大机柜默认广告图'),
            'default_bottom' => lang('大机柜默认底部图'),
            'rent_success_vertical' => lang('租借成功图'),
            'rent_fail_vertical' => lang('租借失败图'),
            'back_success_vertical' => lang('归还成功图'),
            'back_fail_vertical' => lang('归还失败图'),
            'default_cross'=> lang('八口默认广告图'),
            'default_left_cross'=> lang('八口默认左侧图'),
        ];
        $data = [];
        $config = $this->db->name('config')->where(['type' => 'screen_default'])->find();
        $config = json_decode($config['data'], true);
        foreach ($text as $k => $v) {
            $data[] = ['type' => $k, 'text' => $v, 'img' => $config[$k]];
        }
        $this->successResponse($data, lang('获取成功'));
    }

    function defaultSet()
    {
        if (!Request::instance()->isPost()) {
            $this->error(lang('非法请求'));
        }
        $type = input('type');
        $config = $this->db->name('config')->where(['type' => 'screen_default'])->find();
        $config = json_decode($config['data'], true);
        if (!isset($config[$type])) {
            $this->error(lang('图片类型不存在'));
        }
        $image = input('image');
        $file = \think\Loader::model('Upload', 'logic')->useFile($image, 'screen', true);
        if ($file['code'] != 1) {
            $this->error(lang('上传图片失败，请重试'));
        }
        $image = $file['qrcode'];
        $config[$type] = $image;
        $this->db->name('config')
            ->where(['type' => 'screen_default'])
            ->update(['data' => json_encode($config, JSON_UNESCAPED_UNICODE), 'update_time' => time()]);

        cache("operator-config:{$this->oCode}", null);
        $this->successResponse([], lang('操作成功'));
    }

    public function deviceDialog()
    {
        $type = input('type');
        $page_size = input('page_size', 20, 'intval');
        $model = input('model', 'vertical');
        !in_array($model, ['vertical', 'cross', 'cross_touch']) && $model = 'vertical';


        if ('device' == $type) {
            $data = \think\Loader::model('Equipment', 'logic')->equipmentList(['screen' => $model], $page_size, true);
            foreach ($data['list'] as $k => $v) {
                $data['list'][$k] = [
                    'cabinet_id' => $v['cabinet_id'],
                    'device_num' => $v['device_num'],
                    'name' => $v['name'],
                ];
            }
        } else {
            $where = ['not_delete' => 1];
            if ('cross' == $model) {
                $where['model'] = 'pm8';
            } else if ('cross_touch' == $model) {
                $where['model'] = 'cp8';
            } else {
                $where['device_num'] = ['>', 23];
            }
            $data = $this->db->name("charecabinet")
                ->field("count(*) as num, agency_id")
                ->where($where)
                ->group("agency_id")
                ->select();

            $aids = array_column($data, 'agency_id');
            $query = $this->db->name('agency')->field('id,name')->where(['id' => ['IN', $aids], 'not_delete' => 1])->select();
            $agency = array_column($query, 'name', 'id');
            $agency[0] = "平台自营";
            foreach ($data as $k => $v) {
                $data[$k]['name'] = $agency[$v['agency_id']];
            }
        }
        return $this->successResponse($data, lang('获取成功'));
    }

}