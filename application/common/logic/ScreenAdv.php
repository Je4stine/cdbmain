<?php

namespace app\common\logic;

use think\Cache;
use think\Exception;

/**
 * 屏幕广告
 * @package app\common\logic
 */
class Screenadv extends Common
{

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 添加素材
     */
    public function addMaterial($agency_id = 0, $params = [])
    {
        set_time_limit(300);
        $agency_id < 1 && $agency_id = 0;
        if (empty($params)) {
            $params = [
                'id' => input('post.id', 0, 'intval'),
                'name' => input('post.name'),
                'type' => input('post.type'),
                'agency_id' => $agency_id,
                'not_delete' => 1,
            ];
            $file_id = ($params['type'] == 'video') ? input('post.video') : input('post.image');
        } else {
            $file_id = $params['file_id'];
            unset($params['file_id']);
        }

        $file = $this->_userFile($file_id, 'screen', true);
        if ($file['code'] != 1) {
            $this->error(lang('请上传素材'));
        }

        $logic = \think\Loader::model('Upload', 'logic');
        //视频获取时长和第一帧截图
        if ($params['type'] == 'video') {
            $video = $file['path'];
            define('FFMPEG', '/home/test/ffmpeg/bin/ffmpeg');
            ob_start();
            passthru(FFMPEG . " -i " . $video . " 2>&1 ");
            $video_info = ob_get_contents();
            ob_end_clean();
            if (preg_match("/Duration: (.*?), start: (.*?), bitrate: (\d*) kb\/s/", $video_info, $matches)) {
                $duration = explode(':', $matches[1]);
                $duration[2] = ceil($duration[2]);
                $params['seconds'] = $duration[0] * 3600 + $duration[1] * 60 + intval($duration[2]); // 转为秒数
                $params['duration'] = implode(':', $duration); // 视频长度
            }

            $thumb = $video . "_thumb.jpg";
            passthru(FFMPEG . " -i " . $video . " -y -f image2 -t 0.001 -s 160x160 " . $thumb);
            if (!file_exists($thumb)) {
                $this->error(lang('生成视频截图失败1' . $thumb));
            }
            $thumb = str_replace(ROOT_PATH . 'public/uploads/', "", $thumb);
            $thumb = $logic->uploadFileCloud($thumb);
            if (!$thumb) {
                $this->error(lang('生成视频截图失败'));
            }
        } //图片截取缩略图
        else if ($params['type'] == 'image') {
            $thumb = $file['path'];
            if (!@copy($file['file'], $thumb . "_thumb.jpg")) {
                $this->error(lang('生成缩略图失败'));
            }
            $thumb = $thumb . "_thumb.jpg";
            $image = \think\Image::open($thumb);
            $image->thumb(160, 160)->save($thumb);
            $thumb = str_replace(ROOT_PATH . 'public/uploads/', "", $thumb);

            $thumb = $logic->uploadFileCloud($thumb);
            if (!$thumb) {
                $this->error(lang('生成缩略图失败'));
            }
        }

        $params['file'] = $file['file'];
        $params['size'] = $file['size'];
        $params['create_time'] = time();
        if ($id = $this->db->name('ad_material')->insertGetId($params)) {
            $this->operateLog($id, '添加广告素材');
            return ['code' => 1, 'msg' => lang('操作成功')];
        }
        return ['code' => 0, 'msg' => lang('操作失败')];
    }

    /**
     * 删除素材
     * @param $id 主键id
     * @return array|void
     */
    public function deleteMaterial($id, $agency_id = 0)
    {
        $agency_id < 1 && $agency_id = 0;
        $info = $this->db->name('ad_material')->where(['id' => $id, 'not_delete' => 1])->find();
        if (!$info || $info['agency_id'] != $agency_id) {
            return ['code' => 1, 'msg' => lang('操作成功')];
        }
        $num = $this->db->name('ad_group')
            ->where('not_delete = 1')
            ->where('material_ids', 'exp', "	REGEXP '[[:<:]]" . $id . "[[:>:]]'")
            ->count();
        if ($num > 0) {
            return ['code' => 0, 'msg' => $num . lang('广告分组') . lang('正在使用该素材')];
        }
        $info['not_delete'] = 0;
        if ($this->db->name('ad_material')->update($info)) {
            $this->operateLog($info['id'], '删除广告素材');
            return ['code' => 1, 'msg' => lang('操作成功')];
        }
        return ['code' => 0, 'msg' => lang('操作失败')];
    }

    /**
     * 素材列表
     */
    public function materialList($agency_id = 0, $pages = 20, $isReturn = false)
    {
        $pages < 1 && $pages = 10;

        $where = ['not_delete' => 1];
        if (!empty($agency_id)) {
            $agency_id < 1 && $agency_id = 0;
            $where['agency_id'] = $agency_id;
        }

        $pageParam = ['query' => []];

        $type = input('type', '', 'trim');
        if (!empty($type)) {
            $where['type'] = $type;
            $pageParam['query']['type'] = $type;
        }

        $name = input('name', '', 'trim');
        if ('' != $name) {
            $where['name'] = ['LIKE', "%{$name}%"];
            $pageParam['query']['name'] = $name;
        }

        $query = $this->db->name('ad_material')
            ->where($where)
            ->order(['id' => 'DESC'])
            ->paginate($pages, false, $pageParam);
        $total = $query->total();
        $paginate = $query->render();
        $list = $query->all();
        $aids = array_column($list, 'agency_id');
        $agencies = $this->_getAgency($aids);

        foreach ($list as $k => $v) {
            $list[$k]['created'] = date("Y-m-d H:i:s", $v['create_time']);
            $list[$k]['size'] = $this->fileSizeToStr($v['size']);
            $list[$k]['uploader'] = empty($v['agency_id']) ? lang('平台') : $agencies[$v['agency_id']]['name'];
        }

        if ($isReturn) {
            return ['total' => $total, 'list' => $list, 'query' => $pageParam['query'], 'paginate' => $paginate];
        }
        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
        $this->assign('list', $list);
        $this->assign('paginate', $paginate);
        $this->assign('title', lang('广告素材') . lang('列表'));
    }

    private function _getAgency($ids)
    {
        $results = $this->db->name('agency')->field('id,phone,name')->where(['id' => ['in', $ids]])->select();
        return array_column($results, null, 'id');
    }

    public function fileSizeToStr($size)
    {
        if ($size < 1048576) {
            return preg_replace("/([0-9]+\.[0-9]{2})[0-9]*/", '$1', ($size / 1024)) . "K";
        }
        return preg_replace("/([0-9]+\.[0-9]{2})[0-9]*/", '$1', ($size / 1048576)) . "M";
    }

    /**
     * 分组列表
     */
    public function groupList($condition, $pages = 20, $isReturn = false)
    {
        $where = ['not_delete' => 1];
        if (isset($condition['agency_id']) && !empty($condition['agency_id'])) {
            $condition['agency_id'] < 1 && $condition['agency_id'] = 0;
            $where['agency_id'] = $condition['agency_id'];
        }
        $pageParam = ['query' => []];
        $query = $this->db->name('ad_group')
            ->where($where)
            ->order(['id' => 'DESC'])
            ->paginate($pages, false, $pageParam);
        $total = $query->total();
        $paginate = $query->render();
        $list = $query->all();
        $aids = array_column($list, 'agency_id');
        $agencies = $this->_getAgency($aids);
        foreach ($list as $k => $v) {
            $list[$k]['size'] = $this->fileSizeToStr($v['material_size']);
            $material_num = json_decode($v['material_num'], true);
            $list[$k]['image_num'] = $material_num['image'];
            $list[$k]['video_num'] = $material_num['video'];
            $list[$k]['duration'] = $this->timeFormat($v['seconds']);
            $list[$k]['uploader'] = empty($v['agency_id']) ? lang('平台') : $agencies[$v['agency_id']]['name'];
            $list[$k]['created'] = date("Y-m-d H:i:s", $v['create_time']);
        }

        if ($isReturn) {
            return ['total' => $total, 'list' => $list];
        }
        //有查询则赋值
        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
        $this->assign('list', $list);
        $this->assign('paginate', $paginate);
        $this->assign('title', lang('广告素材列表'));
    }

    public function timeFormat($seconds)
    {
        $hours = floor($seconds / 3600);
        $hours < 10 && $hours = "0" . $hours;
        $remain = $seconds % 3600;
        $mins = floor($remain / 60);
        $mins < 10 && $mins = "0" . $mins;
        $secs = $remain % 60;
        $secs < 10 && $secs = "0" . $secs;
        return "{$hours}:{$mins}:{$secs}";
    }

    /**
     * 添加分组
     */
    public function addGroup($agency_id = 0)
    {
        $params = $this->_getGroupParams();
        $params['create_time'] = time();
        $agency_id < 1 && $agency_id = 0;
        $params['agency_id'] = $agency_id;
        if ($id = $this->db->name('ad_group')->insertGetId($params)) {
            $this->operateLog($id, '添加广告分组');
            return ['code' => 1, 'msg' => lang('操作成功')];
        }
        return ['code' => 0, 'msg' => lang('操作失败')];
    }

    /**
     * 广告组参数
     */
    private function _getGroupParams()
    {
        $params = [
            'id' => input('post.id', 0, 'intval'),
            'name' => input('post.name', '', 'trim'),
            'material_num' => ['image' => 0, 'video' => 0],
            'material_size' => 0,
            'seconds' => 0,
            'material_ids' => [],
            'not_delete' => 1,
        ];
        if (empty($params['name'])) {
            $this->error(lang('请输入名称'));
        }

        $details = input('details');
        $details = json_decode($details, true);
        if (empty($details) || !is_array($details)) {
            $this->error(lang('请选择广告素材'));
        }
        $material_id = [];
        $time = [];

        foreach ($details as $k => $v) {
            $v['material_id'] = intval($v['material_id']);
            if ($v['material_id'] > 0) {
                $material_id[] = $v['material_id'];
                $time[] = $v['time'];
            }
        }
        $materials = $this->db->name('ad_material')->where(['id' => ['IN', $material_id], 'not_delete' => 1])->select();
        if (!$materials) {
            $this->error(lang('请选择广告素材'));
        }

        $materials = array_column($materials, null, 'id');
        $params['material_size'] = array_sum(array_column($materials, 'size'));

        foreach ($material_id as $k => $id) {
            if (!isset($materials[$id])) {
                continue;
            }
            $params['material_num'][$materials[$id]['type']] += 1;
            $params['seconds'] += intval($materials[$id]['seconds']);
            $v = [];
            $v['sort'] = $k + 1;
            $v['time'] = intval($time[$k]);
            $v['time'] < 1 && $v['time'] = 5;
            $materials[$id]['type'] == 'video' && $v['time'] = '';
            if ($materials[$id]['type'] == 'image') {
                $params['seconds'] += intval($v['time']);
            }
            $params['details'][] = [
                'material_id' => $id,
                'sort' => $v['sort'],
                'time' => $v['time'],
            ];
        }

//        $sort_field = array_column($params['details'], 'sort');
        //        array_multisort($sort_field, SORT_ASC, $params['details']);
        $params['material_ids'] = implode(",", $material_id);
        $params['material_num'] = json_encode($params['material_num'], JSON_UNESCAPED_UNICODE);
        $params['details'] = json_encode($params['details'], JSON_UNESCAPED_UNICODE);
        return $params;
    }

    /**
     * 修改分组
     */
    public function editGroup()
    {
        $params = $this->_getGroupParams();
        if ($this->db->name('ad_group')->update($params) !== false) {
            $plans = $this->db->name('ad_plan')
                ->where('not_delete = 1')
                ->where('group_ids', 'exp', "	REGEXP '[[:<:]]" . $params['id'] . "[[:>:]]'")
                ->column('id');
            if ($plans) {
                foreach ($plans as $v) {
                    $cache = Cache::store('redis')->handler();
                    $cache->rPush('screen:' . $this->oCode, $v);
                }
            }
            $this->operateLog($params['id'], '修改广告分组');
            return ['code' => 1, 'msg' => lang('操作成功')];
        }
        return ['code' => 0, 'msg' => lang('操作失败')];
    }

    /**
     * 删除分组
     * @param $id 主键id
     * @return array|void
     */
    public function deleteGroup($id, $agency_id = 0)
    {
        $info = $this->db->name('ad_group')->where(['id' => $id, 'not_delete' => 1])->find();
        $agency_id < 1 && $agency_id = 0;
        if (!$info || $info['agency_id'] != $agency_id) {
            return ['code' => 1, 'msg' => lang('删除成功')];
        }
        $num = $this->db->name('ad_plan')
            ->where('not_delete = 1')
            ->where('group_ids', 'exp', "	REGEXP '[[:<:]]" . $id . "[[:>:]]'")
            ->count();
        if ($num > 0) {
            return ['code' => 0, 'msg' => $num . lang('广告计划正在使用该素材')];
        }
        $info['not_delete'] = 0;
        if ($this->db->name('ad_group')->update($info)) {
            $this->operateLog($info['id'], '删除广告分组');
            return ['code' => 1, 'msg' => lang('操作成功')];
        }
        return ['code' => 0, 'msg' => lang('操作失败')];
    }

    public function planDevice($plan_id, $page_size = 20, $isReturn = false)
    {
        $pageParam = ['query' => ['id' => $plan_id]];

        $query = $this->db->name('ad_plan_charecabinet')
            ->alias('a')
            ->join("charecabinet b", 'a.data_id = b.cabinet_id', 'LEFT')
            ->field('b.cabinet_id,b.device_num,b.sid')
            ->where(['a.plan_id' => $plan_id, 'a.not_delete' => 1, 'b.not_delete' => 1])
            ->paginate($page_size, false, $pageParam);
        $list = $query->all();
        $total = $query->total();
        $paginate = $query->render();
        if ($list) {
            $sids = array_column($list, 'sid');
            $seller = $this->db->name('seller')->field('id,name')->where(['id' => ['IN', $sids]])->select();
            $seller = array_column($seller, 'name', 'id');
            foreach ($list as $k => $v) {
                $v['seller'] = empty($v['sid']) ? '' : $seller[$v['sid']];
                $list[$k] = $v;
            }
        }
        if ($isReturn) {
            return ['total' => $total, 'list' => $list];
        }
    }

    /**
     * 计划列表
     */
    public function planList($condition = [], $pages = 20, $isReturn = false)
    {
        $pages < 1 && $pages = 10;
        $where = ['not_delete' => 1];
        $pageParam = ['query' => []];
        if (isset($condition['agency_id']) && !empty($condition['agency_id'])) {
            $pageParam['query']['agency_id'] = $condition['agency_id'];
            $condition['agency_id'] < 1 && $condition['agency_id'] = 0;
            $where['agency_id'] = $condition['agency_id'];
        }
        $name = input('name');
        if ('' != $name) {
            $pageParam['query']['name'] = $name;
            $where['plan_name'] = ['LIKE', "%{$name}%"];
        }
        $device_id = input('device_id');
        if ('' != $device_id) {
            $dids = $this->db->name('ad_plan_charecabinet')->where(['not_delete' => 1, 'data_id' => $device_id])->column('plan_id');
            empty($dids) && $dids = [-1];
            $where['id'] = ['IN', $dids];
        }
        $where['status'] = 1;
        $query = $this->db->name('ad_plan')
            ->where($where)
            ->order(['id' => 'DESC'])
            ->paginate($pages, false, $pageParam);
        $total = $query->total();
        $paginate = $query->render();
        $list = $query->all();
        $ids = array_column($list, 'id');
        $aids = array_column($list, 'agency_id');
        $devices = [];
        $device_query = $this->db->name('ad_plan_charecabinet')->field('plan_id,data_id')->where(['plan_id' => ['IN', $ids], 'not_delete' => 1])->select();
        foreach ($device_query as $v) {
            $devices[$v['plan_id']][] = $v['data_id'];
        }
        $agencies = $this->_getAgency($aids);
        $days = [1 => lang('星期一'), lang('星期二'), lang('星期三'), lang('星期四'), lang('星期五'), lang('星期六'), lang('星期日')];
        foreach ($list as $k => $v) {
            $details = json_decode($v['details'], true);
            foreach ($details as $kk => $detail) {
                $detail['start_hour'] < 10 && $detail['start_hour'] = '0' . $detail['start_hour'];
                $detail['start_minute'] < 10 && $detail['start_minute'] = '0' . $detail['start_minute'];
                $detail['end_hour'] < 10 && $detail['end_hour'] = '0' . $detail['end_hour'];
                $detail['end_minute'] < 10 && $detail['end_minute'] = '0' . $detail['end_minute'];
                $details[$kk] = $detail;
            }
            $list[$k]['details'] = $details;
            $list[$k]['uploader'] = empty($v['agency_id']) ? lang('平台') : $agencies[$v['agency_id']]['name'];
            $list[$k]['created'] = date("Y-m-d H:i:s", $v['create_time']);
            $list[$k]['device'] = isset($devices[$v['id']]) ? $devices[$v['id']] : [];
        }

        if ($isReturn) {
            return ['total' => $total, 'list' => $list];
        }
        //有查询则赋值
        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
        $this->assign('list', $list);
        $this->assign('paginate', $paginate);
        $this->assign('title', lang('广告投放计划') . lang('列表'));
    }

    /**
     * @param int $agency_id
     * @return array
     * TODO 添加计划
     */
    public function addPlan($agency_id = 0)
    {
        $agency_id < 1 && $agency_id = 0;
        $params = $this->_getPlanParams();
        $params['create_time'] = time();
        $params['agency_id'] = $agency_id;
        $params['status'] = 1;
        $equipment_group = $params['equipment_group'];
        unset($params['equipment_group']);
        if ($id = $this->db->name('ad_plan')->insertGetId($params)) {

            foreach ($equipment_group as $v) {
                $equipments[] = [
                    'data_id' => $v,
                    'plan_id' => $id,
                    'not_delete' => 1,
                ];
            }
            if ($params['channel'] == 1) {
                $this->db->name('ad_plan_agency')->insertAll($equipments);
            } else {
                $this->db->name('ad_plan_charecabinet')->insertAll($equipments);
            }
            $cache = Cache::store('redis')->handler();
            $cache->rPush('screen:' . $this->oCode, $id);
            $this->operateLog($id, '添加广告计划');
            return ['code' => 1, 'msg' => lang('操作成功')];
        }
        return ['code' => 0, 'msg' => lang('操作失败')];
    }


    function getDeviceLog($agency_id, $equipment_group)
    {
        //获取设备本月免费投放次数
        $freeDevice = \think\Loader::model('Equipment', 'logic')->getFreeLog($equipment_group);
        !empty($freeDevice) && $freeDevice = array_column($freeDevice, 'free_num', 'device_id');
        $charge_device = [];//投放需要收费的设备
        $use_device = [];//投放免费的设备
        foreach ($equipment_group as $v) {
            if (isset($freeDevice[$v]) && $freeDevice[$v] >= 2) {
                $charge_device[] = $v;
            } else {
                $use_device[] = $v;
            }
        }
        $charge_num = count($charge_device);
        $charge_amount = bcmul(8, $charge_num, 2);
        $account = $this->db->name('account')->where('id', $agency_id)->find();
        if (!$account || bcsub($account['recharge_balance'], $charge_amount, 2) < 0) {
            return ['code' => 301, 'msg' => '余额不足', 'qc_code' => config('qcloudurl') . '/Applets/wx_code.png', 'amount' => bcsub($charge_amount, $account['recharge_balance'], 2)];
        }
        return ['code' => 1, 'charge_device' => $charge_device, 'free_device' => $use_device, 'charge_num' => $charge_num, 'charge_amount' => $charge_amount];
    }

    /**
     * @param $id
     * @param $agency_id
     * @param $equipment_group
     * @param $free_device
     * @param $charge_num
     * TODO 广告扣费记录
     */
    function deviceConsume($id, $agency_id, $equipment_group, $free_device, $charge_num)
    {
        $month = date('Ym');
        $equipments = [];
        foreach ($equipment_group as $v) {
            $equipments[] = [
                'data_id' => $v,
                'plan_id' => $id,
                'not_delete' => 1,
            ];
        }
        if ($charge_num > 0) {
            $charge_amount = $charge_num * 8;
            //扣减金额
            $this->db->name('account')->where('relation_id', $agency_id)->update(['balance' => ['exp', 'recharge_balance-' . $charge_amount]]);
            //记录扣费记录
            $insert = [
                'agency_id' => $agency_id,
                'amount' => $charge_amount,
                'num' => $charge_num,
                'plan_id' => $id,
                'type' => 1,
                'devices' => implode(',', $equipment_group),
                'create_time' => time(),
            ];
            $this->db->name("screen_consume_log")->insert($insert);
        }
        $free_params = [];
        foreach ($free_device as $v) {
            $free_params[] = [
                'plan_id' => $id,
                'month' => $month,
                'device_id' => $v,
            ];
        }
        !empty($free_params) && $this->db->name('screen_free_log')->insertAll($free_params);
        $equipments && $this->db->name('ad_plan_charecabinet')->insertAll($equipments);
    }


    /**
     * 代理添加广告计划
     */
    public function addAgencyPlan($agency_id)
    {
        $agency_id < 1 && $agency_id = 0;
        $params = $this->_getPlanParams();
        $params['create_time'] = time();
        $params['agency_id'] = $agency_id;
        $params['status'] = 2;
        $equipment_group = $params['equipment_group'];
        unset($params['equipment_group']);
        $check = $this->getDeviceLog($agency_id, $equipment_group);
        if ($check['code'] != 1) {
            return $check;
        }

        $this->db->startTrans();
        try {
            $id = $this->db->name('ad_plan')->insertGetId($params);
            $this->deviceConsume($id, $agency_id, $equipment_group, $check['free_device'], $check['charge_num']);
            $cache = Cache::store('redis')->handler();
            $cache->rPush('screen:' . $this->oCode, $id);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            save_log('sql', '代理添加广告失败:' . $e->getMessage());
            return ['code' => 0, 'msg' => lang('操作失败')];
        }
        $this->operateLog($this->auth_info['uid'],'代理商添加广告');
        return ['code' => 1, 'msg' => lang('操作成功')];
    }


    /**
     * 处理广告退款
     */
    public function refund($plan_id)
    {
        //删除免费广告使用记录
        $this->db->name('screen_free_log')->where('plan_id', $plan_id)->update(['not_delete' => 0]);
        //修改收费广告日志为退款
        $screen_consume_log = $this->db->name('screen_consume_log')->where(['plan_id' => $plan_id, 'type' => 1])->order('ID DESC')->find();
        if (!empty($screen_consume_log)) {
            $this->db->name('screen_consume_log')->where('id', $screen_consume_log['id'])->update(['type' => 2]);
            //退款
            $account = $this->db->name('account')->find($screen_consume_log['agency_id']);
            $this->db->name('account')->where('id', $account['id'])->update(['total_amount' => bcadd($account['total_amount'], $screen_consume_log['amount'], 4)]);
        }
    }

    /**
     * 修改代理广告计划
     */
    public function editAgencyPlan($agency_id)
    {
        $params = $this->_getPlanParams();
        $params['update_time'] = time();
        $params['status'] = 2;
        $equipment_group = $params['equipment_group'];
        unset($params['equipment_group']);
        $plan = $this->db->name('ad_plan')->find($params['id']);
        if ($plan['agency_id'] != $agency_id) {
            return ['code' => 0, 'msg' => lang('操作失败')];
        }
        //判断是否已经通过的审核
//        if($plan['status'] == 1){
//            $plan_chare = $this->db->name('ad_plan_charecabinet')->where(['not_delete' => 1, 'plan_id' => $plan['id']])->field('data_id')->select();
//            if(!empty($plan_chare)) {
//                $plan_chare = array_column($plan_chare, 'data_id');
//                $equipment_group = array_diff($equipment_group, $plan_chare);
//            }
//        }
        $check = $this->getDeviceLog($agency_id, $equipment_group);
        if ($check['code'] != 1) {
            return $check;
        }
        $this->db->startTrans();
        try {
            $this->db->name('ad_plan')->update($params);
            $this->deviceConsume($params['id'], $agency_id, $equipment_group, $check['free_device'], $check['charge_num']);
            $this->db->commit();
            $this->operateLog($this->auth_info['uid'],'修改代理商广告计划');
            return ['code' => 1, 'msg' => lang('修改成功')];
        } catch (Exception $e) {
            $this->db->rollback();
            save_log('sql', '代理修改广告计划失败:' . $e->getMessage());
            return ['code' => 0, 'msg' => lang('修改失败')];
        }
        $cache = Cache::store('redis')->handler();
        $cache->rPush('screen:' . $this->oCode, $params['id']);
        return ['code' => 0, 'msg' => lang('修改失败')];
    }

    /**
     * 删除代理广告计划
     * @param $id 主键id
     * @return array|void
     */
    public function deleteAgencyPlan($id, $agency_id = 0)
    {
        $agency_id < 1 && $agency_id = 0;
        $info = $this->db->name('ad_plan')->where(['id' => $id, 'not_delete' => 1])->find();
        if (!$info || $info['agency_id'] != $agency_id) {
            return ['code' => 0, 'msg' => lang('数据不存在')];
        }
        //开启事务
        $this->db->startTrans();
        try {
            $info['not_delete'] = 0;
            $this->db->name('ad_plan')->update($info);
            $this->db->name('ad_plan_charecabinet')->where(['plan_id' => $id])->delete();
            $this->db->name('ad_plan_agency')->where(['plan_id' => $id])->delete();
            $this->operateLog($this->auth_info['uid'],'删除代理商广告计划');
            $this->db->commit();
            return ['code' => 1, 'msg' => lang('删除成功')];
        } catch (Exception $e) {
            $this->db->rollback();
            sav_log('sql', '删除代理计划失败:' . $e->getMessage());
        }

        return ['code' => 0, 'msg' => lang('删除失败')];
    }

    /**
     * 广告计划参数
     */
    private function _getPlanParams()
    {
        $params = [
            'id' => input('post.id', 0, 'intval'),
            'plan_name' => input('post.plan_name', '', 'trim'),
            'start_date' => input('post.start_date'),
            'end_date' => input('post.end_date'),
            'remark' => input('post.remark'),
            'details' => [],
            'not_delete' => 1,
            'channel' => input('post.channel', 1, 'intval'),
            'model' => input('post.model', 'vertical', 'trim'),
        ];
        !in_array($params['model'], ['vertical', 'cross', 'cross_touch']) && $params['model'] = 'vertical';
        $cycle = input('post.week/a', []);
        $group_id = input('post.group_id/a', []);
        $start_hour = [];
        $start_minute = [];
        $end_hour = [];
        $end_minute = [];
        $equipment_group = input('equipment_group');
        $equipment_group = array_unique(explode(",", $equipment_group));

        if (empty($params['plan_name'])) {
            $this->error(lang('请输入名称'));
        }

        !preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/', $params['start_date']) && $this->error(lang('请选择开始日期'));
        !preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/', $params['end_date']) && $this->error(lang('请选择结束日期'));

        $details = input('details');
        $details = json_decode($details, true);
        if (empty($details) || !is_array($details)) {
            $this->error(lang('请添加广告投放计划'));
        }
        foreach ($details as $k => $v) {
            $v['group_id'] = intval($v['group_id']);
            if ($v['group_id'] < 1) {
                continue;
            }
            $group_id[] = $v['group_id'];
            $start_hour[] = $v['start_hour'];
            $start_minute[] = $v['start_minute'];
            $end_hour[] = $v['end_hour'];
            $end_minute[] = $v['end_minute'];
        }

        $groups = $this->db->name('ad_group')->where(['id' => ['IN', $group_id], 'not_delete' => 1])->select();
        if (!$groups) {
            $this->error(lang('请选择') . lang('广告分组'));
        }
        $groups = array_column($groups, null, 'id');
        //代理商分组
        if (empty($equipment_group) || !is_array($equipment_group)) {
            $this->error(lang('请选择') . lang('投放渠道'));
        }
        if (1 == $params['channel']) {
            $equipment_group_ids = $this->db->name('agency')->field('id')->where(['id' => ['IN', $equipment_group], 'not_delete' => 1])->column('id');
            foreach ($equipment_group as $v) {
                if ($v == '0') {
                    $equipment_group_ids[] = 0;
                }
            }
            empty($equipment_group_ids) && $this->error(lang('请选择') . lang('代理商'));
        } else {
            $equipment_group_ids = $this->db->name('charecabinet')->field('cabinet_id')->where(['cabinet_id' => ['IN', $equipment_group], 'not_delete' => 1])->column('cabinet_id');
            empty($equipment_group_ids) && $this->error(lang('请选择') . lang('投放机柜'));
        }

        $times = []; //时间段
        foreach ($group_id as $k => $id) {
            if (!isset($groups[$id])) {
                continue;
            }
            $start = intval($start_hour[$k] . $start_minute[$k]);
            $end = intval($end_hour[$k] . $end_minute[$k]);
            if ($start >= $end) {
                $this->error(lang('广告分组') . "【" . $groups[$id]['name'] . "】" . lang('结束时间需大于开始时间'));
            }
            if ($times) {
                foreach ($times as $time) {
                    $conflict = $this->timeConflict($start, $end, $time['start'], $time['end']);
                    $conflict && $this->error(lang('广告分组') . "【" . $groups[$id]['name'] . "】" . lang('与其他计划时间冲突'));
                }
            }
            $times[] = ['start' => $start, 'end' => $end];
            $v = [];
            $v['group_id'] = $id;
            $v['group_name'] = $groups[$id]['name'];
            $v['start_hour'] = intval($start_hour[$k]);
            $v['start_minute'] = intval($start_minute[$k]);
            $v['end_hour'] = intval($end_hour[$k]);
            $v['end_minute'] = intval($end_minute[$k]);
            $params['details'][] = $v;
        }

        $repeat = $this->checkDeviceRepeat($params['start_date'], $params['end_date'], $equipment_group_ids, $params['id']);
        if ($repeat) {
            $this->error("机柜 " . implode("、", $repeat) . " 投放周期重叠");
        }
        //先去掉分屏
        $ad_position_id = input('ad_position_id', 0, 'intval');
//        $position = $this->db->name('ad_position')->where(['id' =>$ad_position_id])->find();
        //        if(!$position){
        //            $this->error(lang('请设置屏幕尺寸'));
        //        }
        $position = '{"top_height":0,"middle_height":0,"bottom_height":190,"plan_height":1730,"top_image":"","middle_image":"","bottom_image":""}';

        empty($ad_position_id) && $ad_position_id = $this->db->name('ad_position')->insertGetId(['create_time' => time(), 'data' => $position]);

        $params['ad_position_id'] = $ad_position_id;
        $params['group_ids'] = implode(",", $group_id);
        $params['details'] = json_encode($params['details'], JSON_UNESCAPED_UNICODE);
        $params['equipment_group'] = $equipment_group_ids;
        return $params;
    }

    public function timeConflict($beginTime1 = 0, $endTime1 = 0, $beginTime2 = 0, $endTime2 = 0)
    {
        $beginTime1 = intval($beginTime1);
        $endTime1 = intval($endTime1);
        $beginTime2 = intval($beginTime2);
        $endTime2 = intval($endTime2);
        $status = $beginTime2 - $beginTime1;
        if ($status > 0) {
            $status2 = $beginTime2 - $endTime1;
            if ($status2 >= 0) {
                return false; // 无交集
            }
            return true; // 有交集
        } else {
            $status2 = $endTime2 - $beginTime1;
            if ($status2 > 0) {
                return true;
            }
            return false;
        }
    }

    public function checkDeviceRepeat($start, $end, $groups, $id = 0)
    {
        $query = $this->db->name('ad_plan_charecabinet')
            ->alias('a')
            ->join('ad_plan p', 'p.id = a.plan_id')
            ->field('a.*, p.start_date, p.end_date')
            ->where(['a.not_delete' => 1, 'data_id' => ['IN', $groups]])
            ->select();
        $ids = [];
        foreach ($query as $v) {
            if ($id == $v['plan_id']) {
                continue;
            }
//            if (($start <= $v['end_date'] && $start >= $v['start_date']) || ($end <= $v['end_date']) && $end >= $v['start_date']) {
//                $ids[] = $v['data_id'];
//            }
        }
        return $ids;
    }

    public function editPlan()
    {
        $params = $this->_getPlanParams();
        $params['update_time'] = time();
        $equipment_group = $params['equipment_group'];
        unset($params['equipment_group']);
        if ($this->db->name('ad_plan')->update($params) != false) {
            foreach ($equipment_group as $v) {
                $equipments[] = [
                    'data_id' => $v,
                    'plan_id' => $params['id'],
                    'not_delete' => 1,
                ];
            }
            $this->db->name('ad_plan_charecabinet')->where(['plan_id' => $params['id']])->delete();
            $this->db->name('ad_plan_agency')->where(['plan_id' => $params['id']])->delete();
            if ($params['channel'] == 1) {
                $this->db->name('ad_plan_agency')->insertAll($equipments);
            } else {
                $this->db->name('ad_plan_charecabinet')->insertAll($equipments);
            }

            $cache = Cache::store('redis')->handler();
            $cache->rPush('screen:' . $this->oCode, $params['id']);
            $this->operateLog($params['id'], '修改广告计划信息');
            return ['code' => 1, 'msg' => lang('修改成功'), 'r' => 'screen:' . $this->oCode];
        }
        return ['code' => 0, 'msg' => lang('修改失败')];
    }

    /**
     * 删除计划
     * @param $id 主键id
     * @return array|void
     */
    public function deletePlan($id, $agency_id = 0)
    {
        $agency_id < 1 && $agency_id = 0;
        $info = $this->db->name('ad_plan')->where(['id' => $id, 'not_delete' => 1])->find();
        if (!$info || $info['agency_id'] != $agency_id) {
            return ['code' => 0, 'msg' => lang('数据不存在')];
        }
        $info['not_delete'] = 0;
        if ($this->db->name('ad_plan')->update($info)) {
            $this->db->name('ad_plan_charecabinet')->where(['plan_id' => $id])->delete();
            $this->db->name('ad_plan_agency')->where(['plan_id' => $id])->delete();
            $querystring = lang('删除') . lang('广告投放计划') . $info['id'] . lang('信息');
            $this->operateLog($info['id'], '删除广告投放计划');
            return ['code' => 1, 'msg' => lang('删除成功')];
        }
        return ['code' => 0, 'msg' => lang('删除失败')];
    }

    public function sizeSet()
    {
        $top_height = input('top_height', 0, 'intval');
        $middle_height = input('middle_height', 0, 'intval');
        $top_image = input('top_image', 0, 'intval');
        $middle_image = input('middle_image', 0, 'intval');
        $bottom_image = input('bottom_image', 0, 'intval');
        $is_top = input('is_top');
        $is_middle = input('is_middle');

        $height = 1920 - 190;
        $is_top != 'true' && $top_height = 0;
        $is_middle != 'true' && $middle_height = 0;
        $height -= $top_height;
        $height -= $middle_height;
        if ($height < 730) {
            $this->error(lang('头部+中部广告位总高度不能超过1000像素'));
        }
        empty($top_image) && $top_image = '';
        empty($middle_image) && $middle_image = '';
        empty($bottom_image) && $bottom_image = '';
        $id = input('id', 0, 'intval');
        $info = $this->db->name('ad_position')->where(['id' => $id])->find();
        if ($info) {
            $info['data'] = json_decode($info['data'], true);
            foreach ($info['data'] as $k => $v) {
                $info[$k] = $v;
            }
        }

        if ($is_top == 'true') {
            if ($top_height < 1) {
                $this->error(lang('请设置头部广告位高度'));
            }
            if (!empty($info['top_image']) && empty($top_image)) { //修改
                $top_image = $info['top_image'];
            } else {
                if (empty($top_image)) {
                    $this->error(lang('请设置头部广告'));
                }
                $file = $this->_userFile($top_image, 'screen', true);
                if ($file['code'] != 1) {
                    $this->error(lang('上传头部广告失败，请重试'));
                }
                $top_image = $file['file'];
            }

        }
        if ($is_middle == 'true') {
            if (!empty($info['middle_image']) && empty($middle_image)) { //修改
                $middle_image = $info['middle_image'];
            } else {
                if ($middle_height < 1) {
                    $this->error(lang('请设置中部广告位高度'));
                }
                if (empty($middle_image)) {
                    $this->error(lang('请设置中部广告'));
                }
                $file = $this->_userFile($middle_image, 'screen', true);
                if ($file['code'] != 1) {
                    $this->error(lang('上传中部广告失败，请重试'));
                }
                $middle_image = $file['file'];
            }
        }
        if (!empty($bottom_image)) {
            $file = $this->_userFile($bottom_image, 'screen', true);
            if ($file['code'] != 1) {
                $this->error(lang('上传底部广告失败，请重试'));
            }
            $bottom_image = $file['file'];
        } else if (!empty($info['middle_image'])) {
            $bottom_image = $info['middle_image'];
        }
        $params = [
            'top_height' => $top_height,
            'middle_height' => $middle_height,
            'bottom_height' => 190,
            'plan_height' => $height,
            'top_image' => $top_image,
            'middle_image' => $middle_image,
            'bottom_image' => $bottom_image,
        ];
        $params = [
            'data' => json_encode($params, JSON_UNESCAPED_UNICODE),
        ];
        if ($info) {
            $params['update_time'] = time();
            $this->db->name('ad_position')->where(['id' => $id])->update($params);
        } else {
            $params['create_time'] = time();
            $id = $this->db->name('ad_position')->insertGetId($params);
        }

        return ['code' => 1, 'msg' => lang('保存成功'), 'data' => ['id' => $id]];
    }

    /**
     * 发送指令
     * @param $data
     * @return bool
     */
    public function send($data)
    {
        $client = stream_socket_client('tcp://127.0.0.1:8089');
        if (!$client) {
            return false;
        }
        $time = time();
        $params = [
            'p1' => $data['command'], //指令
            'p2' => $time,
            'p3' => md5(config('headerkey') . $time),
            'p4' => 'screen-' . $data['equipment_id'],
        ];
        isset($data['url']) && $params['url'] = $data['url'];
        isset($data['version']) && $params['aims'] = $data['version'];
        $command = 'CMD:' . json_encode($params);
        fwrite($client, $command);
        return true;
    }

    public function refreshPlan()
    {

    }

    /**
     * 代理商广告计划审核列表
     */
    public function agencyPlanList($where, $pages = 20)
    {
        $pages < 1 && $pages = 10;
        $where['not_delete'] = 1;
        $device_id = input('device_id');
        if ('' != $device_id) {
            $dids = $this->db->name('ad_plan_charecabinet')->where(['not_delete' => 1, 'data_id' => $device_id])->column('plan_id');
            empty($dids) && $dids = [-1];
            $where['id'] = ['IN', $dids];
        }

        $query = $this->db->name('ad_plan')
            ->where($where)
            ->order(['id' => 'DESC'])
            ->paginate($pages);
        $total = $query->total();
        $list = $query->all();
        $aids = array_column($list, 'agency_id');
        $agencies = $this->_getAgency($aids);
        $days = [1 => lang('星期一'), lang('星期二'), lang('星期三'), lang('星期四'), lang('星期五'), lang('星期六'), lang('星期日')];
        foreach ($list as $k => $v) {
            $details = json_decode($v['details'], true);
            foreach ($details as $kk => $detail) {
                $detail['start_hour'] < 10 && $detail['start_hour'] = '0' . $detail['start_hour'];
                $detail['start_minute'] < 10 && $detail['start_minute'] = '0' . $detail['start_minute'];
                $detail['end_hour'] < 10 && $detail['end_hour'] = '0' . $detail['end_hour'];
                $detail['end_minute'] < 10 && $detail['end_minute'] = '0' . $detail['end_minute'];
                $details[$kk] = $detail;
            }
            $list[$k]['details'] = $details;
            $list[$k]['uploader'] = empty($v['agency_id']) ? lang('平台') : $agencies[$v['agency_id']]['name'];
            $list[$k]['created'] = date("Y-m-d H:i:s", $v['create_time']);
        }

        return ['total' => $total, 'list' => $list];
    }

    //审核代理商广告计划
    public function agencyPlanStatus($id, $status, $refuse_memo = '', $operator = 'admin')
    {
        if ($status == 3 && $refuse_memo == '') {
            return ['code' => 0, 'msg' => lang('拒绝信息不能为空')];
        }
        $ad_plan = $this->db->name('ad_plan')->find($id);
        if (empty($ad_plan) || $ad_plan['status'] != 2) {
            return ['code' => 0, 'msg' => lang('审核失败')];
        }
        $this->db->startTrans();
        try {
            if ($status == 1) {
                $this->db->name('ad_plan')->where(['id' => $id])->update(['update_time' => time(), 'status' => $status]);
                $memo = '通过广告代理计划';
            } elseif ($status == 3) {
                $this->db->name('ad_plan')->where(['id' => $id])->update(['update_time' => time(), 'status' => $status, 'refuse_memo' => $refuse_memo]);
                $this->db->name('screen_free_log')->where(['plan_id' => $id])->update(['not_delete' => 0]);
                //进行退款操作
                $consume_log = $this->db->name('screen_consume_log')->where(['type' => 1, 'plan_id' => $id])->find();
                if (!empty($consume_log)) {
                    $this->db->name('screen_consume_log')->where('id', $consume_log['id'])->update(['type' => 2]);
                    $this->db->name('account')
                        ->where(['id' => $consume_log['agency_id']])
                        ->update([
                            'recharge_balance' => ['exp', 'recharge_balance+' . $consume_log['amount']]
                        ]);
                }
                $memo = '拒绝广告代理计划,拒绝原因:' . $refuse_memo;
            }
            $operator == 'admin' && $this->operateLog($id, $memo);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            save_log('sql', '审核代理商广告计划失败:' . $e->getMessage());
            return ['code' => 0, 'msg' => '审核失败'];
        }
        return ['code' => 1, 'msg' => lang('操作成功')];
    }

    /**
     * 获取广告价格
     */
    public function getPrice()
    {
        $equipment_group = input('equipment_group', '', 'trim');
        if (empty($equipment_group)) {
            return ['code' => 0, 'msg' => '设备ID不能为空'];
        }
        $equipment_group = explode(',', $equipment_group);
        $freeDevice = \think\Loader::model('Equipment', 'logic')->getFreeLog($equipment_group);
        if (!empty($freeDevice)) {
            $freeDevice = array_column($freeDevice, 'free_num', 'device_id');
        }
        $charge_device = [];
        $useDevice = [];
        $month = date('Ym');
        foreach ($equipment_group as $v) {
            if (isset($freeDevice[$v]) && $freeDevice[$v] >= 2) {
                $charge_device[] = $v;
            }
        }
        $charge_amount = count($charge_device) * 8;
        return $charge_amount;
    }

    /**
     * 代理计划详情
     */
    public function agencyPlanDetail()
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

        $info['details'] = json_decode($info['details'], true);
        $group_ids = array_column($info['details'], 'group_id');
        $groups = $this->db->name('ad_group')->where(['id' => ['in', $group_ids], 'not_delete' => 1])->select();
        $groups = array_column($groups, NULL, 'id');
        foreach ($info['details'] as $k => $v) {
            $v['size'] = $this->fileSizeToStr($groups[$v['group_id']]['material_size']);
            $v['duration'] = $this->timeFormat($groups[$v['group_id']]['seconds']);
            $info['details'][$k] = $v;
        }
        $equipment_group = [];

        $aids = $this->db->name('ad_plan_charecabinet')->field('data_id')->where(['plan_id' => $id, 'not_delete' => 1])->column('data_id');
        $query = $this->db->name('charecabinet')->field('cabinet_id,device_num')->where(['cabinet_id' => ['IN', $aids], 'not_delete' => 1])->select();
        $freeDevice = \think\Loader::model('Equipment', 'logic')->getFreeLog(array_column($query, 'cabinet_id'));
        !empty($freeDevice) && $freeDevice = array_column($freeDevice, 'free_num', 'device_id');
        foreach ($query as $v) {
            $free_num = isset($freeDevice[$v['cabinet_id']]) ? $freeDevice[$v['cabinet_id']] : 0;
            $equipment_group[] = [
                'data_id' => $v['cabinet_id'],
                'name' => $v['cabinet_id'],
                'num' => intval($v['device_num']),
                'free_num' => ($free_num < 3) ? 2 - $free_num : 0,
            ];
        }
        $info['equipment_group'] = $equipment_group;

        return $info;
    }

    /**
     * 获取广告计划素材详情
     */
    public function getPlanSource()
    {
        $id = input('id', '', 'trim');
        if (!$id) {
            return ['code' => 0, 'msg' => '计划ID不能为空'];
        }
        $data = $this->db->name('ad_plan')->where('not_delete', 1)->find($id);
        if (empty($data)) {
            return [];
        }
        $group_ids = explode(',', $data['group_ids']);
        $material = $this->db->name('ad_group')->where('id', 'in', $group_ids)->field('group_concat(material_ids) material_ids')->find();
        if (!empty($material['material_ids'])) {
            $material = explode(',', $material['material_ids']);
            $material_ids = array_unique($material);
            return $this->db->name('ad_material')->where(['id' => ['IN', $material_ids], 'not_delete' => 1])->select();
        }
        return [];
    }

    /**
     * 上传图片到远程
     */
    public function  uploadCloud(){
        $path = input('path', '','trim');
        if(empty($path)){
            $this->errorResponse(0, '路径不能为空');
        }
        $data = [
            'path' => $path,
            'create_time' => time(),
        ];
        $id = $this->db->name("cloud_upload_files")->insertGetId($data);
        return ['code' => 0, 'data' => ['id' => $id]];
    }


    /**
     * 修改文件为已使用
     */
    protected function _userFile($id, $folder = '', $is_path = false)
    {
//        $info = $this->db->name('upload_files')->where(['id' => $id])->find();
//        if (!$info) {
//            return ['code' => 0, 'msg' => lang('上传图片失败，请重新上传')];
//        }
//        $path = config('website').'/uploads/'.$info['path'];
//        $this->db->name('upload_files')->where(['id'=>$id])->update(['status' => 1]);
//        $header_array = get_headers($path, true);
//        $size = $header_array['Content-Length'];
//        return [
//            'code' => 1,
//            'file' => $path,
//            'size' => $size,
//            'path' => $info['path'],
//        ];
        $info = $this->db->name('upload_files')->where(['id' => $id])->find();
        if (!$info) {
            return ['code' => 0, 'msg' => lang('上传图片失败，请重新上传')];
        }
        $path =  ROOT_PATH . 'public/uploads/' . $info['path'];
        $this->db->name('upload_files')->where(['id'=>$id])->update(['status' => 1]);
//        $file = config('qcloudurl')."/".$info['path'];
        $file = config('website')."/uploads/".$info['path'];


        return ['code' => 1,
            'file' => $file,
            'size' => filesize($path),
            'path' => ROOT_PATH . 'public/uploads/' . $info['path'],
        ];
    }



}
