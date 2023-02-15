<?php

namespace app\cloud\controller;

use think\Controller;
use think\Db;

//用于接收相关数据，
class Screen extends Controller
{
    public $db;//数据库

    public function _initialize()
    {
//        if (!Request::instance()->isPost()) {
//            echo json_encode(['status' => 0, 'msg' => '非法请求'], JSON_UNESCAPED_UNICODE);
//            exit;
//        }
        $this->_getDb();
    }

    function plan()
    {
        $str = '{"status":1,"list":[{"list":[{"type":"image","time":5,"file":"https:\/\/zdoss-1258296805.cos.ap-guangzhou.myqcloud.com\/screen\/201912\/15774297931748.png"},{"type":"image","time":5,"file":"https:\/\/zdoss-1258296805.cos.ap-guangzhou.myqcloud.com\/screen\/201912\/15774297638436.png"}],"start":"00:00","end":"23:59"}],"top":{"height":0},"plan":{"height":1730},"middle":{"height":0},"bottom":{"height":190,"img":"https:\/\/zdoss-1258296805.cos.ap-guangzhou.myqcloud.com\/screen\/201912\/15773466037506.png"},"left":"https:\/\/zdoss-1258296805.cos.ap-guangzhou.myqcloud.com\/screen\/202005\/15889948921031.png"}';
        $data = json_decode($str, true);

        $config = $this->db->name('config')->where(['type' => 'screen_default'])->find();
        $config = json_decode($config['data'], true);

        $sn = input('sn');
        $sn == 'CT034191100281' && $sn = 'CT034191200007';
        $equipment = $this->db->name('charecabinet')->where(['not_delete' => 1, 'cabinet_id' => $sn])->find();

        $ret = ['status' => 1];
        $ret['msg'] = '广告列表';
        $ret['default_cross'] = $config['default_cross'];
        $ret['default_vertical'] = $config['default_vertical'];
        $ret['top']['height'] = 0;
        $ret['plan']['height'] = 1730;
        $ret['middle']['height'] = 0;
        $ret['bottom']['height'] = 190;
        $ret['list'] = [];
        $ret['bottom']['img'] = $config['default_bottom'];
        $ret['left'] = $config['default_left_cross'];

        $tmp = [
            'list' => [],
            'start' => "00:00",
            'end' => "23:59"
        ];
        if (substr($sn, 0, 5) == 'CT034') {
            $tmp['list'][] = [
                'type' => 'image',
                'time' => 10,
                'file' => $config['default_vertical']
            ];
        } else {
            $tmp['list'][] = [
                'type' => 'image',
                'time' => 10,
                'file' => $config['default_cross']
            ];
        }


        $ret['list'][] = $tmp;


        if (!$equipment) {
            $this->_saveLog($ret,$sn);
            echo json_encode($ret, JSON_UNESCAPED_UNICODE);
            exit;
        }


        $plan = null;
        //机柜投放优先代理投放
        $pids = $this->db->name('ad_plan_charecabinet')->where(['not_delete' => 1, 'data_id' => $sn])->column('plan_id');
        if ($pids) {
            $plan = $this->db->name('ad_plan')
                ->where(['not_delete' => 1, 'id' => ['IN', $pids]])
                ->where(['start_date' => ['<', date("Y-m-d", time() + 86400)]])
                ->where(['end_date' => ['>', date("Y-m-d", time() - 86400)]])
                ->find();

        }

        if (!$plan) {
            $pids = $this->db->name('ad_plan_agency')->where(['not_delete' => 1, 'data_id' => $equipment['agency_id']])->column('plan_id');

            if (!$pids) {
                $this->_saveLog($ret,$sn);
                echo json_encode($ret, JSON_UNESCAPED_UNICODE);
                exit;
            }

            $plan = $this->db->name('ad_plan')
                ->where(['not_delete' => 1, 'id' => ['IN', $pids]])
                ->where(['start_date' => ['<', date("Y-m-d", time() + 86400)]])
                ->where(['end_date' => ['>', date("Y-m-d", time() - 86400)]])
                ->find();
            if (!$plan) {
                $this->_saveLog($ret,$sn);
                echo json_encode($ret, JSON_UNESCAPED_UNICODE);
                exit;
            }
        }


        $data = [];
        $start_time = strtotime($plan['start_date']);
        $end_time = strtotime($plan['end_date']) + 86399;
        //按时间排序
        $details = json_decode($plan['details'], true);
        $list = [];
        foreach ($details as $k => $v) {
            $start_hour = intval($v['start_hour']);
            $start_minute = intval($v['start_minute']);
            $list[$start_hour][$start_minute] = $v;
        }
        foreach ($list as $k => $v) {
            ksort($v);
            $list[$k] = $v;
        }
        ksort($list);

        $material_ids = $groups = [];
        //所有分组
        $group_query = $this->db->name('ad_group')->field('id,details,material_ids')->where(['not_delete' => 1, 'id' => ['IN', $plan['group_ids']]])->select();
        foreach ($group_query as $group) {
            $material_ids[] = $group['material_ids'];
            $group['details'] = json_decode($group['details'], true);
            $groups[$group['id']] = $group;
        }
        //所有素材
        $material_ids = implode(",", $material_ids);
        $material_ids = explode(",", $material_ids);
        $material_ids = array_unique($material_ids);
        $materials = $this->db->name('ad_material')->field('id,file,type')->where(['not_delete' => 1, 'id' => ['IN', $material_ids]])->select();
        $materials = array_column($materials, NULL, 'id');

        foreach ($list as $k => $v) {
            foreach ($v as $detail) {
                $detail['start_hour'] < 10 && $detail['start_hour'] = '0' . $detail['start_hour'];
                $detail['start_minute'] < 10 && $detail['start_minute'] = '0' . $detail['start_minute'];
                $detail['end_hour'] < 10 && $detail['end_hour'] = '0' . $detail['end_hour'];
                $detail['end_minute'] < 10 && $detail['end_minute'] = '0' . $detail['end_minute'];
                $tmp = [
                    'list' => [],
                    'start' => $detail['start_hour'] . ":" . $detail['start_minute'],
                    'end' => $detail['end_hour'] . ":" . $detail['end_minute'],
                ];
                foreach ($groups[$detail['group_id']]['details'] as $set) {
                    $material = $materials[$set['material_id']];
                    $tmp['list'][] = [
                        'type' => $material['type'],
                        'time' => $set['time'],
                        'file' => $material['file'],
                    ];
                }

                $data[] = $tmp;
            }
        }

        $ret = ['status' => 1, 'list' => $data];
        $position = $this->db->name('ad_position')->where(['id' => $plan['ad_position_id']])->find();
        $position = json_decode($position['data'], true);
        $ret['top'] = [
            'height' => intval($position['top_height']),
        ];
        if ($ret['top']['height'] > 0) {
            $ret['top']['img'] = $position['top_image'];
        }
        $ret['plan'] = [
            'height' => intval($position['plan_height']),
        ];
        $ret['middle'] = [
            'height' => intval($position['middle_height']),
        ];
        if ($ret['middle']['height'] > 0) {
            $ret['middle']['img'] = $position['middle_image'];
        }
        $ret['bottom'] = [
            'height' => 190,
            'img' => empty($position['bottom_image']) ? $config['default_bottom'] : $position['bottom_image']
        ];
        $ret['left'] = empty($position['left_cross']) ? $config['default_left_cross'] : $position['left_cross'];
        $this->_saveLog($ret,$sn);
        echo json_encode($ret, JSON_UNESCAPED_UNICODE);
        exit;
        echo '<pre>';
//        print_r($list);
//        print_r($groups);
//        print_r($materials);
        print_r($ret);
        exit;

    }

    private function _saveLog($msg,$sn=''){
        $path = ROOT_PATH . 'public/logs';
        if (!is_dir($path)) {
            mkdir($path);
        }
        is_array($msg) && $msg['sn'] = $sn;
        $filename = $path . '/' . date('Ymd') . '.txt';
        $content = date("Y-m-d H:i:s") . "\r\n" . print_r($msg, 1) . "\r\n \r\n \r\n ";
        file_put_contents($filename, $content, FILE_APPEND);
    }


    private function _getDb($code = 'dispatch')
    {
        //链接数据库
        $db_config = config('database');
        $this->db = Db::connect($db_config);
    }

    function rent()
    {
        $config = $this->db->name('config')->where(['type' => 'screen_default'])->find();
        $config = json_decode($config['data'], true);
        $ret = ['status' => 1];
        $ret['msg'] = 'Arrendar';
        $ret['success_text'] = ' Arriendo exitoso';
        $ret['fail_text'] = 'Arriendo fallido';
        $ret['success_content'] = 'Por favor recoge tu cargador portátil';
        $ret['fail_content'] = 'Por favor escanea nuevamente el código QR para arrendar';
        $ret['success_cross'] = $config['rent_success_vertical'];
        $ret['success_vertical'] = $config['rent_success_vertical'];
        $ret['fail_cross'] = $config['rent_fail_vertical'];
        $ret['fail_vertical'] = $config['rent_fail_vertical'];
        $this->_saveLog($ret);
        echo json_encode($ret, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function audio()
    {
        $host = config('website');
        $data = [
            'boot_strap' => $host.'/audio/boot_strap.mp3',
            'networking_success' => $host.'/audio/networking_success.mp3',
            'networking_fail' => $host.'/audio/networking_fail.mp3',
            'rent_success' => $host.'/audio/rent_success.mp3',
            'rent_fail' => $host.'/audio/rent_fail.mp3',
            'return_success' => $host.'/audio/return_success.mp3',
            'return_fail' => $host.'/audio/return_fail.mp3',
        ];
        $this->_saveLog($data);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    function back()
    {
        $config = $this->db->name('config')->where(['type' => 'screen_default'])->find();
        $config = json_decode($config['data'], true);
        $ret = ['status' => 1];
        $ret['msg'] = 'Devolver';
        $ret['success_text'] = ' Devolución exitosa';
        $ret['fail_text'] = ' Devolución fallida';
        $ret['success_content'] = 'Nos vemos pronto';
        $ret['fail_content'] = 'Por favor intenta devolver en otra ranura';
        $ret['success_cross'] = $config['back_success_vertical'];
        $ret['success_vertical'] = $config['back_success_vertical'];
        $ret['fail_cross'] = $config['back_fail_vertical'];
        $ret['fail_vertical'] = $config['back_fail_vertical'];
        $this->_saveLog($ret);
        echo json_encode($ret, JSON_UNESCAPED_UNICODE);
        exit;
    }

    //版本更新
    public function version()
    {
        $sn = input('sn');
        $data = [
            'status' => 1,
            'msg' => '检测版本',
            'url' => 'http://ota.w-dian.cn/app_v26.apk',
            'version' => 26,
        ];
        if (!empty($sn) && substr($sn, 0, 5) != 'CT034') {
            $data['url'] = 'http://ota.w-dian.cn/app_v34.apk';
            $data['version'] = 34;
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

