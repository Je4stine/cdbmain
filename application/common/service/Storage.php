<?php

namespace app\common\service;


use think\Request;
use think\Db;
use think\File;
use think\Session;
use think\Cookie;
use think\Cache;
use think\Model;

/**
 * 缓存
 * @package app\common\service
 */
class Storage extends Model
{
    protected $ePrefix = 'E:';//机柜前缀

    protected $cPrefix = 'C:';//客户端前缀

    protected $lPrefix = 'L:';//锁前缀

    protected $oPrefix = 'O:';//操作指令前缀

    protected $bPrefix = 'B:';//电池前缀


    public $cache;

    public function __construct()
    {
        $this->cache = Cache::store('redis');
    }


    //获取机柜信息
    function getEquipment($equipment_id)
    {
        $info = $this->cache->get($this->ePrefix . $equipment_id);
        return $info;
    }

    //存储机柜信息
    function setEquipment($equipment_id, $data)
    {
        $this->cache->set($this->ePrefix . $equipment_id, $data, 86400 * 3);
    }


    //机柜操作初始
    function refreshEquipmentOpe($equipment_id, $data, $act)
    {
        $data['ope_' . $act] = ['status' => 0, 'hex' => null];
        $this->cache->set($this->ePrefix . $equipment_id, $data,600);
    }

    //加锁
    function addEquipmentLock($equipment_id)
    {
        $this->cache->set($this->lPrefix . $equipment_id, time(), 30);
    }

    //释放锁
    function removeEquipmentLock($equipment_id)
    {
        $this->cache->rm($this->lPrefix . $equipment_id);
    }

    //获取锁
    function getEquipmentLock($equipment_id)
    {
        return $this->cache->get($this->lPrefix . $equipment_id);
    }


    //存储链接id
    function setClient($client_id, $data)
    {
        $this->cache->set($this->cPrefix . $client_id, $data,86400*365);
    }

    //获取链接端信息
    function getClient($client_id)
    {
        return $this->cache->get($this->cPrefix . $client_id);
    }

    //释放链接指令
    function removeClient($client_id)
    {
        $this->cache->rm($this->cPrefix . $client_id);
    }

    //操作指令
    function setOperate($id, $val)
    {
        $this->cache->set($this->oPrefix . $id, $val, 30);
    }

    //获取操作指令
    function getOperate($id)
    {
        return $this->cache->get($this->oPrefix . $id);
    }

    //释放操作指令
    function removeOperate($id)
    {
        $this->cache->rm($this->oPrefix . $id);
    }


    //获取电池信息
    function getBattery($battery_id)
    {
        $info = $this->cache->get($this->bPrefix . $battery_id);
        return $info;
    }

    //设置电池信息
    function setBattery($battery_id, $data)
    {
        $this->cache->set($this->bPrefix . $battery_id, $data, 86400 * 365);
    }


}