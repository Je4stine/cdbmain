<?php

namespace app\common\logic;

use think\Request;

/**
 * 权限
 * @package app\common\logic
 */
class Auth extends Common
{

    var $permissions = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    public function getPermissions()
    {
        $permissions = implode(",", $this->permissions);
        $permissions = explode(",", $permissions);
        foreach ($permissions as $k => $v) {
            $permissions[$k] = $v;
        }
        $permissions = array_unique($permissions);
        $permissions = array_values($permissions);
        return $permissions;
    }

    public function getMemus($groups)
    {
        if (empty($groups)) {
            return [];
        }
        $result = $this->getAllNodes($groups);
        $rule_ids = $result['ids'];
        $ids = $this->db->name('auth_rules')
            ->field('pid')
            ->where([ 'status' => 1,  'id' => ['in', $rule_ids]])
            ->column('pid');

        $ids = array_unique($ids);
        !empty($ids) && $rule_ids = array_merge($rule_ids, $ids);

        $ids = $this->db->name('auth_rules')
            ->field('pid')
            ->where([ 'status' => 1,  'id' => ['in', $rule_ids]])
            ->column('pid');

        $ids = array_unique($ids);
        !empty($ids) && $rule_ids = array_merge($rule_ids, $ids);


        $query = $this->db->name('auth_rules')
            ->where(['is_menu' => 1, 'status' => 1])
            ->order("list_order asc")
            ->select();
        $actions = [];

        foreach ($query as $v) {
            if (!in_array($v['id'], $rule_ids)) {
                continue;
            }
            $v['action'] = trim($v['action'], ",");
            $v['extra'] = trim($v['extra'], ",");
            $data[] = [
                'id' => $v['id'],
                'pid' => $v['pid'],
                'title' => lang($v['title']),
                'icon' => $v['icon'],
                'index' => !empty($v['action']) ? $v['action'] : "/" . $v['id'],
            ];
            !empty($v['action']) && $actions[] = $v['action'];
            !empty($v['extra']) && $actions[] = $v['extra'];
        }
        $results = $this->db->name('auth_rules')
            ->field('action,extra')
            ->where(['id' => ['in', $rule_ids]])
            ->select();
        foreach ($results as $v) {
            !empty($v['action']) && $actions[] = $v['action'];
            !empty($v['extra']) && $actions[] = $v['extra'];
        }

        $this->permissions = $actions;
        $menus = self::toTree($data, 0, true);
        return $menus;
    }

    public function getAllNodes($ids)
    {
        $relus_ids = $this->_getRules($ids);
        $query = $this->db->name('auth_rules')
            ->where(['status' => 1])
            ->order("list_order asc")
            ->select();
        foreach ($query as $v) {
            $data[] = [
                'id' => $v['id'],
                'pid' => $v['pid'],
                'label' => lang($v['title']),
            ];
        }

        $nodes = self::toTree($data, 0, true);
        return ['nodes' => $nodes, 'ids' => $relus_ids];
    }

    private function _getRules($ids)
    {
        $ids = explode(",", $ids);
        if (in_array(1, $ids)) { //超管
            $groups = $this->db->name('auth_rules')->where(['status' => 1])->column('id');
        } else {
            $groups = $this->db->name('auth_groups')->where(['id' => ['IN', $ids], 'not_delete' => 1])->column('rules');
        }
        $groups = implode(",", $groups);
        $groups = empty($groups) ? [] : explode(",", $groups);
        return $groups;
    }

    /**
     * 权限树格式化
     */
    private static function toTree($data = null, $pid = 0, $reset = false)
    {
        static $node = [];
        if (empty($data) || !is_array($data)) {
            return [];
        }
        if ($reset) {
            $node = [];
        }
        //父节点
        if ($node === []) {
            foreach ($data as $item) {
                if ($item['pid'] == $item['id']) {
                    //避免死循环
                    continue;
                }
                if (isset($node[$item['pid']])) {
                    $node[$item['pid']][] = $item;
                } else {
                    $node[$item['pid']] = [$item];
                }
            }
        }

        $tree = [];
        foreach ($data as $item) {
            if ($pid == $item['pid']) {
                if (isset($node[$item['id']])) {
                    $item['children'] = self::toTree($node[$item['id']], $item['id']);
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }

    public function checkPermissions($uid)
    {
        $controller = lcfirst(Request::instance()->controller());
        $action = lcfirst(Request::instance()->action());
        if (in_array($controller, ['auth', 'user'])) {
            return true;
        }
        $permissions = cache("admin-permissions:{$this->oid}-{$uid}" );
        if(!$permissions){
            json(['code' => 401, 'msg' => lang('请先登录')])->send();
            exit;
        }
        if (in_array("/{$controller}/{$action}", $permissions)) {
            return true;
        }
        json(['code' => 301, 'msg' => lang('没有操作权限')])->send();
        exit;
    }

}