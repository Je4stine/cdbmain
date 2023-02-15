<?php

namespace app\common\logic;

use app\common\service\Office;
use think\Loader;
use ZipArchive;


/**
 * 机柜
 * @package app\common\logic
 */
class Equipment extends Common
{
    var $lineTime = 50;

    public function _initialize()
    {
        parent::_initialize();
        $this->lineTime = config('online_time');
    }


    /**
     * 列表
     */
    public function equipmentList($condition = [], $pages = 20, $isReturn = false)
    {
    	$this->lang = "'$.{$this->lang}'";
        $pages < 1 && $pages = 20;

        $where = ['c.not_delete' => 1];
        $pageParam = ['query' => []];
        //商家
        $sid = input('sid', 0, 'intval');
        isset($condition['sid']) && $sid = $condition['sid'];
        if (!empty($sid)) {
            $where['c.sid'] = $sid;
            $pageParam['query']['sid'] = $sid;
            $pageParam['query']['seller_name'] = input('seller_name');
        }

        //判断是否有按店铺名查询
        $name = input('name', '', 'trim');
        if ('' != $name) {
            $where['s.name'] = ['LIKE', "%{$name}%"];
            $pageParam['query']['name'] = $name;
        }
        //判断区域查询
        $area = input('area', '', 'trim');
        if ('' != $area) {
            $where['s.area'] = ['LIKE', "%{$area}%"];
            $pageParam['query']['area'] = $area;
        }
        //机柜号
        $cabinet_id = input('cabinet_id', '', 'trim');
        if ('' != $cabinet_id) {
            $where['c.cabinet_id'] = ['LIKE', "%{$cabinet_id}%"];
            $pageParam['query']['cabinet_id'] = $cabinet_id;
        }

        //二维码
        $qrcode = input('qrcode', '', 'trim');
        if ('' != $qrcode) {
            $where['c.qrcode'] = ['LIKE', "%{$qrcode}%"];
            $pageParam['query']['qrcode'] = $qrcode;
        }

        //屏幕型号
        $model = input('model', '', 'trim');
        if (isset($condition['screen'])) {
            if ('cross' == $condition['screen']) {
                $where['c.model'] = 'pm8';
            } else if ('cross_touch' == $condition['screen']) {
                $where['c.model'] = 'cp8';
            } else {
                $where['c.model'] = ['IN', ['gs24','gs48']];
            }
        }else if ('' != $model) {//型号
            $where['c.model'] = $model;
            $pageParam['query']['model'] = $model;
        }

        //代理商
        $agency_id = input('agency_id', 0, 'intval');
        isset($condition['agency_id']) && $agency_id = $condition['agency_id'];

        if (!empty($agency_id)) {
            $pageParam['query']['agency_id'] = $agency_id;
            ($agency_id == '-1') && $agency_id = 0;//直营
            $where['c.agency_id'] = $agency_id;
            $pageParam['query']['agency_name'] = input('agency_name');
        }

        //业务员
        $employee_id = input('employee_id', 0, 'intval');
        isset($condition['employee_id']) && $employee_id = $condition['employee_id'];
        if (!empty($employee_id)) {
            $where['c.employee_id'] = $employee_id;
            $pageParam['query']['employee_id'] = $employee_id;
        }


        $is_online = input('is_online', 0, 'intval');
        if (1 == $is_online) {
            $where['c.heart_time'] = ['>', time() - config('online_time')];
            $where['c.is_online'] = 1;
            $pageParam['query']['is_online'] = $is_online;
        }


        $query = $this->db->name('charecabinet')
            ->alias('c')
            ->join("seller s", 'c.sid = s.id', 'LEFT')
            ->field("c.id,c.cabinet_id,c.qrcode,c.is_online,c.model,c.mode,c.is_fault,c.device_num,c.heart_time,c.agency_id,c.employee_id, JSON_UNQUOTE(s.name->$this->lang) name,s.area,s.address")
            ->where($where);
        if (2 == $is_online) {//离线，拼装sql
            $outline_day = input('outline_day', 0, 'intval');
            $online_time = time() - config('online_time');
            if (!empty($outline_day)) {
                $online_time = time() - $outline_day * 86400;
            }
            $query->where('c.heart_time < :time OR c.is_online = :is_online ', ['time' => $online_time, 'is_online' => 0]);
            $pageParam['query']['is_online'] = $is_online;
        }

        $query = $query->order('c.create_time desc')->paginate($pages, false, $pageParam);
        $paginate = $query->render();
        $list = $query->all();
        $total = $query->total();

        $time = time();
        $models = config('equipment');
        foreach ($models as $key => $value)
        {
            $models[$key]['name'] = lang($value['name']);
        }
        $storage = \think\Loader::model('Storage', 'service');


        //统计在线设备数量
        $online_num = $this->db->name('charecabinet')
            ->where(['not_delete' => 1, 'is_online' => 1, 'heart_time' => ['>', time() - config('online_time')]])
            ->count();

        $outline_num = $this->db->name('charecabinet')
            ->where(['not_delete' => 1, 'heart_time' => ['ELT', time() - config('online_time')]])
            ->count();

        $stock_num = $online_num + $outline_num;


        if ($list) {
            $model = \think\Loader::model('Agency');
            $allAgency = $model->allAgency();
            $employee_ids = array_column($list, 'employee_id');
            $employees = $this->db->name('agency')
                ->field('id,name')
                ->where(['not_delete' => 1, 'type' => 2, 'id' => ['IN', $employee_ids]])
                ->select();
            $employees = array_column($employees, 'name', 'id');

            foreach ($list as $k => $v) {
                $cache = $this->getEquipmentCache($v['cabinet_id']);

                $heartbeattime = 0;
                $v['return_num'] = $v['device_num'];
                $v['borrow_num'] = 0;
                $v['ip'] = '';
                $v['line'] = 'offline';
                if ($cache) {
                    //$heartbeattime = $v['heart_time'];
                    $heartbeattime = $cache['heart_time'];
                    $v['borrow_num'] = intval($cache['stock_num']);
                    $v['return_num'] = $v['device_num'] - $v['borrow_num'];
                    $v['return_num'] < 0 && $v['return_num'] = 0;
                    $v['ip'] = $cache['ip'];
                }
                if (($time - $heartbeattime) < $this->lineTime && $v['is_online'] == 1) {
                    $v['line'] = 'online';
                }
                $v['model'] = $models[$v['model']]['name'];
                $v['name'] = empty($v['name']) ? '' : $v['name'];
                $v['agency_name'] = '';
                if (!empty($v['agency_id'])) {
                    $parent = $model->getParents($allAgency, $v['agency_id']);
                    $parent = array_column($parent, 'name');
                    $v['agency_name'] = implode(" > ", $parent);
                    $v['employee_name'] = isset($employees[$v['employee_id']]) ? $employees[$v['employee_id']] : '';
                }
                $list[$k] = $v;
            }
        }

        if ($isReturn) {
            return ['total' => $total, 'list' => $list, 'online_num' => $online_num, 'outline_num' => $outline_num, 'stock_num' => $stock_num];
        }

        $this->assign('stock_num', $stock_num);
        $this->assign('online_num', $online_num);
        $this->assign('outline_num', $outline_num);
        //有查询则赋值
        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
        $this->assign('list', $list);
        $this->assign('paginate', $paginate);

    }

    /**
     * 添加机柜
     */
    public function add($data)
    {
        $params = $this->_getParams($data);
        $validate = \think\Loader::validate('Equipment');
        if (!$validate->scene('add')->check($params)) {
            return ['code' => 0, 'msg' => lang($validate->getError())];
        }
        if (!empty($params['sid'])) {
            $seller = $this->db->name('seller')->where(['id' => $params['sid']])->find();
            if (intval($seller['agency_id']) !== intval($params['agency_id'])) {
                return ['code' => 0, 'msg' => lang('店铺不属于代理')];
            }
        }
        $params['create_time'] = time();

        //有删除历史数据则恢复
        $info = $this->db->name('charecabinet')
            ->where(['cabinet_id' => $params['cabinet_id']])
            ->find();

        if ($info) {
            $id = $info['id'];
            $params['not_delete'] = 1;
            $this->db->name('charecabinet')
                ->where(['id' => $info['id']])
                ->update($params);
            $this->agencyRelation($params, $params['agency_id']);
            $this->operateLog($id, '恢复机柜');
            return ['code' => 1, 'msg' => lang('操作成功')];
        }
        if ($id = $this->db->name('charecabinet')->insertGetId($params)) {
            $this->agencyRelation($params, $params['agency_id']);
            $this->operateLog($id, '添加机柜');
            return ['code' => 1, 'msg' => lang('操作成功')];
        }

        return ['code' => 0, 'msg' => lang('操作失败')];
    }

    /**
     * 获取添加/修改参数
     * @param array $data 附加参数
     * @return array
     */
    private function _getParams($data = [])
    {
        $params = [
            'cabinet_id' => input('cabinet_id', '', 'trim'),
            'model' => input('model'),
            'device_num' => input('device_num'),
            'sid' => input('sid', 0, 'intval'),
            'agency_id' => input('agency_id', 0, 'intval'),
            'employee_id' => input('employee_id', 0, 'intval'),
            'is_fault' => input('is_fault', 0, 'intval'),
            'qrcode' => input('qrcode', '', 'trim'),
        ];
        $params['agency_id'] < 1 && $params['agency_id'] = 0;//平台自营
        return array_merge($params, $data);
    }

    function agencyRelation($device, $agency_id = 0, $type = 1)
    {
        $type == 2 && $device['cabinet_id'] = $device['device_id'];//密码线
        if (empty($agency_id)) {//没有代理，删除设备数据
            $this->db->name('device_agency')->where(['device_code' => $device['cabinet_id'], 'type' => $type])->delete();
            return;
        }
        if (!empty($device['agency_id'])) {//原来有代理
            $this->db->name('device_agency')->where(['device_code' => $device['cabinet_id'], 'type' => $type])->delete();
        }

        $agency = $this->db->name('agency')->field('parents')
            ->where(['not_delete' => 1, 'id' => $agency_id, 'type' => 1])
            ->value('parents');
        $agency && $agency = explode(",", $agency);
        !$agency && $agency = [];
        $params[] = [
            'agency_id' => $agency_id,
            'device_code' => $device['cabinet_id'],
            'type' => $type,
            'is_self' => 1
        ];
        foreach ($agency as $v) {
            $v = intval($v);
            if (empty($v)) {
                continue;
            }
            $params[] = [
                'agency_id' => $v,
                'device_code' => $device['cabinet_id'],
                'type' => $type,
                'is_self' => 0
            ];
        }
        $this->operateLog($agency_id, '设备绑定代理商');
        $this->db->name('device_agency')->insertAll($params);

    }

    /**
     * 批量添加机柜
     */
    public function execlAdd()
    {
        $file = request()->file('file');
        if (!$file) {
            $this->error(lang('请上传文件'));
        }
        $params = [
            'cabinet_id' => '',
            'model' => input('model'),
            'device_num' => input('device_num'),
            'sid' => input('sid', 0, 'intval'),
            'agency_id' => input('agency_id', 0, 'intval'),
            'employee_id' => input('employee_id', 0, 'intval'),
            'is_fault' => input('is_fault', 0, 'intval'),
            'qrcode' => '',
            //'network_card' => input('network_card'),
        ];
        $params['agency_id'] < 1 && $params['agency_id'] = 0;//平台自营
        if (!empty($params['sid'])) {
            $seller = $this->db->name('seller')->where(['id' => $params['sid']])->find();
            if (intval($seller['agency_id']) !== intval($params['agency_id'])) {
                return ['code' => 0, 'msg' => lang('店铺不属于代理')];
            }
        }
        //记录文件信息
        $uploaded_name = $_FILES['file']['name'];
        $uploaded_ext = substr($uploaded_name, strrpos($uploaded_name, '.') + 1);
        $uploaded_ext = strtolower($uploaded_ext);
        $uploaded_size = $_FILES['file']['size'];
        $uploaded_error = $_FILES['file']['error'];
        //识别文件后缀
        if (!in_array($uploaded_ext, ['xlsx', 'xls']) || ($uploaded_size > 100000)) {
            $this->error(lang('文件格式不正确'));
        }
        if ($uploaded_error > 0) {
            $this->error(lang('文件格式上传错误'));
        }

        $file_info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'execl');
        if (!$file_info) {
            $this->error(lang('文件上传失败'));
        }

        $Office = new Office();
        $data_arr = $Office->importExecl(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'execl' . DS . $file_info->getSaveName());

        try {
            $total = count($data_arr);
            if ($total > 2001) $this->error(lang('每次最多2000条数据'));
            $url = config('qrcodeurl') . "/Lease?o=" . mwencrypt((string)$this->oid) . "&&t=";
            $data_error = [];
            foreach ($data_arr as $k => $item) {
                if ('设备编号' == $item['A'] || '' == $item['A']) continue;

                $params['cabinet_id'] = $item['A'];
                $params['qrcode'] = str_replace($url, '', $item['B']);
                $validate = \think\Loader::validate('Equipment');
                if (!$validate->scene('add')->check($params)) {
                    $data_error[$k]['cabinet_id'] = $params['cabinet_id'];
                    $data_error[$k]['qrcode'] = $params['qrcode'];
                    $data_error[$k]['msg'] = lang('错误信息') . '：' . lang($validate->getError());
                    continue;
                }

                $params['create_time'] = time();

                //有删除历史数据则恢复
                $info = $this->db->name('charecabinet')
                    ->where(['cabinet_id' => $params['cabinet_id']])
                    ->find();
                if ($info) {
                    $id = $info['id'];
                    $params['not_delete'] = 1;
                    $this->db->name('charecabinet')
                        ->where(['id' => $info['id']])
                        ->update($params);
                    !empty($params['agency_id']) && $this->agencyRelation($params, $params['agency_id']);
                    $this->operateLog($id, '恢复机柜');
                    //$this->qrcode($params['qrcode']);
                } else if ($id = $this->db->name('charecabinet')->insertGetId($params)) {
                    !empty($params['agency_id']) && $this->agencyRelation($params, $params['agency_id']);
                    $this->operateLog($id, '添加机柜');
                    //$this->qrcode($params['qrcode']);
                }
            }
            //如果有错误信息则输出错误信息
            if (!empty($data_error)) {
                $data_error = array_values($data_error);
                $title = array(
                    lang('设备编号'),
                    lang('二维码'),
                    lang('错误信息')
                );
                $filename = lang('错误二维码列表信息导出') . '-' . date('Y-m-d');

                //数据中对应的字段，用于读取相应数据：
                $keys = ['cabinet_id', 'qrcode', 'msg'];
                $tmp = time() . rand(1000, 9999) . ".xlsx";
                $Office->outdata($filename, $data_error, $title, $keys, $tmp);
                return $this->errorResponse(301, config('website') . '/uploads/' . $tmp);
            }
            return ['code' => 1, 'msg' => '操作成功'];

        } catch (Exception $e) {
            return ['code' => 0, 'msg' => '操作失败'];
        }

    }

    /**
     * 修改
     */
    public function edit($info, $data = [])
    {
        if (!$info) {
            return ['code' => 0, 'msg' => lang('信息不存在')];
        }
        $data['cabinet_id'] = $info['cabinet_id'];
        $params = $this->_getParams($data);

        $validate = \think\Loader::validate('Equipment');
        if (!$validate->scene('edit')->check($params)) {
            return ['code' => 0, 'msg' => lang($validate->getError())];
        }
        if (!empty($params['sid'])) {
            $seller = $this->db->name('seller')->where(['id' => $params['sid']])->find();
            if (intval($seller['agency_id']) !== intval($params['agency_id'])) {
                return ['code' => 0, 'msg' => lang('店铺不属于代理')];
            }
        }
        $params['id'] = $info['id'];
        $params['update_time'] = time();

        $this->db->startTrans();
        try {
            $this->db->name('charecabinet')->update($params);
            $this->agencyRelation($info, $params['agency_id']);
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            save_log('sql', $e->getMessage());
            return ['code' => 0, 'msg' => lang('操作失败')];
        }
        $this->operateLog($info['id'], '修改机柜');
        return ['code' => 1, 'msg' => lang('操作成功')];
    }

    /**
     * 删除
     * @param $id id
     * @param array $condition 查找条件
     * @return array|void
     */
    public function delete($id, $condition = [])
    {
        $where = ['id' => $id, 'not_delete' => 1];
        $where = array_merge($where, $condition);
        $info = $this->db->name('charecabinet')->where($where)->find();
        if (!$info) {
            return ['code' => 0, 'msg' => lang('信息不存在')];
        }
        $data = ['id' => $id, 'not_delete' => 0, 'update_time' => time()];
        if ($this->db->name('charecabinet')->update($data)) {
            $this->agencyRelation($info, 0);
            $this->operateLog($id, '删除机柜');
            return ['code' => 1, 'msg' => lang('操作成功')];
        }
        return ['code' => 0, 'msg' => lang('操作失败')];
    }

    /**
     * 机柜详情
     */
    public function detail($id, $condition = [])
    {
        $where = ['id' => $id, 'not_delete' => 1];
        $where = array_merge($where, $condition);
        $info = $this->db->name('charecabinet')->where($where)->find();
        if (!$info) {
            $this->error(lang('信息不存在'));
            return;
        }
        $forbid_locks = empty($info['forbid_locks']) ? [] : explode(",", $info['forbid_locks']);
        $storage = \think\Loader::model('Storage', 'service');
        $data = $this->getEquipmentCache($info['cabinet_id']);

        if (!$data || time() - $data['heart_time'] > $this->lineTime) {
            $data['online'] = 'offline';
            $data['info'] = $info;

        } else {
            //执行指令
            $service = \think\Loader::model('Command', 'service')->initData($info['cabinet_id']);
            $result = $service->triggerHeartbeat();
            if (1 == $result['status']) {
                sleep(4);
                $data = $this->getEquipmentCache($info['cabinet_id']);
            }
            $data['online'] = 'online';
        }

        $data['device'] = [];
        empty($data['details']) && $data['details'] = [];
        for ($i = 1; $i <= $info['device_num']; $i++) {
            if (!isset($data['details'][$i])) {
                $data['device'][$i] = [
                    'bid' => '', 'power' => '', 'lock' => $i, 'fault' => false, 'is_freeze' => in_array($i, $forbid_locks) ? 1 : 0,
                ];
                continue;
            }else{
                $data['details'][$i]['lock'] = $i;
            }
            $data['details'][$i]['is_freeze'] = in_array($i, $forbid_locks) ? 1 : 0;
            $data['device'][$i] = $data['details'][$i];
        }
        unset($data['details']);
        $data['info'] = $info;
        return ['code' => 1, 'data' => $data];
    }

    //锁口禁用
    function freeze($device, $lock_id, $is_forbid)
    {
        if (empty($lock_id) || $lock_id > $device['device_num']) {
            $this->error(lang('锁口错误'));
            return;
        }
        $forbid_locks = empty($device['forbid_locks']) ? [] : explode(",", $device['forbid_locks']);
        if ($is_forbid == 1) {
            $forbid_locks[] = $lock_id;
        } else {
            foreach ($forbid_locks as $k => $v) {
                if ($v == $lock_id) {
                    unset($forbid_locks[$k]);
                }
            }
        }
        $forbid_locks = array_unique($forbid_locks);
        $forbid_locks = empty($forbid_locks) ? '' : implode(",", $forbid_locks);
        $this->db->name('charecabinet')->where(['id' => $device['id']])->update(['forbid_locks' => $forbid_locks]);

        $this->operateLog($device['id'], '禁用锁孔');
        return ['code' => 1, 'data' => [], 'msg' => lang('操作成功')];
    }

    //禁用的锁口
    function forbidLocks($device_id)
    {
        //禁止锁孔
        $device = $this->db->name('charecabinet')
            ->where(['cabinet_id' => $device_id, 'not_delete' => 1])
            ->find();
        $forbid_locks = empty($device['forbid_locks']) ? [] : explode(",", $device['forbid_locks']);
        //5分钟内归还的订单且有故障的充电宝
        $order_table = getTableNo('lease_order', 'date', date("Ym"));
        $battery_ids = $this->db->name($order_table)->where(['device_code' => $device_id, 'status' => 2, 'end_time' => ['>', time() - 120]])->column('battery_id');
        $battery_ids = array_unique($battery_ids);
        $cache = $this->getEquipmentCache($device_id);
        empty($cache['details']) && $cache['details'] = [];
        $errors = [];
        foreach ($cache['details'] as $v) {
            if (in_array($v['bid'], $battery_ids)) {
                $forbid_locks[] = $v['lock'];
            }
            if ($v['is_fault']) {
                $errors[$v['lock']] = $v['bid'];
            }
        }
        foreach ($forbid_locks as $k => $v) {
            if (empty($v)) {
                unset($k);
            }
        }
        $forbid_locks = array_values($forbid_locks);
        if (!empty($forbid_locks)) {
            $content = [
                'device_id' => $device_id,
                'locks' => $forbid_locks,
                'errors' => $errors,
            ];
            save_log('forbid_locks', $content);
        }
        if (!empty($error)) {
            //    save_log('forbid_locks', $errors);
        }
        return $forbid_locks;
    }


    /**
     * 命令操作
     */
    function operate($info, $user_type = '', $uid, $params = [])
    {
        $storage = \think\Loader::model('Storage', 'service');
        $data = $this->getEquipmentCache($info['cabinet_id']);
        $type = input('type');
        isset($params['type']) && $type = $params['type'];
        $lock_id = input('lock_id');
        isset($params['lock_id']) && $lock_id = $params['lock_id'];

        if (!$data || time() - $data['heart_time'] > $this->lineTime) {
            return ['code' => 0, 'status' => 0, 'msg' => lang('机柜不在线')];
        }

        //执行指令
        $service = \think\Loader::model('Command', 'service')->initData($info['cabinet_id']);
        $service->setUserInfo(['user_type' => $user_type, 'uid' => $uid, 'memo' => '发送指令']);


        switch ($type) {
            case 'restart'://重启
                $result = $service->restart();
                break;
            case 'open'://开锁
                $result = $service->openLock($lock_id);
                break;
            case 'openAll'://打开所有
                $result = $service->openAllLock();
                break;
            case 'borrow'://借出
                $battery_power = $this->getOperatorConfig('battery_power');
                $service->setLowPower($battery_power);
                $result = $service->borrowDevice();
                break;
            case 'trigger'://强制心跳
                $result = $service->triggerHeartbeat();
                break;
            default:
                $result = ['status' => 0, 'msg' => lang('指令错误')];
                break;
        }
        $result['code'] = $result['status'];
        $this->operateLog(0, '命令操作');
        return $result;
    }

    /**
     * 列表
     */
    public function batteryList($condition = [], $pages = 20, $isReturn = false)
    {
        $pages = intval($pages);
        $pages < 1 && $pages = 20;
        $where = ['not_delete' => 1];
        $pageParam = ['query' => []];

        //电池编号
        $device_id = input('device_id', '', 'trim');
        if ('' != $device_id) {
            $where['device_id'] = ['LIKE', "%{$device_id}%"];
            $pageParam['query']['device_id'] = $device_id;
        }

        $query = $this->db->name('battery')
            ->where($where)
            ->order('id desc')
            ->paginate($pages, false, $pageParam);
        $list = [];
        $total = $query->total();

        foreach ($query->all() as $v) {
            $v['time'] = date("Y-m-d H:i:s", $v['create_time']);
            unset($v['create_time'], $v['update_time'], $v['not_delete']);
            $list[] = $v;
        }
        if ($isReturn) {
            return ['total' => $total, 'list' => $list];
        }
        $paginate = $query->render();
        //有查询则赋值
        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
        $this->assign('list', $list);
        $this->assign('paginate', $paginate);
    }

    /**
     * 电池日志
     */
    public function batteryLog($battery_id, $pages = 20, $isReturn = false)
    {
        $pages = intval($pages);
        $pages < 1 && $pages = 20;
        $battery = $this->db->name('battery')
            ->where(['device_id' => $battery_id, 'not_delete' => 1])
            ->find();
        !$battery && $this->error(lang('充电宝不存在'));
        $pageParam = ['query' => ['battery_id' => $battery_id]];

        $query = $this->db->name('battery_log')
            ->where(['battery_id' => $battery_id])
            ->order('id desc')
            ->paginate($pages, false, $pageParam);

        $paginate = $query->render();
        $total = $query->total();

        $list = [];
        $agency_ids = [];
        $operator_ids = [];
        foreach ($query->all() as $v) {
            $v['time'] = date("Y-m-d H:i:s", $v['create_time']);
            $list[] = $v;
            if (is_null($v['user_type'])) {
                continue;
            }
            if (empty($v['user_type'])) {
                $operator_ids[] = $v['relation_id'];
            } else if (99 != $v['user_type']) {
                $agency_ids[] = $v['relation_id'];
            }
        }
        if ($operator_ids) {
            $operators = $this->db->name('operator_users')->field('id,username')->where(['id' => ['IN', $operator_ids]])->select();
            $operators = array_column($operators, 'username', 'id');
        }
        if ($agency_ids) {
            $agencies = $this->db->name('agency')->field('id,name')->where(['id' => ['IN', $agency_ids]])->select();
            $agencies = array_column($agencies, 'name', 'id');
        }
        foreach ($list as $k => $v) {
            $v['user'] = '';
            if (is_null($v['user_type'])) {
                $v['user_type'] = lang('系统');
            } else if (empty($v['user_type'])) {
                $v['user_type'] = lang('管理员');
                $v['user'] = $operators[$v['relation_id']];
            } else if (99 == $v['user_type']) {
                $v['user_type'] = lang('客户');
            } else {
                $v['user_type'] = config('user_type_name.' . $v['user_type']);
                $v['user'] = $agencies[$v['relation_id']];
            }
            if ( !empty($v['memo']) ) {
                $v['memo'] = lang($v['memo']);
            }
            $list[$k] = $v;
        }
        if ($isReturn) {
            return ['total' => $total, 'list' => $list];
        }

        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
        $this->assign('list', $list);
        $this->assign('paginate', $paginate);
    }

    public function qrcode2($text, $serial)
    {
        $website = config('qrcodeurl');
        //二维码链接,运营商id+文字
        $url = $website . "/Lease?o=" . mwencrypt((string)$this->oid) . "&&t={$text}";
        $path = dirname($_SERVER['SCRIPT_FILENAME']) . "/qrcode";
        $canvas = $path . '/device/blank.png';
        $font = $path . '/simfang.ttf'; // 字体文件

        $oid = $this->oid;
        !is_dir($path . "/device/{$oid}") && mkdir($path . "/device/{$oid}");
        //生成二维码文件夹,1000个文件一个文件夹
        $folder = "/device/{$oid}/" . ceil($serial / 1000) . "/";
        $path = $path . $folder;
        !is_dir($path) && mkdir($path);


        //复制背景图
        $background = $path . $text . '.png';

        if (!@copy($canvas, $background)) {
            return false;
        }

        //生成二维码图片
        $code_file = $path . 'tmp-' . time() . '.png';
        Loader::import('qrcode.phpqrcode');
        $object = new \QRcode();
        $object->png($url, $code_file, 'Q', 12, 1);
        if (!file_exists($code_file)) {
            return false;
        }

        $width = 200;
        $height = 220;
        $font_size = 18;

        //合成
        $back = imagecreatefrompng($background);
        $color = imagecolorallocate($back, 0, 0, 0);

        $file = imagecreatefrompng($code_file);
        $logo_w = imagesx($file);
        $logo_h = imagesy($file);


        //获取文字信息
        $info = imagettfbbox($font_size, 0, $font, $text);
        $minx = min($info[0], $info[2], $info[4], $info[6]);
        $maxx = max($info[0], $info[2], $info[4], $info[6]);
        $miny = min($info[1], $info[3], $info[5], $info[7]);
        $maxy = max($info[1], $info[3], $info[5], $info[7]);
        /* 计算文字初始坐标和尺寸 */
        $x = $minx;
        $y = abs($miny);
        $w = $maxx - $minx;
        $h = $maxy - $miny;
        $x += ($width - $w) / 2;
        $y += $height - $h;


        imagettftext($back, $font_size, 0, $x, $y, $color, $font, $text);// 调二维码中字体
        imagecopyresampled($back, $file, 0, 0, 0, 0, 200, 200, $logo_w, $logo_h);//调中间logo位置;//调中间logo位置
        imagepng($back, $background);
        imagedestroy($back);
        imagedestroy($file);
        unlink($code_file);


        $params = [
            'code' => $text,
            'path' => $folder . $text . '.png',
            'create_time' => time()
        ];
        $this->db->name('charecabinet_qrcode')->insert($params);
        return true;
    }

    /**
     * 批量添加
     * @param $oid
     * @param $num
     */
    public function batchCode($prefix, $num)
    {
        $oid = $this->oid;
        cache("equipment_qrcode:$oid", time(), 3);
        $query = $this->db->name('charecabinet_qrcode')
            ->order(['id' => 'desc'])
            ->find();
        $serial = $query ? $query['id'] : 0;
        $serial = $serial + 1;
        $total = 0;


        $start = date('Y-m-d', strtotime(date('Y-m', time()) . '-01 00:00:00'));
        $end = date('Y-m-d', strtotime(date('Y-m', time()) . '-' . date('t', time()) . ' 00:00:00')); //t为当月天数,28至31天
        $starttime = strtotime($start . " 00:00:00") - 1;
        $endtime = strtotime($end . " 23:59:59") + 1;
        $where = ['create_time' => [['>', $starttime], ['<', $endtime]]];
        $month_num = $this->db->name('charecabinet_qrcode')->where($where)->count();


        $prefix = $prefix . date("ym");
        $prefix = str_pad($prefix, 8, 0);
        for ($i = 0; $i < $num; $i++) {
            cache("equipment_qrcode:$oid", time(), 3);
            $text = $prefix . ($month_num + 111001 + $i);

            $ret = $this->qrcode($text, $serial);
            if ($ret) {
                $serial++; //成功后序号递增
                $total++;
            }
        }
        cache("equipment_qrcode:$oid", null);
        return $total;
//        echo $time = microtime(true) - THINK_START_TIME;
//        echo ' m:' . memory_get_usage();
    }

    public function qrcode($text)
    {
        $website = config('qrcodeurl');
        //二维码链接,运营商id+文字
        $url = $website . "/Lease?o=" . mwencrypt((string)$this->oid) . "&&t={$text}";
        $path = dirname($_SERVER['SCRIPT_FILENAME']) . "/qrcode/";
        $canvas = $path . 'device/blank.png';
        $font = $path . 'simfang.ttf'; // 字体文件
        $folder = $path . 'device/' . $this->oid . "/";
        !is_dir($folder) && mkdir($folder);

        //复制背景图
        $background = $folder . $text . '.png';
        if (!@copy($canvas, $background)) {
            return false;
        }

        //生成二维码图片
        $code_file = $folder . 'tmp-' . time() . '.png';
        Loader::import('qrcode.phpqrcode');
        $object = new \QRcode();
        $object->png($url, $code_file, 'Q', 12, 1);
        if (!file_exists($code_file)) {
            return false;
        }

        $width = 200;
        $height = 220;
        $font_size = 18;

        //合成
        $back = imagecreatefrompng($background);
        $color = imagecolorallocate($back, 0, 0, 0);

        $file = imagecreatefrompng($code_file);
        $logo_w = imagesx($file);
        $logo_h = imagesy($file);


        //获取文字信息
        $info = imagettfbbox($font_size, 0, $font, $text);
        $minx = min($info[0], $info[2], $info[4], $info[6]);
        $maxx = max($info[0], $info[2], $info[4], $info[6]);
        $miny = min($info[1], $info[3], $info[5], $info[7]);
        $maxy = max($info[1], $info[3], $info[5], $info[7]);
        /* 计算文字初始坐标和尺寸 */
        $x = $minx;
        $y = abs($miny);
        $w = $maxx - $minx;
        $h = $maxy - $miny;
        $x += ($width - $w) / 2;
        $y += $height - $h;


        imagettftext($back, $font_size, 0, $x, $y, $color, $font, $text);// 调二维码中字体
        imagecopyresampled($back, $file, 0, 0, 0, 0, 200, 200, $logo_w, $logo_h);//调中间logo位置;//调中间logo位置
        imagepng($back, $background);
        imagedestroy($back);
        imagedestroy($file);
        unlink($code_file);
        return true;
    }

    function batchDownload($files)
    {
        $folder = dirname($_SERVER['SCRIPT_FILENAME']) . "/qrcode/device/" . $this->oid . "/";

        $path = dirname($_SERVER['SCRIPT_FILENAME']) . "/qrcode/download/";
        !is_dir($path) && mkdir($path);

        $zipname = $path . '/qrcode-' . time() . mt_rand(1000, 9999) . ".zip";
        $zip = new ZipArchive();
        $res = $zip->open($zipname, ZipArchive::CREATE);
        if ($res !== true) {
            echo '下载失败，请稍后重试';
            exit;
        }

        foreach ($files as $file) {
            $file = $folder . $file;
            $zip->addFile($file, 'qrcode/' . basename($file));
        }
        $zip->close();

        header("Content-Type: application/zip");
        header("Content-Transfer-Encoding: Binary");
        header("Content-Length: " . filesize($zipname));
        header("Content-Disposition: attachment; filename=" . iconv("UTF-8", "gbk//TRANSLIT", '二维码.zip') . ";");
        readfile($zipname);
        unlink($zipname);
        return;
    }


    function agencyDevice($agency_id, $pages = 20)
    {
        $where = ['c.not_delete' => 1, 'a.agency_id' => $agency_id, 'a.type' => 1];
        $pages < 1 && $pages = 20;
        //机柜号
        $cabinet_id = input('cabinet_id', '', 'trim');
        if ('' != $cabinet_id) {
            $where['c.cabinet_id'] = ['LIKE', "%{$cabinet_id}%"];
        }

        //二维码
        $qrcode = input('qrcode', '', 'trim');
        if ('' != $qrcode) {
            $where['c.qrcode'] = ['LIKE', "%{$qrcode}%"];
        }

        //商家
        $sid = input('sid', 0, 'intval');
        if (!empty($sid)) {
            $where['c.sid'] = $sid;
        }

        //是否自有
        $type = input('type', 0, 'intval');
        if (!empty($type) && in_array($type, [1, 2])) {
            $type = ($type == 1) ? 1 : 0;
            $where['a.is_self'] = $type;
        }
        $is_export = input('is_export', 0, 'intval');
        $is_export == 1 && $pages = 10000;

        $is_online = input('is_online', 0, 'intval');
        if (1 == $is_online) {
            $where['c.heart_time'] = ['>', time() - config('online_time')];
            $where['c.is_online'] = 1;
        }
        $query = $this->db->name('charecabinet')
            ->alias('c')
            ->join("device_agency a", 'a.device_code = c.cabinet_id', 'LEFT')
            ->field('c.id,c.cabinet_id,c.qrcode,c.is_online,c.model,c.mode,c.is_fault,c.device_num,c.heart_time,c.agency_id,c.employee_id,c.sid,c.network_card')
            ->where($where);
        if (2 == $is_online) {//离线，拼装sql
            $outline_day = input('outline_day', 0, 'intval');
            $online_time = time() - config('online_time');
            if (!empty($outline_day)) {
                $online_time = time() - $outline_day * 86400;
            }
            $query->where('c.heart_time < :time OR c.is_online = :is_online ', ['time' => $online_time, 'is_online' => 0]);
        }

        $query = $query->order('c.create_time desc')->paginate($pages);
        $list = $query->all();
        $total = $query->total();
        $time = time();
        $models = config('equipment');
        if ($list) {
            $sids = array_column($list, 'sid');
            $sids = array_unique($sids);
            $sellers = $this->db->name('seller')->field('id,name,area,address')->where(['id' => ['IN', $sids]])->select();
            $sellers = array_column($sellers, NULL, 'id');
            $model = \think\Loader::model('Agency');
            $allAgency = $model->allAgency();
            $employee_ids = array_column($list, 'employee_id');
            $employees = $this->db->name('agency')
                ->field('id,name')
                ->where(['not_delete' => 1, 'type' => 2, 'id' => ['IN', $employee_ids]])
                ->select();
            $employees = array_column($employees, 'name', 'id');

            foreach ($list as $k => $v) {
                $cache = $this->getEquipmentCache($v['cabinet_id']);

                $heartbeattime = 0;
                $v['return_num'] = $v['device_num'];
                $v['borrow_num'] = 0;
                $v['ip'] = '';
                $v['line'] = 'offline';
                if ($cache) {
                    $heartbeattime = $v['heart_time'];
                    $v['borrow_num'] = intval($cache['stock_num']);
                    $v['return_num'] = $v['device_num'] - $v['borrow_num'];
                    $v['return_num'] < 0 && $v['return_num'] = 0;
                    $v['ip'] = $cache['ip'];
                }
                if (($time - $heartbeattime) < $this->lineTime && $v['is_online'] == 1) {
                    $v['line'] = 'online';
                }
                $v['model'] = $models[$v['model']]['name'];
                $v['name'] = isset($sellers[$v['sid']]['name']) ? $sellers[$v['sid']]['name'] : '';
                $v['area'] = isset($sellers[$v['sid']]['area']) ? $sellers[$v['sid']]['area'] : '';
                $v['address'] = isset($sellers[$v['sid']]['address']) ? $sellers[$v['sid']]['address'] : '';
                $v['agency_name'] = '';
                if (!empty($v['agency_id'])) {
                    $parent = $model->getParents($allAgency, $v['agency_id']);
                    $parent = array_column($parent, 'name');
                    $v['agency_name'] = implode(" > ", $parent);
                    $v['employee_name'] = isset($employees[$v['employee_id']]) ? $employees[$v['employee_id']] : '';
                }
                if ($is_export == 1) {
                    $v['network_card'] = "\t{$v['network_card']}\t";
                    $v['line'] = ($v['line'] == 'offline') ? lang('离线') : lang('在线');
                    $v['address'] = $v['area'].$v['address'];
                }

                $list[$k] = $v;
            }
        }
        if ($is_export != 1) {
            return ['total' => $total, 'list' => $list];
        }
        $title = array(
            lang('机柜号'),
            lang('二维码'),
            lang('CCID'),
            lang('商铺'),
            lang('区域'),
            lang('机柜型号'),
            lang('卡槽'),
            lang('可借'),
            lang('可还'),
            lang('状态'),
            lang('代理商'),
            lang('业务员'),
        );
        $filename = lang('代理商机柜').'-' . date('Y-m-d');

        $excel = new Office();

        //数据中对应的字段，用于读取相应数据：
        $keys = ['cabinet_id', 'qrcode', 'network_card', 'name', 'address', 'model', 'device_num', 'borrow_num', 'return_num', 'line', 'agency_name', 'employee_name'];
        $tmp = time() . rand(1000, 9999) . ".xlsx";
        $excel->outdata($filename, $list, $title, $keys, $tmp);
        return $this->errorResponse(301, config('website') . '/uploads/' . $tmp);

    }
}