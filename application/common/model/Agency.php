<?php

namespace app\common\model;

use think\Exception;
use think\Model;
use app\common\service\Base;

/**
 * 代理商
 * @package app\common\model
 */
class Agency extends Base
{

    /**
     * 顶级代理
     * @return bool|int|string
     */
    public function topAgency()
    {
        $query = $this->db->name('agency')->where(['not_delete' => 1, 'parent_id' => 0, 'type' => 1])
            ->order('id', 'desc')
            ->select();
        $query && $query = collection($query)->toArray();
        return $query;
    }

    /**
     * 运营商所有代理
     */
    public function allAgency()
    {
        $query = $this->db->name('agency')->where(['not_delete' => 1, 'type' => 1])
            ->order('id', 'desc')
            ->select();
        $query && $query = collection($query)->toArray();
        return $query;
    }


    /**
     * 代理树状列表
     * @param type $type
     * @return type
     */
    public function listAgency()
    {
        $data = [];
        $query = $this->db->name('agency')->where(['not_delete' => 1, 'type' => 1])->order('id', 'desc')->select();
        $query && $query = collection($query)->toArray();
        foreach ($query as $v) {
            $data[$v['id']] = $v;
        }

        $tree = self::tree($data);
        return array_values($tree);
    }

    public function agencyTree()
    {
        $arr = $this->listAgency();
        return self::getOptions($arr);
    }


    static public function getOptions($arr, $level = 0)
    {
        $data = [];
        foreach ($arr as $v) {
            $flag = $level > 0 ? '|-' : '';
            $level > 0 && $flag = str_repeat("&nbsp;", $level * 4) . $flag;
            $data[] = ['id' => $v['id'], 'name' => $flag . $v['name']];  //"<option value='{$v['id']}'>$flag {$v['name']}</option>";
            if (isset($v['sub'])) {
                $subs = self::getOptions($v['sub'], $level + 1);
                foreach ($subs as $s) {
                    $data[] = $s;
                }
            }
        }
        return $data;
    }


    /**
     * 树状结构
     * @param type $arr
     * @param type $pid
     * @return type
     */
    static public function tree($arr, $pid = 0)
    {
        $data = [];
        foreach ($arr as $k => $v) {
            if ($v['parent_id'] == $pid) {   //  0 0  0
                $data[$v['id']] = ['id' => $v['id'], 'name' => $v['name']];
                $sub = self::tree($arr, $v['id']);
                $sub && $data[$v['id']]['sub'] = array_values($sub);
            }
        }
        return $data;
    }


    /**
     * 获取所有父代理
     * @param type $list
     * @param type $cid
     * @return type
     */
    static function getParents($list, $cid)
    {
        $tree = array();
        foreach ($list as $item) {
            if ($item['id'] == $cid) {
                if ($item['parent_id'] > 0) {
                    $tree = array_merge($tree, self::getParents($list, $item['parent_id']));
                }
                $tree[] = ['id' => $item['id'], 'name' => $item['name'], 'type' => $item['type'], 'avatar' => $item['avatar']];
                break;
            }
        }
        return $tree;
    }


    /**
     * 获取所有子代理
     * @param type $list
     * @param type $cid
     * @param type $level
     * @return type
     */
    static function getSubs($list, $cid = 0, $level = 1)
    {
        $subs = array();
        foreach ($list as $item) {
            if ($item['parent_id'] == $cid) {
                $item['level'] = $level;
                $subs[] = ['id' => $item['id'], 'name' => $item['name']];
                $subs = array_merge($subs, self::getSubs($list, $item['id'], $level + 1));
            }
        }
        return $subs;
    }

}