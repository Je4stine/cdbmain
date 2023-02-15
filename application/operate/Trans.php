<?php

namespace app\operate;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Db;

/**
 * 脚本
 * @package app\operate
 */
class Trans extends Command
{
    var $db = null;

    function orderSync()
    {
        set_time_limit(0);
        //echo '<pre>';
        $max_id = $this->db->name('lease_order')->field('id')->order("id desc")->limit(1)->value('id');
        $min_id = 1;
        $page_size = 100;
        $i = floor($min_id / $page_size);
        $batch = ceil($max_id / $page_size);
        flush();
        ob_end_flush();
        $ids = [];
        for ($i = $batch; $i >= 0; $i--) {
            flush();
            ob_end_flush();
            $start_id = $i * $page_size;
            $end_id = $start_id + $page_size;
            echo "\n ============== no.{$i} , id: {$start_id} - {$end_id} ======================= ";
            $results = $this->db->query("SELECT id,order_no,start_time FROM lease_order 
                                                WHERE id > $start_id AND id <= $end_id                                                
                                                ");

            foreach ($results as $result) {
                if (empty($result['start_time'])) {
                    continue;
                }
                if (substr($result['order_no'], 0, 6) == date("Ym", $result['start_time'])) {
                    continue;
                }
                $ids[] = $result['id'];
            }


        }
        print_r($ids);
        save_log('sync', $ids);
        exit;

    }

    function monthPart($year = 2019)
    {
        $year = $year + 1;
        $day = strtotime("{$year}-01-01");

        $partitions = [];
        $p = 0;
        for ($i = 11; $i >= 0; $i--) {
            $p++;
            $month = strtotime("-{$i} month", $day);
            $partitions[] = "PARTITION part_{$p} VALUES LESS THAN ({$month}) ENGINE = InnoDB";
        }
        $partitions = "(" . implode(',', $partitions) . ")";
        return $partitions;
    }

    //按月分区

    function addStatText($folder, $file = 'stat_operator', $where, $params)
    {
        $path = LOG_PATH . "stat_sql/" . $folder;
        if (!is_dir($path)) {
            mkdir($path);
        }
        $filename = $path . '/' . $file . '.txt';
        $msg = ['where' => $where, 'params' => $params];
        file_put_contents($filename, json_encode($msg, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
    }

    function save_log($folder = 'debug', $msg)
    {
        $path = LOG_PATH . $folder;
        if (!is_dir($path)) {
            mkdir($path);
        }
        $filename = $path . '/' . date('Ymd') . '.sql';
        // $content = date("Y-m-d H:i:s") . "\r\n" . print_r($msg, 1) . "\r\n \r\n \r\n ";
        file_put_contents($filename, $msg . "\n", FILE_APPEND);
    }

    protected function configure()
    {
        $this->addArgument('name', Argument::REQUIRED); //必传参数
        $this->addOption('message', 'm', Option::VALUE_REQUIRED); //选项值必填
        $this->setName('trans')->setDescription('crontab');
    }

    protected function execute(Input $input, Output $output)
    {
        $database = config('database');
        $this->db = Db::connect($database);
        set_time_limit(0);
        $args = $input->getArguments();
        //$output->writeln('The args value is:');
        $action = isset($args['name']) ? $args['name'] : 'cal';
        switch ($action) {
            case  'table':
                $this->table();
                break;
            case  'order':
                $this->orderData();
                break;
            case  'alert':
                $this->alert();
                break;
            case  'test':
                $this->test();
                break;
        }
    }

    protected function table()
    {
        for ($i = 0; $i < 2; $i++) {
            $month = date('Ym', strtotime('-' . $i . ' month'));
            $this->createTable('order_brokerage_month', "order_brokerage_{$month}");
            $this->createTable('lease_order_month', "lease_order_{$month}");
            $this->createTable('order_agency', "order_agency_{$month}");
            echo "\n $month";
        }
        for ($i = 1; $i <= 6; $i++) {
            $month = date('Ym', strtotime('+' . $i . ' month'));
            $this->createTable('order_brokerage_month', "order_brokerage_{$month}");
            $this->createTable('lease_order_month', "lease_order_{$month}");
            $this->createTable('order_agency', "order_agency_{$month}");
            echo "\n $month";
        }

        for ($i = 0; $i < 16; $i++) {
            $table = dechex($i);
            $table = str_pad($table, 2, "0", STR_PAD_LEFT);
            $this->createTable('order_user', "order_user_{$table}");
            echo "\n $table";
        }
        for ($i = 0; $i < 4; $i++) {
            $table = dechex($i);
            $table = str_pad($table, 2, "0", STR_PAD_LEFT);
            $this->createTable('recharge_user', "recharge_user_{$table}");
            echo "\n $table";
        }
        echo 'success';
    }

    protected function createTable($table, $name)
    {
        //判断表是否创建
        $query = $this->db->query("SHOW TABLES LIKE '" . $name . "'");
        if ($query) {
            return $name . '已存在';
        }

        //获取建表语句
        $res = $this->db->query('SHOW CREATE TABLE ' . $table);
        if (isset($res[0]['Table'])) {
            $sql = str_replace($table, $name, $res[0]['Create Table']);
        }

        $this->db->execute($sql);
    }

    function orderData()
    {
        set_time_limit(0);
        //echo '<pre>';
        $max_id = $this->db->name('lease_order')->field('id')->order("id desc")->limit(1)->value('id');
        $min_id = 1;
        $page_size = 100;
        $i = floor($min_id / $page_size);
        $batch = ceil($max_id / $page_size);
        flush();
        ob_end_flush();
        $this->shop_manager_ids = [];
        $this->all_agency_ids = [];

        $query = $this->db->name('agency')->field('id,parents')->where(['type' => 1])->select();
        $this->all_agency_ids = array_column($query, 'parents', 'id');

        $query = $this->db->name('seller')->field('id,manager_id')->where(['manager_id' => ['>', 0]])->select();
        $this->shop_manager_ids = array_column($query, 'manager_id', 'id');
        for ($i = $batch; $i >= 0; $i--) {
            flush();
            ob_end_flush();
            $start_id = $i * $page_size;
            $end_id = $start_id + $page_size;
            echo "\n ============== no.{$i} , id: {$start_id} - {$end_id} ======================= ";
            $results = $this->db->query("SELECT id FROM lease_order 
                                                WHERE id > $start_id AND id <= $end_id                                                
                                                ");
            $ids = array();
            foreach ($results as $result) {
                $ids[] = $result['id'];
            }
            if (empty($ids)) {
                continue;
            }

            $ids = implode(",", $ids);
            $orders = $this->db->query("SELECT * FROM  lease_order  WHERE id IN(" . $ids . ") ORDER BY id desc");

            $this->userData = [];
            $this->monthData = [];
            $this->agencyData = [];
            $this->shopData = [];
            $this->deviceData = [];

            $this->stat_operator = [];
            $this->stat_agency = [];
            $this->stat_device = [];
            $this->stat_agency_device = [];
            $this->stat_agency_seller = [];

            foreach ($orders as $order) {
                $this->_transOrder($order);
            }
            /*            foreach ($this->shopData as $sid => $params) {
                            $this->db->name('seller')->where(['id' => $sid])
                                ->update([
                                    'total_amount' => ['exp', 'total_amount+' . $params['total_amount']],
                                    'total_num' => ['exp', 'total_num+' . $params['total_num']],
                                ]);
                        }
                        foreach ($this->deviceData as $cid => $params) {
                            $this->db->name('charecabinet')->where(['id' => $cid])
                                ->update([
                                    'total_amount' => ['exp', 'total_amount+' . $params['total_amount']],
                                    'total_num' => ['exp', 'total_num+' . $params['total_num']],
                                ]);
                        }*/
            foreach ($this->userData as $table => $params) {
                $this->db->name($table)->insertAll($params);
            }
            foreach ($this->monthData as $table => $params) {
                $this->db->name($table)->insertAll($params);
            }
            foreach ($this->agencyData as $table => $params) {
                $this->db->name($table)->insertAll($params);
            }
        }
        echo "done";

    }

    function _transOrder($order)
    {
        if (substr($order['order_no'], 0, 6) != date("Ym", $order['start_time'])) {//如果跨月则重置订单号
            echo " 修改订单号{$order['order_no']}";
            $order['order_no'] = date('YmdHis', $order['start_time']) . rand(1000, 9999);
            $this->db->name("lease_order")->where(['id' => $order['id']])->update(['order_no' => $order['order_no']]);
            $this->db->name("order_brokerage")->where(['order_id' => $order['id']])->update(['order_no' => $order['order_no']]);
        }


        $order['amount'] = priceFormat($order['amount']);

        if ($order['status'] == 1 || ($order['status'] == 2 && empty($order['is_pay']))) {//进行中、未支付订单表
            $order_params = $order;
            unset($order_params['id']);
            $this->db->name("order_active")->insert($order_params);
        } else if ($order['status'] == 2 && !empty($order['is_lose'])) {//丢失充电宝
            $order_params = $order;
            unset($order_params['id']);
            $this->db->name("order_lose")->insert($order_params);
        }


        //用户表
        $table = getTableNo('order_user', 'hash', 16, $order['uid']);
        $user_params = [
            'uid' => $order['uid'],
            'order_no' => $order['order_no'],
            'type' => $order['type'],
            'status' => $order['status'],
            'start_time' => $order['start_time'],
            'end_time' => $order['end_time'],
            'expire_time' => $order['expire_time'],
            'amount' => $order['amount'],
            'is_pay' => $order['is_pay'],
        ];
        $this->userData[$table][] = $user_params;
        //  $this->db->name($table)->insert($user_params);

        if (empty($order['start_time'])) { //没有正式下单
            return;
        }

        //订单月表（预下单时间与租借成功时间处理）
        $month = date("Ym", $order['start_time']);
        $order_params = $order;
        unset($order_params['id']);
        $this->monthData["lease_order_{$month}"][] = $order_params;
        // $this->db->name("lease_order_{$month}")->insert($order_params);


        //代理商关系表
        $data = [
            'order_no' => $order['order_no'],
            'type' => $order['type'],
            'relation_id' => 0,
            'sid' => $order['sid'],
            'is_lose' => $order['is_lose'],
            'is_late' => 0,
            'uid' => $order['uid'],
            'agency_id' => $order['agency_id'],
            'employee_id' => $order['employee_id'],
            'app_type' => $order['app_type'],
            'start_time' => $order['start_time'],
            'end_time' => $order['end_time'],
            'status' => $order['status'],
            'amount' => $order['amount'],
            'device_id' => $order['device_id'],
            'battery_id' => $order['battery_id'],
            'is_self' => 1,
            'is_pay' => $order['is_pay'],
            'is_credit' => empty($order['pay_auth_id']) ? 0 : 1,
            'agency_end' => $order['agency_end'],
        ];
        $params = [];
        if (!isset($this->deviceData[$order['device_code']])) {
            $this->deviceData[$order['device_code']] = ['total_num' => 0, 'total_amount' => 0];
        }
        $this->deviceData[$order['device_code']]['total_num'] += 1;
        if ($order['status'] == 2) {
            $this->deviceData[$order['device_code']]['total_amount'] = bcadd($this->deviceData[$order['device_code']]['total_amount'], $order['amount'], 2);
        }
        if (!empty($order['employee_id'])) {
            $data['relation_id'] = $order['employee_id'];
            $params[] = $data;
        }
        if (!empty($order['sid'])) {
            if (!isset($this->shopData[$order['sid']])) {
                $this->shopData[$order['sid']] = ['total_num' => 0, 'total_amount' => 0];
            }
            $this->shopData[$order['sid']]['total_num'] += 1;
            if ($order['status'] == 2) {
                $this->shopData[$order['sid']]['total_amount'] = bcadd($this->shopData[$order['sid']]['total_amount'], $order['amount'], 2);
            }
            $manager_id = isset($this->shop_manager_ids[$order['sid']]) ? $this->shop_manager_ids[$order['sid']] : '';
            if ($manager_id) {
                $data['relation_id'] = $manager_id;
                $params[] = $data;
            }
        }
        if (!empty($order['agency_id'])) {
            $data['relation_id'] = $order['agency_id'];
            $params[] = $data;
            $parents = isset($this->all_agency_ids[$order['agency_id']]) ? $this->all_agency_ids[$order['agency_id']] : '';
            if (!empty($parents)) {
                $parents = explode(",", $parents);
                foreach ($parents as $parent) {
                    $data['relation_id'] = $parent;
                    $data['is_self'] = 0;
                    $params[] = $data;
                }
            }
        }
        foreach ($params as $val) {
            $this->agencyData["order_agency_{$month}"][] = $val;
        }

        //$params && $this->db->name("order_agency_{$month}")->insertAll($params);

    }

    protected function alert()
    {
        for ($i = 0; $i < 10; $i++) {
            $month = date('Ym', strtotime('-' . $i . ' month'));
            $sql = "  ALTER TABLE `order_agency_{$month}` DROP `order_id`;   ";
            $this->db->execute($sql);
        }
        for ($i = 1; $i <= 3; $i++) {
            $month = date('Ym', strtotime('+' . $i . ' month'));
            $sql = "  ALTER TABLE `order_agency_{$month}` DROP `order_id`;   ";
            $this->db->execute($sql);
        }
        echo 'success';
    }

    protected function test()
    {
        $this->device();//设备
        $this->agencySeller();//商户
        $this->orderData();//订单
        $this->_brokerageLog();//分成
        $this->_authLog();//授权
        $this->_tradeLog();//交易
        $this->_accountLog();//用户记录
        echo "done";
    }

    public function device()
    {
        set_time_limit(0);

        $agency = $this->db->name('agency')->field('id,parents')->where(['not_delete' => 1, 'type' => 1])->select();
        $agency = array_column($agency, NULL, 'id');

        $lose = [];
        $query = $this->db->name('charecabinet')->field('id,cabinet_id,agency_id')->where(['not_delete' => 1, 'agency_id' => ['>', 0]])->select();

        foreach ($query as $device) {
            flush();
            ob_end_flush();
            echo "{$device['cabinet_id']}\n";
            if (!isset($agency[$device['agency_id']])) {
                $lose[] = $device['agency_id'];
                continue;
            }
            $params = [];
            $params[] = [
                'agency_id' => $device['agency_id'],
                'device_code' => $device['cabinet_id'],
                'type' => 1,
                'is_self' => 1
            ];
            $parents = explode(",", $agency[$device['agency_id']]['parents']);
            empty($parents) && $parents = [];
            foreach ($parents as $v) {
                $v = intval($v);
                if (empty($v)) {
                    continue;
                }
                $params[] = [
                    'agency_id' => $v,
                    'device_code' => $device['cabinet_id'],
                    'type' => 1,
                    'is_self' => 0
                ];
            }
            $this->db->name('device_agency')->insertAll($params);
        }


        $query = $this->db->name('wired_device')->field('id,device_id as cabinet_id,agency_id')->where(['not_delete' => 1, 'agency_id' => ['>', 0]])->select();

        foreach ($query as $device) {
            flush();
            ob_end_flush();
            echo "{$device['cabinet_id']}\n";
            if (!isset($agency[$device['agency_id']])) {
                $lose[] = $device['agency_id'];
                continue;
            }
            $params = [];
            $params[] = [
                'agency_id' => $device['agency_id'],
                'device_code' => $device['cabinet_id'],
                'type' => 2,
                'is_self' => 1
            ];
            $parents = explode(",", $agency[$device['agency_id']]['parents']);
            empty($parents) && $parents = [];
            foreach ($parents as $v) {
                $v = intval($v);
                if (empty($v)) {
                    continue;
                }
                $params[] = [
                    'agency_id' => $v,
                    'device_code' => $device['cabinet_id'],
                    'type' => 2,
                    'is_self' => 0
                ];
            }
            $this->db->name('device_agency')->insertAll($params);
        }
        $lose = array_unique($lose);
        print_r($lose);
    }

    //授权记录

    public function agencySeller()
    {
        set_time_limit(0);
        $agency = $this->db->name('agency')->field('id,parents')->where(['not_delete' => 1, 'type' => 1])->select();
        $agency = array_column($agency, NULL, 'id');
        $lose = [];
        $query = $this->db->name('seller')->field('id,agency_id')->where(['not_delete' => 1, 'agency_id' => ['>', 0]])->select();
        foreach ($query as $seller) {
            flush();
            ob_end_flush();
            $seller['agency_id'] = intval($seller['agency_id']);
            echo "{$seller['id']}\n";
            if (!isset($agency[$seller['agency_id']])) {
                $lose[] = $seller['agency_id'];
                continue;
            }
            $params = [];
            $params[] = [
                'agency_id' => $seller['agency_id'],
                'sid' => $seller['id'],
                'is_self' => 1
            ];
            $parents = explode(",", $agency[$seller['agency_id']]['parents']);
            empty($parents) && $parents = [];
            foreach ($parents as $v) {
                $v = intval($v);
                if (empty($v)) {
                    continue;
                }
                $params[] = [
                    'agency_id' => $v,
                    'sid' => $seller['id'],
                    'is_self' => 0
                ];
            }
            $this->db->name('seller_agency')->insertAll($params);
        }
        $lose = array_unique($lose);
        print_r($lose);
    }

    function _brokerageLog()
    {
        set_time_limit(0);

        //退款记录
        $max_id = $this->db->name('order_brokerage')->field('id')->order("id desc")->limit(1)->value('id');
        $min_id = 1;
        $page_size = 200;
        $i = floor($min_id / $page_size);
        $batch = ceil($max_id / $page_size);
        flush();
        ob_end_flush();
        echo "brokerageLog\n";

        $this->stat_operator = [];
        $this->stat_agency = [];
        for ($i = $batch; $i >= 0; $i--) {
            flush();
            ob_end_flush();
            $start_id = $i * $page_size;
            $end_id = $start_id + $page_size;
            echo "\n ============== no.{$i} , id: {$start_id} - {$end_id} ======================= ";
            $results = $this->db->query("SELECT id FROM order_brokerage 
                                                WHERE id > $start_id AND id <= $end_id                                             
                                                ");
            $ids = array();
            foreach ($results as $result) {
                $ids[] = $result['id'];
            }
            if (empty($ids)) {
                continue;
            }

            $ids = implode(",", $ids);
            $logs = $this->db->query("SELECT * FROM  order_brokerage  WHERE id IN(" . $ids . ") ORDER BY id desc");
            $data = [];
            $agency_data = [];
            $operator_data = [];
            foreach ($logs as $v) {
                $month = date("Ym", $v['create_time']);
                $create_day = date("Y-m-d", $v['create_time']);
                $settlement_day = date("Y-m-d", $v['settlement_time']);
                unset($v['id']);
                $data[$month][] = $v;
                if ($v['status'] == 3) {
                    continue;
                }


                !isset($this->stat_agency[$create_day]) && $this->stat_agency[$create_day] = [];
                if (!isset($this->stat_agency[$create_day][$v['relation_id']])) {
                    $this->stat_agency[$create_day][$v['relation_id']] = [
                        'battery_amount' => 0,
                        'battery_pay_amount' => 0,
                        'battery_num' => 0,
                        'battery_pay_num' => 0,
                        'wired_amount' => 0,
                        'wired_num' => 0,
                        'total_amount' => 0,
                        'total_pay_amount' => 0,
                        'total_num' => 0,
                        'total_pay_num' => 0,
                        'brokerage_amount' => 0,
                        'brokerage_settle' => 0,
                    ];
                }
                $this->stat_agency[$create_day][$v['relation_id']]['brokerage_amount'] = bcadd($this->stat_agency[$create_day][$v['relation_id']]['brokerage_amount'], $v['amount'], 2);

                !isset($this->stat_operator[$create_day]) && $this->stat_operator[$create_day] = [
                    'battery_num' => 0,
                    'wired_num' => 0,
                    'total_num' => 0,
                    'wired_amount' => 0,
                    'total_amount' => 0,
                    'total_pay_amount' => 0,
                    'total_pay_num' => 0,
                    'brokerage_amount' => 0,
                    'brokerage_settle' => 0,
                ];
                $this->stat_operator[$create_day]['brokerage_amount'] = bcadd($this->stat_operator[$create_day]['brokerage_amount'], $v['amount'], 2);

                if ($v['status'] == 2 || $v['status'] == 9) {
                    !isset($this->stat_agency[$settlement_day]) && $this->stat_agency[$settlement_day] = [];
                    if (!isset($this->stat_agency[$settlement_day][$v['relation_id']])) {
                        $this->stat_agency[$settlement_day][$v['relation_id']] = [
                            'battery_amount' => 0,
                            'battery_pay_amount' => 0,
                            'battery_num' => 0,
                            'battery_pay_num' => 0,
                            'wired_amount' => 0,
                            'wired_num' => 0,
                            'total_amount' => 0,
                            'total_pay_amount' => 0,
                            'total_num' => 0,
                            'total_pay_num' => 0,
                            'brokerage_amount' => 0,
                            'brokerage_settle' => 0,
                        ];
                    }

                    $this->stat_agency[$settlement_day][$v['relation_id']]['brokerage_settle'] = bcadd($this->stat_agency[$settlement_day][$v['relation_id']]['brokerage_settle'], $v['amount'], 2);

                    !isset($this->stat_operator[$settlement_day]) && $this->stat_operator[$settlement_day] = [
                        'battery_num' => 0,
                        'wired_num' => 0,
                        'total_num' => 0,
                        'wired_amount' => 0,
                        'total_amount' => 0,
                        'total_pay_amount' => 0,
                        'total_pay_num' => 0,
                        'brokerage_amount' => 0,
                        'brokerage_settle' => 0,
                    ];
                    $this->stat_operator[$settlement_day]['brokerage_settle'] = bcadd($this->stat_operator[$settlement_day]['brokerage_settle'], $v['amount'], 2);
                }
            }
            foreach ($data as $month => $params) {
                $this->db->name('order_brokerage_' . $month)->insertAll($params);
            }

        }
        echo "done";
        $this->statData();
    }

    function statData()
    {
        echo "\n statData";
        set_time_limit(0);
        //echo '<pre>';
        $max_id = $this->db->name('lease_order')->field('id')->order("id desc")->limit(1)->value('id');
        $min_id = 1;
        $page_size = 200;
        $i = floor($min_id / $page_size);
        $batch = ceil($max_id / $page_size);
        flush();
        ob_end_flush();

//        $this->stat_operator = [];
//        $this->stat_agency = [];
        $this->stat_device = [];
        $this->stat_seller = [];
        $this->stat_agency_device = [];
        $this->stat_agency_seller = [];

        $this->shop_manager_ids = [];
        $this->all_agency_ids = [];

        $query = $this->db->name('agency')->field('id,parents')->where(['type' => 1])->select();
        $this->all_agency_ids = array_column($query, 'parents', 'id');

        $query = $this->db->name('seller')->field('id,manager_id')->where(['manager_id' => ['>', 0]])->select();
        $this->shop_manager_ids = array_column($query, 'manager_id', 'id');

        for ($i = $batch; $i >= 0; $i--) {
            flush();
            ob_end_flush();
            $start_id = $i * $page_size;
            $end_id = $start_id + $page_size;
            echo "\n ============== no.{$i} , id: {$start_id} - {$end_id} ======================= ";
            $results = $this->db->query("SELECT id FROM lease_order 
                                                WHERE id > $start_id AND id <= $end_id                                                
                                                ");
            $ids = array();
            foreach ($results as $result) {
                $ids[] = $result['id'];
            }
            if (empty($ids)) {
                continue;
            }

            $ids = implode(",", $ids);
            $orders = $this->db->query("SELECT * FROM  lease_order  WHERE id IN(" . $ids . ") ORDER BY id desc");


            foreach ($orders as $order) {
                $this->_transStat($order);
            }

        }

        foreach ($this->stat_operator as $day => $params) {
            $sql = addNumSql('stat_operator', ['date' => $day], $params);
            $this->db->execute($sql);
        }
        echo "stat_device\n";
        $tmp = [];
        $n = 0;
        foreach ($this->stat_device as $day => $device) {
            foreach ($device as $code => $params) {
                $n++;
                $params['date'] = $day;
                $params['device_id'] = $code;
                $tmp[] = $params;
                if ($n % 100 == 1) {
                    $this->db->name('stat_device')->insertAll($tmp);
                    $tmp = [];
                }
            }
        }

        $this->db->name('stat_device')->insertAll($tmp);

        echo "stat_seller\n";
        $tmp = [];
        $n = 0;
        foreach ($this->stat_seller as $day => $seller) {
            foreach ($seller as $code => $params) {
                $n++;
                $params['date'] = $day;
                $params['sid'] = $code;
                $tmp[] = $params;
                if ($n % 100 == 1) {
                    $this->db->name('stat_seller')->insertAll($tmp);
                    $tmp = [];
                }
            }
        }
        $this->db->name('stat_seller')->insertAll($tmp);

        echo "stat_agency\n";
        $tmp = [];
        $n = 0;
        foreach ($this->stat_agency as $day => $agency) {
            foreach ($agency as $code => $params) {
                $params['date'] = $day;
                $params['agency_id'] = $code;
                $tmp[] = $params;
                $n++;
                if ($n % 100 == 1) {
                    $this->db->name('stat_agency')->insertAll($tmp);
                    $tmp = [];
                }
            }
        }
        $this->db->name('stat_agency')->insertAll($tmp);

        $tmp = [];
        $n = 0;
        echo "stat_agency_seller\n";
        foreach ($this->stat_agency_seller as $day => $agency) {
            foreach ($agency as $code => $params_data) {
                foreach ($params_data as $sid => $params) {
                    $params['date'] = $day;
                    $params['relation_id'] = $code;
                    $params['sid'] = $sid;
                    $tmp[] = $params;
                    $n++;
                    if ($n % 100 == 1) {
                        $this->db->name('stat_agency_seller')->insertAll($tmp);
                        $tmp = [];
                    }
                }
            }
        }

        $this->db->name('stat_agency_seller')->insertAll($tmp);

        echo "stat_agency_device\n";
        $tmp = [];
        $n = 0;
        foreach ($this->stat_agency_device as $day => $agency) {
            foreach ($agency as $code => $params_data) {
                foreach ($params_data as $device_id => $params) {
                    $params['date'] = $day;
                    $params['relation_id'] = $code;
                    $params['device_id'] = $device_id;
                    $tmp[] = $params;
                    $n++;
                    if ($n % 100 == 1) {
                        $this->db->name('stat_agency_device')->insertAll($tmp);
                        $tmp = [];
                    }
                }
            }
        }
        $this->db->name('stat_agency_device')->insertAll($tmp);
        echo "done";
    }


    //用户资金记录

    function _transStat($order)
    {
        $stat_seller_fields = [
            'battery_amount' => 0,
            'battery_pay_amount' => 0,
            'battery_num' => 0,
            'battery_pay_num' => 0,
            'wired_amount' => 0,
            'wired_num' => 0,
            'total_amount' => 0,
            'total_pay_amount' => 0,
            'total_num' => 0,
            'total_pay_num' => 0,
        ];

        $stat_agency_fields = [
            'battery_amount' => 0,
            'battery_pay_amount' => 0,
            'battery_num' => 0,
            'battery_pay_num' => 0,
            'wired_amount' => 0,
            'wired_num' => 0,
            'total_amount' => 0,
            'total_pay_amount' => 0,
            'total_num' => 0,
            'total_pay_num' => 0,
            'brokerage_amount' => 0,
            'brokerage_settle' => 0,
        ];

        $stat_device_fields = [
            'create_num' => 0,
            'settle_num' => 0,
            'amount' => 0,
            'pay_amount' => 0,
        ];


        $stat_agency_seller_fields = [
            'order_amount' => 0,
            'pay_amount' => 0,
            'order_num' => 0,
            'pay_num' => 0,
        ];


        $stat_agency_device_fields = [
            'order_amount' => 0,
            'pay_amount' => 0,
            'order_num' => 0,
            'pay_num' => 0,
        ];


        $order['amount'] = priceFormat($order['amount']);
        $order['payment_amount'] = priceFormat($order['payment_amount']);
        if (empty($order['start_time']) || $order['status'] == 3) { //没有正式下单
            return;
        }
        $create_day = date('Y-m-d', $order['start_time']);
        $agency_ids = [];
        if (!empty($order['employee_id'])) {
            !isset($this->stat_agency[$create_day]) && $this->stat_agency[$create_day] = [];
            $agency_ids[] = $order['employee_id'];
        }
        if (!empty($order['sid'])) {
            $manager_id = isset($this->shop_manager_ids[$order['sid']]) ? $this->shop_manager_ids[$order['sid']] : 0;
            if ($manager_id) {
                !isset($this->stat_agency[$create_day]) && $this->stat_agency[$create_day] = [];
                $agency_ids[] = $manager_id;
            }
        }

        if (!empty($order['agency_id'])) {
            !isset($this->stat_agency[$create_day]) && $this->stat_agency[$create_day] = [];
            $agency_ids[] = $order['agency_id'];

            $parents = isset($this->all_agency_ids[$order['agency_id']]) ? $this->all_agency_ids[$order['agency_id']] : '';
            $parents = explode(",", $parents);
            empty($parents) && $parents = [];
            foreach ($parents as $parent) {
                if (empty($parent)) {
                    continue;
                }
                $agency_ids[] = $parent;
            }
        }
        $agency_ids = array_unique($agency_ids);

        //平台创建订单
        !isset($this->stat_operator[$create_day]) && $this->stat_operator[$create_day] = [
            'battery_num' => 0,
            'wired_num' => 0,
            'total_num' => 0,
            'wired_amount' => 0,
            'total_amount' => 0,
            'total_pay_amount' => 0,
            'total_pay_num' => 0,
            'brokerage_amount' => 0,
            'brokerage_settle' => 0,
        ];
        $this->stat_operator[$create_day]['total_num'] += 1;
        if ($order['type'] == 1) {
            $this->stat_operator[$create_day]['battery_num'] += 1;
        } else {
            $this->stat_operator[$create_day]['wired_num'] += 1;
            $this->stat_operator[$create_day]['total_pay_num'] += 1;
            $this->stat_operator[$create_day]['wired_amount'] = bcadd($this->stat_operator[$create_day]['wired_amount'], $order['amount'], 2);
            $this->stat_operator[$create_day]['total_amount'] = bcadd($this->stat_operator[$create_day]['total_amount'], $order['amount'], 2);
            $this->stat_operator[$create_day]['total_pay_amount'] = bcadd($this->stat_operator[$create_day]['total_pay_amount'], $order['amount'], 2);
        }
        //商户创建订单
        !isset($this->stat_seller[$create_day]) && $this->stat_seller[$create_day] = [];
        if (!isset($this->stat_seller[$create_day][$order['sid']])) {
            $this->stat_seller[$create_day][$order['sid']] = $stat_seller_fields;
        }

        $this->stat_seller[$create_day][$order['sid']]['total_num'] += 1;
        if ($order['type'] == 1) {
            $this->stat_seller[$create_day][$order['sid']]['battery_num'] += 1;
        } else {
            $this->stat_seller[$create_day][$order['sid']]['wired_num'] += 1;
            $this->stat_seller[$create_day][$order['sid']]['total_pay_num'] += 1;
            $this->stat_seller[$create_day][$order['sid']]['wired_amount'] = bcadd($this->stat_seller[$create_day][$order['sid']]['wired_amount'], $order['amount'], 2);
            $this->stat_seller[$create_day][$order['sid']]['total_amount'] = bcadd($this->stat_seller[$create_day][$order['sid']]['total_amount'], $order['amount'], 2);
            $this->stat_seller[$create_day][$order['sid']]['total_pay_amount'] = bcadd($this->stat_seller[$create_day][$order['sid']]['total_pay_amount'], $order['amount'], 2);
        }
        //代理商
        foreach ($agency_ids as $aid) {
            if (!isset($this->stat_agency[$create_day][$aid])) {
                $this->stat_agency[$create_day][$aid] = $stat_agency_fields;
            }

            $this->stat_agency[$create_day][$aid]['total_num'] += 1;
            if ($order['type'] == 1) {
                $this->stat_agency[$create_day][$aid]['battery_num'] += 1;

                //代理设备
                if (!isset($this->stat_agency_device[$create_day][$aid][$order['device_code']])) {
                    $this->stat_agency_device[$create_day][$aid][$order['device_code']] = $stat_agency_device_fields;
                }
                $this->stat_agency_device[$create_day][$aid][$order['device_code']]['order_num'] += 1;
            } else {
                $this->stat_agency[$create_day][$aid]['wired_num'] += 1;
                $this->stat_agency[$create_day][$aid]['total_pay_num'] += 1;
                $this->stat_agency[$create_day][$aid]['wired_amount'] = bcadd($this->stat_agency[$create_day][$aid]['wired_amount'], $order['amount'], 2);
                $this->stat_agency[$create_day][$aid]['total_amount'] = bcadd($this->stat_agency[$create_day][$aid]['total_amount'], $order['amount'], 2);
                $this->stat_agency[$create_day][$aid]['total_pay_amount'] = bcadd($this->stat_agency[$create_day][$aid]['total_pay_amount'], $order['amount'], 2);
            }


            //代理商户
            if (!isset($this->stat_agency_seller[$create_day][$aid][$order['sid']])) {
                $this->stat_agency_seller[$create_day][$aid][$order['sid']] = $stat_agency_seller_fields;
            }
            $this->stat_agency_seller[$create_day][$aid][$order['sid']]['order_num'] += 1;
            if ($order['type'] == 2) {
                $this->stat_agency_seller[$create_day][$aid][$order['sid']]['pay_num'] += 1;
                $this->stat_agency_seller[$create_day][$aid][$order['sid']]['order_amount'] = bcadd($this->stat_agency_seller[$create_day][$aid][$order['sid']]['order_amount'], $order['amount'], 2);
                $this->stat_agency_seller[$create_day][$aid][$order['sid']]['pay_amount'] = bcadd($this->stat_agency_seller[$create_day][$aid][$order['sid']]['pay_amount'], $order['amount'], 2);
            }

        }


        //设备创建订单
        if ($order['type'] == 1) {
            !isset($this->stat_device[$create_day]) && $this->stat_device[$create_day] = [];
            if (!isset($this->stat_device[$create_day][$order['device_code']])) {
                $this->stat_device[$create_day][$order['device_code']] = $stat_device_fields;
            }
            $this->stat_device[$create_day][$order['device_code']]['create_num'] += 1;
        }

        if ($order['type'] == 1 && $order['status'] == 2) {
            $end_day = date('Y-m-d', $order['end_time']);
            //平台订单金额
            !isset($this->stat_operator[$end_day]) && $this->stat_operator[$end_day] = [
                'battery_amount' => 0,
                'total_amount' => 0,
            ];
            $this->stat_operator[$end_day]['battery_amount'] = bcadd($this->stat_operator[$end_day]['battery_amount'], $order['amount'], 2);
            $this->stat_operator[$end_day]['total_amount'] = bcadd($this->stat_operator[$end_day]['total_amount'], $order['amount'], 2);


            //商户订单金额
            !isset($this->stat_seller[$end_day]) && $this->stat_seller[$end_day] = [];
            if (!isset($this->stat_seller[$end_day][$order['sid']])) {
                $this->stat_seller[$end_day][$order['sid']] = $stat_seller_fields;
            }
            $this->stat_seller[$end_day][$order['sid']]['total_amount'] = bcadd($this->stat_seller[$end_day][$order['sid']]['total_amount'], $order['amount'], 2);
            $this->stat_seller[$end_day][$order['sid']]['battery_amount'] = bcadd($this->stat_seller[$end_day][$order['sid']]['battery_amount'], $order['amount'], 2);

            //设备订单金额
            !isset($this->stat_device[$end_day]) && $this->stat_device[$end_day] = [];
            if (!isset($this->stat_device[$end_day][$order['device_code']])) {
                $this->stat_device[$end_day][$order['device_code']] = $stat_device_fields;
            }
            $this->stat_device[$end_day][$order['device_code']]['amount'] = bcadd($this->stat_device[$end_day][$order['device_code']]['amount'], $order['amount'], 2);

            //代理订单金额
            foreach ($agency_ids as $aid) {
                !isset($this->stat_agency[$end_day]) && $this->stat_agency[$end_day] = [];
                if (!isset($this->stat_agency[$end_day][$aid])) {
                    $this->stat_agency[$end_day][$aid] = $stat_agency_fields;
                }
                $this->stat_agency[$end_day][$aid]['total_amount'] = bcadd($this->stat_agency[$end_day][$aid]['total_amount'], $order['amount'], 2);
                $this->stat_agency[$end_day][$aid]['battery_amount'] = bcadd($this->stat_agency[$end_day][$aid]['battery_amount'], $order['amount'], 2);
                //代理设备
                if (!isset($this->stat_agency_device[$end_day][$aid][$order['device_code']])) {
                    $this->stat_agency_device[$end_day][$aid][$order['device_code']] = $stat_agency_device_fields;
                }
                $this->stat_agency_device[$end_day][$aid][$order['device_code']]['order_amount'] = bcadd($this->stat_agency_device[$end_day][$aid][$order['device_code']]['order_amount'], $order['amount'], 2);

                //代理商户
                if (!isset($this->stat_agency_seller[$end_day][$aid][$order['sid']])) {
                    $this->stat_agency_seller[$end_day][$aid][$order['sid']] = $stat_agency_seller_fields;
                }
                $this->stat_agency_seller[$end_day][$aid][$order['sid']]['order_amount'] = bcadd($this->stat_agency_seller[$end_day][$aid][$order['sid']]['order_amount'], $order['amount'], 2);

            }


        }


        if ($order['type'] == 1 && $order['status'] == 2 && $order['is_pay'] == 1) {
            $pay_day = date('Y-m-d', $order['payment_time']);
            //平台订单金额
            !isset($this->stat_operator[$pay_day]) && $this->stat_operator[$pay_day] = [
                'battery_pay_amount' => 0,
                'total_pay_amount' => 0,
                'battery_pay_num' => 0,
                'total_pay_num' => 0,
            ];
            $this->stat_operator[$pay_day]['battery_pay_amount'] = bcadd($this->stat_operator[$pay_day]['battery_pay_amount'], $order['amount'], 2);
            $this->stat_operator[$pay_day]['total_pay_amount'] = bcadd($this->stat_operator[$pay_day]['total_pay_amount'], $order['amount'], 2);
            $this->stat_operator[$pay_day]['battery_pay_num'] += 1;
            $this->stat_operator[$pay_day]['total_pay_num'] += 1;

            //商户订单金额
            !isset($this->stat_seller[$pay_day]) && $this->stat_seller[$pay_day] = [];
            if (!isset($this->stat_seller[$pay_day][$order['sid']])) {
                $this->stat_seller[$pay_day][$order['sid']] = $stat_seller_fields;
            }
            $this->stat_seller[$pay_day][$order['sid']]['total_pay_amount'] = bcadd($this->stat_seller[$pay_day][$order['sid']]['total_pay_amount'], $order['amount'], 2);
            $this->stat_seller[$pay_day][$order['sid']]['battery_pay_amount'] = bcadd($this->stat_seller[$pay_day][$order['sid']]['battery_pay_amount'], $order['amount'], 2);
            $this->stat_seller[$pay_day][$order['sid']]['battery_pay_num'] += 1;
            $this->stat_seller[$pay_day][$order['sid']]['total_pay_num'] += 1;

            //设备订单金额
            !isset($this->stat_device[$pay_day]) && $this->stat_device[$pay_day] = [];
            if (!isset($this->stat_device[$pay_day][$order['device_code']])) {
                $this->stat_device[$pay_day][$order['device_code']] = $stat_device_fields;
            }
            $this->stat_device[$pay_day][$order['device_code']]['settle_num'] += 1;
            $this->stat_device[$pay_day][$order['device_code']]['pay_amount'] = bcadd($this->stat_device[$pay_day][$order['device_code']]['pay_amount'], $order['amount'], 2);

            //代理订单金额
            foreach ($agency_ids as $aid) {
                !isset($this->stat_agency[$pay_day]) && $this->stat_agency[$pay_day] = [];
                if (!isset($this->stat_agency[$pay_day][$aid])) {
                    $this->stat_agency[$pay_day][$aid] = $stat_agency_fields;
                }
                $this->stat_agency[$pay_day][$aid]['total_pay_amount'] = bcadd($this->stat_agency[$pay_day][$aid]['total_pay_amount'], $order['amount'], 2);
                $this->stat_agency[$pay_day][$aid]['battery_pay_amount'] = bcadd($this->stat_agency[$pay_day][$aid]['battery_pay_amount'], $order['amount'], 2);
                $this->stat_agency[$pay_day][$aid]['battery_pay_num'] += 1;
                $this->stat_agency[$pay_day][$aid]['total_pay_num'] += 1;


                //代理设备
                if (!isset($this->stat_agency_device[$pay_day][$aid][$order['device_code']])) {
                    $this->stat_agency_device[$pay_day][$aid][$order['device_code']] = $stat_agency_device_fields;
                }
                $this->stat_agency_device[$pay_day][$aid][$order['device_code']]['pay_amount'] = bcadd($this->stat_agency_device[$pay_day][$aid][$order['device_code']]['pay_amount'], $order['amount'], 2);
                $this->stat_agency_device[$pay_day][$aid][$order['device_code']]['pay_num'] += 1;
                //代理商户
                if (!isset($this->stat_agency_seller[$pay_day][$aid][$order['sid']])) {
                    $this->stat_agency_seller[$pay_day][$aid][$order['sid']] = $stat_agency_seller_fields;
                }
                $this->stat_agency_seller[$pay_day][$aid][$order['sid']]['pay_amount'] = bcadd($this->stat_agency_seller[$pay_day][$aid][$order['sid']]['pay_amount'], $order['amount'], 2);
                $this->stat_agency_seller[$pay_day][$aid][$order['sid']]['pay_num'] += 1;
            }
        }

    }

    function _authLog()
    {
        set_time_limit(0);

        //退款记录
        $max_id = $this->db->name('pay_auth_log')->field('id')->order("id desc")->limit(1)->value('id');
        $min_id = 1;
        $page_size = 100;
        $i = floor($min_id / $page_size);
        $batch = ceil($max_id / $page_size);
        flush();
        ob_end_flush();
        for ($i = $batch; $i >= 0; $i--) {
            flush();
            ob_end_flush();
            $start_id = $i * $page_size;
            $end_id = $start_id + $page_size;
            echo "\n ============== no.{$i} , id: {$start_id} - {$end_id} ======================= ";
            $results = $this->db->query("SELECT id FROM pay_auth_log 
                                                WHERE id > $start_id AND id <= $end_id  
                                                AND pay_status = 1                                              
                                                ");
            $ids = array();
            foreach ($results as $result) {
                $ids[] = $result['id'];
            }
            if (empty($ids)) {
                continue;
            }

            $ids = implode(",", $ids);
            $logs = $this->db->query("SELECT * FROM  pay_auth_log  WHERE id IN(" . $ids . ") ORDER BY id desc");
            $data = [];
            foreach ($logs as $v) {
                if (empty($v['pay_status']) || $v['status'] < 4) {
                    continue;
                }
                $year = date("Y", $v['create_time']);
                unset($v['id'], $v['lease_id']);
                $data[$year][] = $v;
            }
            foreach ($data as $year => $params) {
                $this->db->name('pay_auth_log_' . $year)->insertAll($params);
            }
        }

        echo "done";
    }

    //交易流水记录
    function _tradeLog()
    {
        set_time_limit(0);
        //充值记录
        $max_id = $this->db->name('recharge_log')->field('id')->order("id desc")->limit(1)->value('id');
        $min_id = 1;
        $page_size = 200;
        $i = floor($min_id / $page_size);
        $batch = ceil($max_id / $page_size);
        flush();
        ob_end_flush();
        for ($i = $batch; $i >= 0; $i--) {
            flush();
            ob_end_flush();
            $start_id = $i * $page_size;
            $end_id = $start_id + $page_size;
            echo "\n ============== no.{$i} , id: {$start_id} - {$end_id} ======================= ";
            $results = $this->db->query("SELECT id FROM recharge_log 
                                                WHERE id > $start_id AND id <= $end_id  
                                                AND pay_status = 1                                              
                                                ");
            $ids = array();
            foreach ($results as $result) {
                $ids[] = $result['id'];
            }

            if (empty($ids)) {
                continue;
            }

            $ids = implode(",", $ids);
            $logs = $this->db->query("SELECT * FROM  recharge_log  WHERE id IN(" . $ids . ") ORDER BY id desc");
            $data = [];
            $users = [];
            foreach ($logs as $v) {
                empty($v['payment_time']) && $v['payment_time'] = $v['create_time'] + 10;//兼容出错数据
                $year = date("Y", $v['payment_time']);
                $data[$year][] = [
                    'order_no' => $v['order_no'],
                    'trade_no' => $v['trade_no'],
                    'uid' => $v['uid'],
                    'amount' => $v['amount'],
                    'pay_type' => $v['pay_type'],
                    'type' => 1,
                    'create_time' => $v['payment_time'],
                ];
                $user_table = getTableNo('recharge_user', 'hash', 4, $v['uid']);
                $users[$user_table][] = [
                    'order_no' => $v['order_no'],
                    'trade_no' => $v['trade_no'],
                    'uid' => $v['uid'],
                    'amount' => $v['amount'],
                    'balance' => $v['balance'],
                    'pay_type' => $v['pay_type'],
                    'is_credit' => empty($v['auth_log_id']) ? 0 : 1,
                    'payment_time' => $v['payment_time'],
                ];
            }

            foreach ($data as $year => $params) {
                $this->db->name('trade_log_' . $year)->insertAll($params);
            }
            foreach ($users as $table => $params) {
                $this->db->name($table)->insertAll($params);
            }
        }

        //退款记录
        $max_id = $this->db->name('refund_log')->field('id')->order("id desc")->limit(1)->value('id');
        $min_id = 1;
        $page_size = 100;
        $i = floor($min_id / $page_size);
        $batch = ceil($max_id / $page_size);
        flush();
        ob_end_flush();
        for ($i = $batch; $i >= 0; $i--) {
            flush();
            ob_end_flush();
            $start_id = $i * $page_size;
            $end_id = $start_id + $page_size;
            echo "\n ============== no.{$i} , id: {$start_id} - {$end_id} ======================= ";
            $results = $this->db->query("SELECT id FROM refund_log 
                                                WHERE id > $start_id AND id <= $end_id  
                                                AND status = 1                                              
                                                ");
            $ids = array();
            foreach ($results as $result) {
                $ids[] = $result['id'];
            }
            if (empty($ids)) {
                continue;
            }

            $ids = implode(",", $ids);
            $logs = $this->db->query("SELECT * FROM  refund_log  WHERE id IN(" . $ids . ") ORDER BY id desc");
            $data = [];
            foreach ($logs as $v) {
                empty($v['refund_time']) && $v['refund_time'] = $v['create_time'] + 1;//兼容出错数据
                $year = date("Y", $v['refund_time']);
                $data[$year][] = [
                    'order_no' => $v['refund_no'],
                    'trade_no' => $v['transaction_id'],
                    'recharge_log' => $v['order_no'],
                    'uid' => $v['uid'],
                    'amount' => $v['amount'],
                    'pay_type' => $v['pay_type'],
                    'type' => 2,
                    'create_time' => $v['refund_time'],
                ];
            }
            foreach ($data as $year => $params) {
                $this->db->name('trade_log_' . $year)->insertAll($params);
            }
        }

        echo "done";
    }

    function _accountLog()
    {
        set_time_limit(0);

        $max_id = $this->db->name('user_account_log')->field('id')->order("id desc")->limit(1)->value('id');
        $min_id = 1;
        $page_size = 100;
        $i = floor($min_id / $page_size);
        $batch = ceil($max_id / $page_size);
        flush();
        ob_end_flush();
        for ($i = $batch; $i >= 0; $i--) {
            flush();
            ob_end_flush();
            $start_id = $i * $page_size;
            $end_id = $start_id + $page_size;
            echo "\n ============== no.{$i} , id: {$start_id} - {$end_id} ======================= ";
            $results = $this->db->query("SELECT id FROM user_account_log 
                                                WHERE id > $start_id AND id <= $end_id                                               
                                                ");
            $ids = array();
            foreach ($results as $result) {
                $ids[] = $result['id'];
            }
            if (empty($ids)) {
                continue;
            }

            $ids = implode(",", $ids);
            $logs = $this->db->query("SELECT * FROM  user_account_log  WHERE id IN(" . $ids . ") ORDER BY id desc");
            $data = [];
            foreach ($logs as $v) {
                $year = date("Y", $v['create_time']);
                unset($v['id']);
                $data[$year][] = $v;
            }
            foreach ($data as $year => $params) {
                $this->db->name('user_account_log_' . $year)->insertAll($params);
            }
        }

        echo "done";
    }

    private function _stat()
    {
        for ($dateline = strtotime('2019-04-09'); $dateline <= time(); $dateline = $dateline + 86400) {
            $start_time = $dateline;
            $end_time = $start_time + 86399;
            $date = date("Y-m-d", $dateline);
            echo "$date\n";
            $where_battery_amount = ['type' => 1, 'status' => 2, 'end_time' => ['between', [$start_time, $end_time]]];
            $where_battery_pay_amount = ['type' => 1, 'is_pay' => 2, 'payment_time' => ['between', [$start_time, $end_time]]];
            $where_battery_num = ['type' => 1, 'status' => ['<', 3], 'start_time' => ['between', [$start_time, $end_time]]];
            $where_battery_pay_num = ['type' => 1, 'is_pay' => 1, 'payment_time' => ['between', [$start_time, $end_time]]];
            $where_wired_amount = ['type' => 2, 'status' => ['<', 3], 'start_time' => ['between', [$start_time, $end_time]]];
            $where_wired_num = ['type' => 2, 'status' => ['<', 3], 'start_time' => ['between', [$start_time, $end_time]]];

            //平台统计
            $params = [
                'date' => $date,
                'battery_amount' => (float)$this->db->name('lease_order')->where($where_battery_amount)->sum('amount'),
                'battery_pay_amount' => (float)$this->db->name('lease_order')->where($where_battery_pay_amount)->sum('payment_amount'),
                'battery_num' => (int)$this->db->name('lease_order')->where($where_battery_num)->count(),
                'battery_pay_num' => (int)$this->db->name('lease_order')->where($where_battery_pay_num)->count(),
                'wired_amount' => (float)$this->db->name('lease_order')->where($where_wired_amount)->sum('payment_amount'),
                'wired_num' => (int)$this->db->name('lease_order')->where($where_wired_num)->count(),
            ];
            $params['total_amount'] = bcadd($params['battery_amount'], $params['wired_amount'], 2);
            $params['total_pay_amount'] = bcadd($params['battery_pay_amount'], $params['wired_amount'], 2);
            $params['total_num'] = $params['battery_num'] + $params['wired_num'];
            $params['total_pay_num'] = $params['battery_pay_num'] + $params['wired_num'];
            $sql = sqlInsertUpdate('stat_operator', $params, $params);
            $this->db->execute($sql);

            //商家统计
            $seller = [];
            $query = $this->db->name('lease_order')->field("sum('amount') as battery_amount,sid")->where($where_battery_amount)->group('sid')->select();
            foreach ($query as $v) {
                !isset($seller[$v['sid']]) && $seller[$v['sid']] = [];
                $seller[$v['sid']]['battery_amount'] = $v['battery_amount'];
            }

            $query = $this->db->name('lease_order')->field("sum('payment_amount') as battery_pay_amount,sid")->where($where_battery_pay_amount)->group('sid')->select();
            foreach ($query as $v) {
                !isset($seller[$v['sid']]) && $seller[$v['sid']] = [];
                $seller[$v['sid']]['battery_pay_amount'] = $v['battery_pay_amount'];
            }
            $query = $this->db->name('lease_order')->field("count(*) as battery_num,sid")->where($where_battery_num)->group('sid')->select();
            foreach ($query as $v) {
                !isset($seller[$v['sid']]) && $seller[$v['sid']] = [];
                $seller[$v['sid']]['battery_num'] = $v['battery_num'];
            }
            $query = $this->db->name('lease_order')->field("count(*) as battery_pay_num,sid")->where($where_battery_pay_num)->group('sid')->select();
            foreach ($query as $v) {
                !isset($seller[$v['sid']]) && $seller[$v['sid']] = [];
                $seller[$v['sid']]['battery_pay_num'] = $v['battery_pay_num'];
            }
            $query = $this->db->name('lease_order')->field("sum('payment_amount') as wired_amount,sid")->where($where_wired_amount)->group('sid')->select();
            foreach ($query as $v) {
                !isset($seller[$v['sid']]) && $seller[$v['sid']] = [];
                $seller[$v['sid']]['wired_amount'] = $v['wired_amount'];
            }
            $query = $this->db->name('lease_order')->field("count(*) as wired_num,sid")->where($where_wired_num)->group('sid')->select();
            foreach ($query as $v) {
                !isset($seller[$v['sid']]) && $seller[$v['sid']] = [];
                $seller[$v['sid']]['wired_num'] = $v['wired_num'];
            }
            foreach ($seller as $sid => $v) {
                $v['date'] = $date;
                $v['sid'] = $sid;
                !isset($v['battery_amount']) && $v['battery_amount'] = 0;
                !isset($v['battery_pay_amount']) && $v['battery_pay_amount'] = 0;
                !isset($v['battery_num']) && $v['battery_num'] = 0;
                !isset($v['battery_pay_num']) && $v['battery_pay_num'] = 0;
                !isset($v['wired_amount']) && $v['wired_amount'] = 0;
                !isset($v['wired_num']) && $v['wired_num'] = 0;
                $v['total_amount'] = bcadd($v['battery_amount'], $v['wired_amount'], 2);
                $v['total_pay_amount'] = bcadd($v['battery_pay_amount'], $v['wired_amount'], 2);
                $v['total_num'] = $v['battery_num'] + $v['wired_num'];
                $v['total_pay_num'] = $v['battery_pay_num'] + $v['wired_num'];
                $sql = sqlInsertUpdate('stat_seller', $v, $v);
                $this->db->execute($sql);
            }

            //设备统计
            $device = [];
            $query = $this->db->name('lease_order')->field("sum('amount') as battery_amount,device_id,device_code")->where($where_battery_amount)->group('device_id')->select();
            foreach ($query as $v) {
                !isset($device[$v['device_code']]) && $device[$v['device_code']] = [];
                $device[$v['device_code']]['amount'] = $v['battery_amount'];
            }

            $query = $this->db->name('lease_order')->field("sum('payment_amount') as battery_pay_amount,device_id,device_code")->where($where_battery_pay_amount)->group('device_id')->select();
            foreach ($query as $v) {
                !isset($device[$v['device_code']]) && $device[$v['device_code']] = [];
                $device[$v['device_code']]['pay_amount'] = $v['battery_pay_amount'];
            }

            $query = $this->db->name('lease_order')->field("count(*) as battery_num,device_id,device_code")->where($where_battery_num)->group('device_id')->select();
            foreach ($query as $v) {
                !isset($device[$v['device_code']]) && $device[$v['device_code']] = [];
                $device[$v['device_code']]['create_num'] = $v['battery_num'];
            }
            $query = $this->db->name('lease_order')->field("count(*) as battery_pay_num,device_id,device_code")->where($where_battery_pay_num)->group('device_id')->select();
            foreach ($query as $v) {
                !isset($device[$v['device_code']]) && $device[$v['device_code']] = [];
                $device[$v['device_code']]['settle_num'] = $v['battery_pay_num'];
            }
            foreach ($device as $device_id => $v) {
                $v['date'] = $date;
                $v['device_id'] = $device_id;
                $sql = sqlInsertUpdate('stat_device', $v, $v);
                $this->db->execute($sql);
            }

        }
    }


    function orderDebug()
    {
        set_time_limit(0);
        $table = 'lease_order_201904';
        //echo '<pre>';
        $max_id = $this->db->name($table)->field('id')->order("id desc")->limit(1)->value('id');
        $min_id = 1;
        $page_size = 100;
        $i = floor($min_id / $page_size);
        $batch = ceil($max_id / $page_size);
        flush();
        ob_end_flush();

        for ($i = $batch; $i >= 0; $i--) {
            flush();
            ob_end_flush();
            $start_id = $i * $page_size;
            $end_id = $start_id + $page_size;
            echo "\n ============== no.{$i} , id: {$start_id} - {$end_id} ======================= ";
            $results = $this->db->query("SELECT id FROM {$table} 
                                                WHERE id > $start_id AND id <= $end_id                                                
                                                ");
            $ids = array();
            foreach ($results as $result) {
                $ids[] = $result['id'];
            }
            if (empty($ids)) {
                continue;
            }

            $ids = implode(",", $ids);
            $orders = $this->db->query("SELECT uid,order_no FROM  {$table}   WHERE id IN(" . $ids . ") ORDER BY id desc");


            foreach ($orders as $order) {
                $user_table = getTableNo('order_user', 'hash', 16, $order['uid']);
                $this->db->name($user_table)->where(['order_no'=>$order['order_no']])->update(['uid'=>$order['uid']]);
            }

        }
        echo "done";


    }




}