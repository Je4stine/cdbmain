<?php

namespace app\common\logic;

use app\common\logic\Common;
use think\Request;
use think\Db;
use think\File;
use think\Session;
use think\Cookie;


/**
 * 电池日志
 * @package app\common\logic
 */
class BatteryLog extends Common
{
    public $storage;//缓存介质

    public function _initialize()
    {
        parent::_initialize();
        $this->storage = \think\Loader::model('Storage', 'service');
    }

    function deviceLog($device_id, $battery_id)
    {
        $cache = $this->storage->getBattery($battery_id);
        if ($cache && isset($cache['device_id']) && $cache['device_id'] == $device_id) {
            return;
        }
        $cache = [
            'device_id' => $device_id,
        ];
        $this->storage->setBattery($battery_id, $cache);
        $params = [
            'device_id' => $device_id,
            'battery_id' => $battery_id,
            'create_time' => time()
        ];
        $this->db->name('battery_log')->insert($params);
    }

    function leaseLog($device_id, $battery_id, $uid, $order_no)
    {
        $cache = [
            'device_id' => '-1',
        ];
        $this->storage->setBattery($battery_id, $cache);
        $params = [
            'device_id' => $device_id,
            'battery_id' => $battery_id,
            'user_type' => 99,//用户
            'relation_id' => $uid,
            'memo' => lang('订单号').":{$order_no}",
            'create_time' => time()
        ];
        $this->db->name('battery_log')->insert($params);
    }

    function openLog($device_id, $battery_id, $lock_id)
    {
        $cache = [
            'device_id' => '-1',
        ];
        $this->storage->setBattery($battery_id, $cache);

        $key = $device_id . "_" . $lock_id;
        $cache = $this->storage->getOperate($key);
        if (!$cache) {
            return;
        }

        $params = [
            'device_id' => $device_id,
            'battery_id' => $battery_id,
            'user_type' => $cache['user_type'],
            'relation_id' => $cache['uid'],
            'memo' => $cache['memo'],
            'create_time' => time()
        ];
        $this->db->name('battery_log')->insert($params);
        $this->storage->removeOperate($key);
    }

}