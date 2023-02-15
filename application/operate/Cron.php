<?php

namespace app\operate;

use think\Cache;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Db;
use think\Loader;

/**
 * 脚本
 * @package app\operate
 */
class Cron extends Command
{
    protected $db;

    protected $code;

    protected function configure()
    {
        $this->code = config('ocode');
        $this->addArgument('name', Argument::REQUIRED); //必传参数
        $this->addOption('message', 'm', Option::VALUE_REQUIRED); //选项值必填
        $this->setName('cron')->setDescription('crontab');
    }

    protected function execute(Input $input, Output $output)
    {
        set_time_limit(0);
        $database = config('database');
        $this->db = Db::connect($database);

        $args = $input->getArguments();
        //$output->writeln('The args value is:');
        $action = isset($args['name']) ? $args['name'] : 'cal';
        switch ($action) {
            case  'cal':
                $this->cal();
                break;
            case  'heart':
                $this->heart();
                break;
            case  'table':
                $this->table();
                break;
            case  'screen':
                $this->screenPlan();
                break;
                break;
            case  'stat':
                $this->stat();
                break;
            case  'lateOrder':
                $this->lateOrder();
                break;
            case  'test':
                $this->scanError();
                break;
            case  'pay_order':
                $this->payOrder();
                break;
            case 'deleteData':
                $this->deleteData();
                break;

        }
    }

    protected function scanError()
    {
        $list = $this->db->name('lease_order')->where(['is_pay' => 1, 'create_time' => ['>', '1596211200']])
            ->order('id DESC')->select();

        foreach ($list as $k => $item) {
            $user_table = getTableNo('order_user', 'hash', 16, $item['uid']);

            $this->db->name($user_table)->where(['order_no' => $item['order_no']])
                ->update(['status' => 2, 'is_pay' => 1]);
            $this->db->name('lease_order_202008')->where(['order_no' => $item['order_no']])
                ->update(['status' => 2, 'is_pay' => 1]);
            $this->db->name('order_lose')->where(['order_no' => $item['order_no']])
                ->update(['status' => 2, 'is_pay' => 1]);
            $this->db->name('order_active')->where(['order_no' => $item['order_no']])
                ->delete();
            echo $k . ' - ';
        }
    }


    protected function table()
    {
        for ($i = 1; $i <= 3; $i++) {
            $month = date("Ym",strtotime(date("Y-m-01")." +{$i} month"));
            $year = date("Y",strtotime(date("Y")." +{$i} year"));

            $this->_createTable('order_brokerage_month', "order_brokerage_{$month}");
            $this->_createTable('lease_order_month', "lease_order_{$month}");
            $this->_createTable('order_agency', "order_agency_{$month}");
            $this->_createTable('trade_log', "trade_log_{$year}");
            $this->_createTable('user_account_log', "user_account_log_{$year}");
            echo "\n $month";
        }
        echo 'success';
    }

    private function _createTable($table, $name)
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

    protected function cal()
    {
        set_time_limit(0);

        //3天前订单，结算
        $time = strtotime(date("Y-m-d", time() - 86400 * 3));
        $db = $this->db;

        //        for ($dateline = time() + 86400; $dateline <= time() + 86400 * 7; $dateline = $dateline + 86400) {
        //            $start = $dateline;
        //            $params = [
        //                'date' => date("Y-m-d", $start),
        //            ];
        //            $sql = sqlInsertUpdate('stat_operator', $params, $params);
        //            $db->execute($sql);
        //        }

        //获取当天结算的总数据
        $query = $db->name('order_brokerage')
            ->where(['create_time' => ['<', $time]])
            ->where('status = 1')
            ->field("relation_id ")
            ->group('relation_id')
            ->select();


        foreach ($query as $brokerage) {
            $tmp = $db->name('order_brokerage')
                ->field('id,create_time,amount,order_no')
                ->where(['create_time' => ['<', $time]])
                ->where('status = 1')
                ->where(['relation_id' => $brokerage['relation_id']])
                ->select();
            $this->db->startTrans();
            try {
                $brokerage['amount'] = 0;

                $month = [];
                foreach ($tmp as $v) {
                    $brokerage['amount'] = bcadd($brokerage['amount'], $v['amount'], 2);
                    $month[] = date("Ym", $v['create_time']);
                }
                $brokerage['amount'] = priceFormat($brokerage['amount']);
                //账户余额
                $db->name('account')
                    ->where(['relation_id' => $brokerage['relation_id']])
                    ->update([
                        'total_amount' => ['exp', 'total_amount+' . $brokerage['amount']],
                        'balance' => ['exp', 'balance+' . $brokerage['amount']]
                    ]);

                $db->name('order_brokerage')
                    ->where(['create_time' => ['<', $time]])
                    ->where('status = 1')
                    ->where(['relation_id' => $brokerage['relation_id']])
                    ->update(['status' => 2, 'settlement_time' => time()]);

                foreach ($month as $v) {
                    $db->name('order_brokerage_' . $v)
                        ->where(['create_time' => ['<', $time]])
                        ->where('status = 1')
                        ->where(['relation_id' => $brokerage['relation_id']])
                        ->update(['status' => 2, 'settlement_time' => time()]);
                }
                $this->db->commit();
            } catch (\Exception $e) {
                $this->db->rollback();
                save_log('cal_error', "【{$brokerage['relation_id']}】" . $e->getMessage());
                continue;
            }
            $stat_text = [];
            if ($brokerage['amount'] > 0) {
                $stat_text[] = statSql('stat_operator', ['date' => date("Y-m-d")], ['brokerage_settle' => $brokerage['amount']]);
                $stat_text[] = statSql('stat_agency', ['date' => date("Y-m-d"), 'agency_id' => $brokerage['relation_id']], ['brokerage_settle' => $brokerage['amount']]);
                statText('jcc', $stat_text);
            }
        }
        echo "done";
        //$ret = $this->_curl('stat');
    }

    /**
     * 删除数据
     */
    private function deleteData()
    {
        $day2 = time() - 86400 * 2;
        $this->db->name('battery_order')->where(['not_delete' => 0])->delete();//租借充电宝
        $this->db->name('order_active')->where(['status' => 2, 'is_pay' => 1])->delete();//为完成订单
        $this->db->name('order_brokerage')->where(['status' => 2])->delete();//已完成的分成

        //OPTIMIZE TABLE `pay_auth_log`
        echo "done";
    }


    protected function heart()
    {
        $redis = Cache::store('redis')->handler();
        $length = $redis->llen('heart:'.config('ocode'));
        $list = [];
        for ($i = 0; $i < $length; $i++) {
            $msg = $redis->lPop('heart:'.config('ocode'));
            $msg && $list[] = $msg;
        }
        echo "\n" . date("Y-m-d H:i:s");
        if (empty($list)) {
            return;
        }
        $database = config('database');
        $db = Db::connect($database);
        $list = array_unique($list);
        $total = count($list);
        $size = 100;
        $range = ceil($total / $size);
        for ($i = 0; $i < $range; $i++) {
            $data = array_splice($list, 0, $size);
            $db->name('charecabinet')
                ->where(['cabinet_id' => ['in', $data]])
                ->update(['heart_time' => time(), 'is_online' => 1]);
        }
        echo config('ocode')." done " . $total;
    }


    private function _curl($url, $data = [])
    {
        $this->code = config('ocode');
        $host = 'http://api.local.com/cloud/Api/';
        $url = $host.$url;
        $curl = curl_init();
        $time = time();
        $headerkey = config('headerkey');
        $header = array(
            'key:' . md5($headerkey . $time),
            'time:' . $time,
        );
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1000);
        curl_setopt($curl, CURLOPT_TIMEOUT, 1000);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($curl, CURLOPT_POST, 1);
        if (!empty($data)) { //post方式
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        try {
            $output = curl_exec($curl);
            if (curl_errno($curl)) {
                echo curl_error($curl);
            }
            curl_close($curl);
            $json = json_decode($output, true);
            return $json;
        } catch (\Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }


    /**
     * @throws \think\Exception
     * 广告推送
     */
    protected function screenPlan()
    {
        $redis = Cache::store('redis')->handler();
        $length = $redis->llen('screen:'.config('ocode'));
        $list = [];
        for ($i = 0; $i < $length; $i++) {
            $msg = $redis->lPop('screen:'.config('ocode'));
            $msg && $list[] = $msg;
        }
        echo "\n".date("Y-m-d H:i:s");
        if (empty($list)) {
            return;
        }
        $database = config('database');
        $list = array_unique($list);
        $db = Db::connect($database);
        //机柜投放
        $aids = $db->name('ad_plan_charecabinet')
            ->where(['plan_id'=>['IN',$list], 'not_delete'=>1 ])
            ->column('data_id');
        $query = $db->name('charecabinet')
            ->where(['not_delete'=>1, 'cabinet_id'=>['IN',$aids] ])
            ->column('cabinet_id');
        foreach($query as $equipment_id){
            $this->_screenCommand(['command'=>'refresh', 'equipment_id'=>$equipment_id]);
        }
    }

    /**
     * 发送指令
     * @param $data
     * @return bool
     */
    private function _screenCommand($data)
    {
        $server_url = config('tcpintraneturl');
        $client = stream_socket_client($server_url);
        if (!$client) {
            return false;
        }
        $time = time();
        $params = [
            'p1' => $data['command'],//指令
            'p2' => $time,
            'p3' => md5(config('headerkey') . $time),
            'p4' => 'screen-'.$data['equipment_id'],
            'oCode' => config('ocode'),
        ];
        isset($data['url']) && $params['url'] = $data['url'];
        isset($data['version']) && $params['aims'] = $data['version'];
        $command = 'CMD:' . json_encode($params);
        fwrite($client, $command);
        return true;
    }


    protected function stat()
    {
        set_time_limit(600);
        $code = config('ocode');
        echo "\n{$code} ";
        $lock = cache("stat_lock_" . $this->code);
        if ($lock) {
            echo 'lock';
            return;
        }

        cache("stat_lock_" . $this->code, time(), 30);
        $folder = LOG_PATH . "stat/{$this->code}/";
        if (!is_dir($folder)) {
            cache("stat_lock_" . $this->code, null);
            echo 'none';
            return;
        }
        $db = $this->db;
        $list = scandir($folder);

        $files = [];
        foreach ($list as $v) {
            if (pathinfo($v, PATHINFO_EXTENSION) != 'txt') {
                continue;
            }
            $file = $folder . $v;
            $files[] = $file;
            $contents = file_get_contents($file);
            $contents = explode("\n", $contents);
            $db->startTrans();
            try {
                $caches = [];
                echo "\n-----------------------------------------------\n";
                foreach ($contents as $content) {
                    $content = json_decode($content, true);

                    $hash = "stat_cache_fhd:" . md5($this->code . ":" . $content['table'] . ":" . json_encode($content['condition']));
                    $table_cache = cache($hash);
                    $data = [];
                    foreach ($content['params'] as $field => $val) {
                        !isset($data[$field]) && $data[$field] = 0;
                        $data[$field] = bcadd($data[$field], $val, 2);
                        $data[$field] = priceFormat($data[$field]);
                    }

                    if ($table_cache) { //表数据存在，则更新
                        $params = [];
                        foreach ($data as $field => $val) {
                            $params[$field] = ['exp', $field . '+' . $val];
                        }
                        //print_r($content['condition']);
                        $db->name($content['table'])->where($content['condition'])->update($params);
                        //echo $db->name($content['table'])->getLastsql() . "\n";
                    } else { //新纪录插入

                        $sql = addNumSql($content['table'], $content['condition'], $data);
                        //echo $sql . "\n";
                        $db->execute($sql);
                        $caches[] = $hash;
                    }
                }
                $db->commit();
                @unlink($file);
                foreach ($caches as $hash) {
                    cache($hash, 1, 86400);
                }
            } catch (\Exception $e) {
                save_log('sql', lang('统计出错') . $e->getMessage());
                $db->rollback();
            }
        }
        cache("stat_lock_" . $this->code, null);
        echo " done";
        return;
    }

    protected function lateOrder()
    {
        $query = $this->db->name('order_active')
            ->field('order_no,start_time')
            ->where(['status' => 1, 'is_late' => 0, 'type' => 1, 'expire_time' => ['<', time()]])
            ->select();

        foreach ($query as $v) {
            $this->db->name("order_agency_" . date("Ym", $v['start_time']))
                ->where(['order_no' => $v['order_no']])
                ->update(['is_late' => 1]);
            $this->db->name('order_active')->where(['order_no' => $v['order_no']])->update(['is_late' => 1]);
        }
        echo 'done';
    }

    protected function payOrder()
    {
        $time = time() - 300;
        $table = getTableNo('lease_order', 'date', date("Ym"));
        $order_data = $this->db->name($table)
            ->field('id,order_no,uid,amount,order_no,end_time')
            ->where(['end_time' => ['lt',$time], 'is_pay' => 0, 'amount' => ['>', 0]])
            ->select();
        foreach ( $order_data as $order ) {
            $ret = $this->_curl('payOrder', ['id'=>$order['id']]);
            print_r($ret);
        }
        echo '任务已完成。';
    }
}
