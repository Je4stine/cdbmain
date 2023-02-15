<?php

namespace app\operate\controller;

use think\Loader;
use think\Request;

class Equipment extends Common
{


    public function index()
    {
        $page_size = input('page_size', 20, 'intval');
        $data = \think\Loader::model('Equipment', 'logic')->equipmentList([], $page_size, true);
        return $this->successResponse($data, '机柜列表');
    }


    /**
     * 添加设备
     */
    public function add()
    {
        !Request::instance()->isPost() && $this->error(lang('非法请求'));
        $ret =  \think\Loader::model('Equipment', 'logic')->add([]);
        if ($ret['code'] === 0) {
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }

    /**
     * execl 导入添加设备
     */
    public function execlAdd()
    {
        set_time_limit(600);
        !Request::instance()->isPost() && $this->errorResponse(0, lang('非法请求'));
        $ret = \think\Loader::model('Equipment', 'logic')->execlAdd([]);
        if ($ret['code'] === 0) {
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }

    /**
     * 修改机柜
     */
    public function edit()
    {
        !Request::instance()->isPost() && $this->errorResponse(0, lang('非法请求'));
        $id = input('id', 0, 'intval');
        $info = $this->db->name('charecabinet')->where(['id' => $id, 'not_delete' => 1])->find();
        if (!$info) {
            return $this->errorResponse(0, lang('信息不存在'));
        }
        $ret = \think\Loader::model('Equipment', 'logic')->edit($info, ['id' => $id]);
        if ($ret['code'] === 0) {
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }


    //机柜详情
    public function info()
    {
        $id = input('id', 0, 'intval');
        $producer = config('equipment');
        $producer = array_values($producer);
        foreach ($producer as $key => $value)
        {
            $producer[$key]['name'] = lang($value['name']);
        }
        if ($id < 1) {
            $info = ['agency_id' => -1, 'employee_id' => 0];
        } else {
            $info = $this->db->name('charecabinet')->where(['id' => $id, 'not_delete' => 1])->find();
        }
        if (!$info) {
            return $this->errorResponse(0, lang('信息不存在!'));
        }
        $info['seller_name'] = '';
        if(!empty($info['sid'])){
            $info['seller_name'] = json_decode($this->db->name('seller')->where(['id' => $info['sid']])->value('name'), true)[$this->lang] ?? '';
        }
        !empty($info['agency_id']) && $info['agency_name'] = $this->db->name('agency')->where(['id' => $info['agency_id']])->value('name');
        $info['employeeList'] = $this->db->name('agency')->field('id,name,brokerage')
            ->where(['not_delete' => 1, 'parent_id' => $info['agency_id'], 'type' => 2])
            ->select();
        $info['producer'] = $producer;
        empty($info['agency_name']) && $info['agency_name'] = lang('平台自营');
        return $this->successResponse($info, lang('机柜信息'));

    }


    //机柜详情
    public function detail()
    {
        $id = Request::instance()->post('id', 0, 'intval');
        $result = \think\Loader::model('Equipment', 'logic')->detail($id, []);
        $result = $result['data'];
        $info = ['cabinet_id' => $result['info']['cabinet_id']];
        $info['is_online'] = ($result['online'] == 'online') ? 1 : 0;
        $info['signal'] = isset($result['signal']) ? $result['signal'] : 0;
        $info['device'] = isset($result['device']) ? $result['device'] : [];
        $info['device'] = array_values($info['device']);
        return $this->successResponse($info, lang('机柜详情'));

    }

    //删除机柜信息
    public function delete()
    {
        $id = Request::instance()->post('id', 0, 'intval');
        $ret = \think\Loader::model('Equipment', 'logic')->delete($id, []);
        if ($ret['code'] === 0) {
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }

    //机柜操作
    public function operate()
    {
        $cabinet_id = input('cabinet_id');
        $info = $this->db->name('charecabinet')
            ->where(['cabinet_id' => $cabinet_id, 'not_delete' => 1])
            ->find();
        if (!$info) {
            return $this->errorResponse(0, lang('信息不存在!'));
        }
        $ret = \think\Loader::model('Equipment', 'logic')->operate($info, '0', $this->auth_info['id']);
        if ($ret['code'] === 0) {
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }

    //禁用锁孔
    public function freeze()
    {
        $cabinet_id = input('cabinet_id');
        $lock_id = input('lock_id', 0, 'intval');
        $is_forbid = input('is_forbid', 0, 'intval');
        $info = $this->db->name('charecabinet')
            ->where(['cabinet_id' => $cabinet_id, 'not_delete' => 1])
            ->find();
        if (!$info) {
            return $this->errorResponse(0, lang('信息不存在'));
        }
        $ret = \think\Loader::model('Equipment', 'logic')->freeze($info, $lock_id, $is_forbid);
        if ($ret['code'] === 0) {
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }


    //充电宝列表
    public function batteryList()
    {
        $page_size = input('page_size', 20, 'intval');
        $data = \think\Loader::model('Equipment', 'logic')->batteryList([], $page_size, true);
        $this->successResponse($data, lang('获取成功'));
    }

    //充电宝日志
    public function batteryLog()
    {
        $page_size = input('page_size', 20, 'intval');
        $battery_id = input('battery_id', '', 'trim');
        $data = \think\Loader::model('Equipment', 'logic')->batteryLog($battery_id, $page_size, true);
        $this->successResponse($data, lang('获取成功'));
    }


    /**
     * 代理商
     */
    public function agencyBind()
    {
        if (!Request::instance()->isPost()) {
            $this->errorResponse(0, lang('非法请求'));
        }
        $ids = input('ids', '', 'trim');
        $ids = explode(",", $ids);
        $data = [];
        foreach ($ids as $v) {
            if (trim($v) == '') {
                continue;
            }
            $data[] = $v;
        }
        empty($data) && $this->errorResponse(0, lang('请输入设备编号'));
        count($data) > 100 && $this->errorResponse(0, lang('每次最多输入100台设备号'));

        $agency_id = input('agency_id', 0, 'intval');
        $agency_id < 1 && $agency_id = 0;//自营
        $employee_id = input('employee_id', 0, 'intval');
        $sid = input('sid', 0, 'intval');
        if ($agency_id) {
            $agency = $this->db->name('agency')
                ->where(['not_delete' => 1, 'id' => $agency_id, 'type' => 1])
                ->find();
            !$agency && $this->errorResponse(0, lang('代理不存在'));
        }
        if ($employee_id) {
            $employee = $this->db->name('agency')
                ->where(['not_delete' => 1, 'id' => $employee_id, 'type' => 2])
                ->find();
            !$employee && $this->errorResponse(0, lang('业务员不存在'));
        }
        if ($sid) {
            $seller = $this->db->name('seller')
                ->where(['not_delete' => 1, 'id' => $sid])
                ->find();
            !$seller && $this->errorResponse(0, lang('商户不存在'));
        }

        $this->db->name('charecabinet')
            ->where(['not_delete' => 1, 'cabinet_id' => ['IN', $data]])
            ->update(['agency_id' => $agency_id, 'employee_id' => $employee_id, 'sid' => $sid, 'update_time' => time()]);
        return $this->successResponse([], lang('设置成功'));
    }

    //重启设备
    public function restart()
    {
        $start_id = input('start_id', 0, 'intval');
        $end_id = input('end_id', 0, 'intval');
        empty($start_id) && $this->errorResponse(0, lang('请输入起始序列号'));
        empty($end_id) && $this->errorResponse(0, lang('请输入结束序列号'));

        $query = $this->db->name('charecabinet')
            ->where(['not_delete' => 1, 'id' => ['between', [$start_id, $end_id]]])
            ->select();

        $logic = \think\Loader::model('Equipment', 'logic');
        $total = 0;
        foreach ($query as $value) {
            $ret = $logic->operate($value, '0', $this->auth_info['id'], ['type' => 'restart']);
            $ret['code'] == 1 && $total++;
        }
        $total < 1 && $this->errorResponse(0, lang('没有设备可以重启'));
        return $this->successResponse([], lang('设备重启成功') . '：' . $total);
    }

    public function trend()
    {
        $id = input('id', 0, 'intval');
        $info = $this->db->name('charecabinet')->where(['id' => $id])->find();
        !$info && $this->errorResponse(0, lang('机柜不存在'));
        $data = \think\Loader::model('Stat', 'logic')->deviceTrend($info['cabinet_id']);
        return $this->successResponse($data, lang('租借统计'));
    }

    /**
     * 下载摸版
     */
    public function modelDownload()
    {
        $file = dirname($_SERVER['SCRIPT_FILENAME']) . "/excel_model.xlsx";
        header("Content-type: octet/stream");
        header("Content-Disposition:attachment;filename=" . iconv("UTF-8", "gbk//TRANSLIT", '批量导入机柜摸版.xlsx')); //告诉浏览器通过附件形式来处理文件
        header('Content-Length:' . filesize($file)); //下载文件大小
        readfile($file);  //读取文件内容
        return;
    }


    //生成二维码
    public function qrcode()
    {
        $text = input('text');
        $url = config('qrcodeurl') . "/Lease?o=" . mwencrypt((string)$this->oid) . "&&t={$text}";
        ob_start();
        Loader::import('qrcode.phpqrcode');
        $object = new \QRcode();
        Header("Content-type: image/png");
        $object->png($url, false, 'L', 18, 2);
        exit;
    }

    /**
     * 下载单张二维码
     */
    function codeDownload($code = '')
    {
        $file = dirname($_SERVER['SCRIPT_FILENAME']) . "/qrcode/device/{$this->oid}/{$code}.png";
        !file_exists($file) && $this->errorResponse(0, lang('二维码不存在'));

        header("Content-type: octet/stream");
        header("Content-disposition:attachment;filename=" . $code . ".png;");
        header("Content-Length:" . filesize($file));
        readfile($file);
        return;
    }


}
