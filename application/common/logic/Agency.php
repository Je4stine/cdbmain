<?php

namespace app\common\logic;

use think\Request;

/**
 * 代理商
 * @package app\common\logic
 */
class Agency extends Common
{

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 添加
     */
    public function agencyAdd($data = [])
    {

        $params = $this->_getParams($data);
        $validate = \think\Loader::validate('Agency');
        if (!$validate->check($params)) {
            return ['code' => 0, 'msg' => $validate->getError()];
        }
        if (!empty($params['parent_id'])) {
            $parents = $this->getParentIds($params['parent_id']);
            if (count($parents) >= 10) {
                return ['code' => 0, 'msg' => lang('最多十级代理')];
            }
            $params['parents'] = implode(",", $parents);
        }
        $params['password'] = md123($params['password']);
        $params['create_time'] = time();
        unset($params['id']);

        $this->db->startTrans();
        try {
            $id = $this->db->name('agency')->insertGetId($params);
            $this->db->name('account')->insert(
                ['user_type' => config('user_type.agency'), 'relation_id' => $id, 'create_time' => time()]
            );
            $this->operateLog($id, '添加代理商');
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            save_log('sql', $e->getMessage());
            return ['code' => 0, 'msg' => lang('操作失败')];
        }
        return ['code' => 1, 'msg' => lang('操作成功')];
    }

    private function _getParams($data = [])
    {
        $params = [
            'id' => input('post.id', 0, 'intval'),
            'type' => 1,
            'name' => input('post.name'),
            'phone' => input('post.phone'),
            'password' => input('post.password'),
            'parent_id' => input('post.parent_id', 0, 'intval'),
            'brokerage' => input('post.brokerage', 0, 'intval'),
            'open_lock' => input('post.open_lock', 0, 'intval'),
            'is_self' => input('post.is_self', 0, 'intval'),
            'status' => input('post.status', 0, 'intval'),
        ];
        $params['parent_id'] < 1 && $params['parent_id'] = 0;
        return array_merge($params, $data);

    }

    /**
     * 获取上级id集合
     * @param $parent_id
     * @return array
     */
    public function getParentIds($parent_id)
    {
        $query = $this->db->name('agency')
            ->field('id,parents')
            ->where(['id' => $parent_id, 'not_delete' => 1])
            ->find();
        if (!$query) {
            return [];
        }
        $parents = [];
        !empty($query['parents']) && $parents = explode(",", $query['parents']);
        array_push($parents, $parent_id);
        return $parents;
    }

    /**
     * 修改
     */
    public function agencyEdit($info, $data = [])
    {
        if (!$info) {
            return ['code' => 0, 'msg' => lang('信息不存在')];
        }

        $params = $this->_getParams($data);

        $validate = \think\Loader::validate('Agency');
        if (!$validate->scene('edit')->check($params)) {
            return ['code' => 0, 'msg' =>   $validate->getError()];
        }

        if ('' != $params['password']) {
            $params['password'] = Request::instance()->post('password', '', 'Godok\Org\Filter::password');
            if (empty($params['password'])) {
                return ['code' => 0, 'msg' => lang("密码至少6位数！")];
            }
            $params['password'] = md123($params['password']);
        } else {
            unset($params['password']);
        }
        if (!empty($params['parent_id'])) {
            $parents = $this->getParentIds($params['parent_id']);
            if (count($parents) >= 10) {
                return ['code' => 0, 'msg' => lang('最多十级代理')];
            }
            $params['parents'] = implode(",", $parents);
        } else {
            $params['parents'] = NULL;
        }
        $params['update_time'] = time();
        $params['id'] = $info['id'];

        $parents = empty($params['parents']) ? $info['id'] : $params['parents'] . "," . $info['id'];
        $subs = $this->db->name('agency')
            ->where(['not_delete' => 1])
            ->where('parents', 'exp', "	REGEXP '[[:<:]]" . $info['id'] . "[[:>:]]'")
            ->column('id,parents', 'id');

        foreach ($subs as $k => $v) {
            $arr = explode(",", $v);
            $key = array_search($info['id'], $arr);
            $tmp = array_slice($arr, $key + 1);
            $subs[$k] = $parents . "," . implode(",", $tmp);
            $subs[$k] = trim($subs[$k], ",");
        }
        $old_parents = $this->getParentIds($info['parent_id']);//原来所有上级

        // 启动事务
        $this->db->startTrans();
        try {
            $this->db->name('agency')->where(['id' => $info['id']])->update($params);
            foreach ($subs as $sub_id => $p) {
                $this->db->name('agency')->where(['id' => $sub_id])->update(['parents' => $p, 'update_time' => time()]);
            }
            //删除原有上级的设备，增加新上级设备
            $old_parent = intval($info['parent_id']);
            $parent_id = intval($params['parent_id']);
            $devices = $wireds = [];
            $query = $this->db->name('device_agency')->field('device_code,type')->where(['agency_id' => $info['id']])->select();
            foreach ($query as $v) {
                if (1 == $v['type']) {
                    $devices[] = $v['device_code'];
                } else {
                    $wireds[] = $v['device_code'];
                }
            }

            $device_agency = [];
            if ($old_parent !== $parent_id && !empty($query)) {
                if (!empty($old_parents)) {
                    $devices && $this->db->name('device_agency')->where(['device_code' => ["IN", $devices], 'agency_id' => ["IN", $old_parents], 'type' => 1])->delete();
                    $wireds && $this->db->name('device_agency')->where(['device_code' => ["IN", $wireds], 'agency_id' => ["IN", $old_parents], 'type' => 2])->delete();
                }
                $parents = empty($params['parents']) ? [] : explode(",", $params['parents']);
                foreach ($query as $device) {
                    foreach ($parents as $aid) {
                        $device_agency[] = [
                            'agency_id' => $aid,
                            'device_code' => $device['device_code'],
                            'type' => $device['type'],
                            'is_self' => 0
                        ];

                        if (100 <= count($device_agency)) {//100条数据插入一次
                            $this->db->name('device_agency')->insertAll($device_agency);
                            $device_agency = [];
                        }
                    }
                }
                $device_agency && $this->db->name('device_agency')->insertAll($device_agency);
            }

            $this->operateLog($info['id'], '修改代理商');
            ($params['status'] != $info['status']) && $this->offline($info['id']);
            $this->db->commit();
        } catch (\Exception $e) {
            save_log('sql', $e->getMessage());
            $this->db->rollback();
            return ['code' => 0, 'msg' => lang('操作失败')];
        }
        return ['code' => 1, 'msg' => lang('操作成功')];
    }

    public function offline($id)
    {
        $token = cache("merchant-login:{$this->oid}-{$id}");
        if ($token) {
            cache("merchant-token:{$this->oid}-{$token}", null);
        }
    }

    /**
     * 删除
     * @param $id 商户id
     * @param array $condition 查找条件
     * @return array|void
     */
    public function agencyDelete($id, $condition = [])
    {
        $where = ['id' => $id, 'not_delete' => 1, 'type' => 1];
        $where = array_merge($where, $condition);
        $info = $this->db->name('agency')->where($where)->find();
        if (!$info) {
            return ['code' => 0, 'msg' => lang('信息不存在')];
        }

        $check = $this->deleteAccount($id);
        if (1 != $check['code']) {
            return ['code' => 0, 'msg' => lang($check['msg'])];
        }
        if (1 == $info['is_self']) {//购机代理
            $sub = $this->db->name('Agency')->where(['parent_id' => $id, 'not_delete' => 1, 'type' => 1])->count();
            if ($sub > 0) {
                return ['code' => 0, 'msg' => lang('代理商存在').$sub.lang('个下级代理，请先删除')];
            }
            $sub = $this->db->name('Agency')->where(['parent_id' => $id, 'not_delete' => 1, 'type' => 2])->count();
            if ($sub > 0) {
               return ['code' => 0,  'msg' => lang('代理商存在').$sub.lang('个业务员，请先删除')];
                
            }
            $sub = $this->db->name('Agency')->where(['parent_id' => $id, 'not_delete' => 1, 'type' => 3])->count();
            if ($sub > 0) {
               return ['code' => 0,  'msg' => lang('代理商存在').$sub.lang('个店铺管理员，请先删除')];
            }

        } else {//投放代理下级数据归属上级
            $parent_id = $info['parent_id'];
            $parents = empty($info['parents']) ? '' : $info['parents'];
            $subs = $this->db->name('agency')
                ->where(['not_delete' => 1])
                ->where('parents', 'exp', "	REGEXP '[[:<:]]" . $info['id'] . "[[:>:]]'")
                ->column('id,parents,parent_id', 'id');
            foreach ($subs as $k => $v) {
                $arr = explode(",", $v['parents']);
                $key = array_search($info['id'], $arr);
                $tmp = array_slice($arr, $key + 1);
                $subs[$k]['parents'] = $parents . "," . implode(",", $tmp);
                $subs[$k]['parents'] = trim($subs[$k]['parents'], ",");
            }
        }


        // 启动事务
        $this->db->startTrans();
        try {
            if (1 == $info['is_self']) {//购机代理,数据回收系统
                $parent_id = 0;
            } else {
                foreach ($subs as $sub_id => $v) {
                    if ($v['parent_id'] == $info['id']) {
                        $this->db->name('agency')->where(['id' => $sub_id])->update(['parent_id' => $parent_id, 'parents' => $v['parents'], 'update_time' => time()]);
                    } else {
                        $this->db->name('agency')->where(['id' => $sub_id])->update(['parents' => $v['parents'], 'update_time' => time()]);
                    }
                }
            }
            //商户
            $this->db->name('seller')->where(['agency_id' => $id])->update(['agency_id' => $parent_id, 'update_time' => time()]);
            //设备
            $this->db->name('charecabinet')->where(['agency_id' => $id, 'not_delete' => 1])->update(['agency_id' => $parent_id, 'update_time' => time()]);
            $this->db->name('wired_device')->where(['agency_id' => $id, 'not_delete' => 1])->update(['agency_id' => $parent_id, 'update_time' => time()]);
            //设备归属上级
            if (empty($info['is_self'])) {
                $devices = $wireds = [];
                $query = $this->db->name('device_agency')->field('device_code,type')->where(['agency_id' => $id, 'is_self' => 1])->select();
                foreach ($query as $v) {
                    if (1 == $v['type']) {
                        $devices[] = $v['device_code'];
                    } else {
                        $wireds[] = $v['device_code'];
                    }
                }
                $devices && $this->db->name('device_agency')->where(['device_code' => ["IN", $devices], 'agency_id' => $parent_id, 'type' => 1])->update(['is_self' => 1]);
                $wireds && $this->db->name('device_agency')->where(['device_code' => ["IN", $wireds], 'agency_id' => $parent_id, 'type' => 2])->update(['is_self' => 1]);
            }
            $this->db->name('device_agency')->where(['agency_id' => $id])->delete();
            $this->db->name('agency')->update(['id' => $id, 'not_delete' => 0, 'update_time' => time()]);
            $this->operateLog($id, '删除代理商');
            // 提交事务
            $this->db->commit();
        } catch (\Exception $e) {
            save_log('sql', $e->getMessage());
            $this->db->rollback();
            return ['code' => 0, 'msg' => lang('删除失败')];
        }
        $this->offline($id);
        return ['code' => 1, 'msg' => lang('删除成功')];
    }

    public function deleteAccount($id)
    {
        $account = $this->db->name('account')->where(['relation_id' => $id])->find();
        if ($account['balance'] > 0) {
            return ['code' => 0, 'msg' => lang('账户尚有余额，暂不能删除，请先禁用')];
        }
        if ($account['freeze_amount'] > 0) {
            return ['code' => 0, 'msg' => lang('账户尚有提现未处理，暂不能删除，请先禁用')];
        }
        //审核资金
        $account['audit_amount'] = (float)$this->db->name("order_brokerage")
            ->where(['relation_id' => $id, 'status' => 1])
            ->sum('amount');


        if ($account['audit_amount'] > 0) {
            return ['code' => 0, 'msg' => lang('账户尚有待结算订单，暂不能删除，请先禁用')];
        }

        $this->operateLog($id, '删除代理商账户');
        return ['code' => 1, 'msg' => lang('操作成功')];
    }

    /**
     * 代理商端删除
     * @param $id 商户id
     * @param array $condition 查找条件
     * @return array|void
     */
    public function appAgencyDelete($id, $condition = [])
    {
        $where = ['id' => $id, 'not_delete' => 1, 'type' => 1];
        $where = array_merge($where, $condition);
        $info = $this->db->name('agency')->where($where)->find();
        if (!$info) {
           return ['code' => 0,  'msg' => lang('信息不存在')];
        }

        if (1 != $info['is_self']) {//投放代理
            $check = \think\Loader::model('Agency', 'logic')->deleteAccount($id);
            if (1 != $check['code']) {
               return ['code' => 0,  'msg' => $check['msg']];
            }
            $parents = empty($info['parents']) ? '' : $info['parents'];
        } else {
            $parents = $info['id'];
        }


        $subs = $this->db->name('agency')
            ->where(['not_delete' => 1])
            ->where('parents', 'exp', "	REGEXP '[[:<:]]" . $info['id'] . "[[:>:]]'")
            ->column('id,parents,parent_id', 'id');
        foreach ($subs as $k => $v) {
            $arr = explode(",", $v['parents']);
            $key = array_search($info['id'], $arr);
            $tmp = array_slice($arr, $key + 1);
            $subs[$k]['parents'] = $parents . "," . implode(",", $tmp);
            $subs[$k]['parents'] = trim($subs[$k]['parents'], ",");
        }


        // 启动事务
        $this->db->startTrans();
        try {
            if (1 == $info['is_self']) {//购机代理,数据完全独立
                $this->db->name('agency')->update(['id' => $id, 'parent_id' => 0, 'parents' => '', 'update_time' => time()]);
                $parent_id = $id;
            } else {//投放代理，下级数据回收上级
                $parent_id = $info['parent_id'];
                $this->db->name('agency')->update(['id' => $id, 'not_delete' => 0, 'update_time' => time()]);
                //商户
                $this->db->name('seller')->where(['agency_id' => $id])->update(['agency_id' => $parent_id, 'update_time' => time()]);
                //设备
                $this->db->name('charecabinet')->where(['agency_id' => $id, 'not_delete' => 1])->update(['agency_id' => $parent_id, 'update_time' => time()]);
                $this->db->name('wired_device')->where(['agency_id' => $id, 'not_delete' => 1])->update(['agency_id' => $parent_id, 'update_time' => time()]);
            }
            foreach ($subs as $sub_id => $v) {
                if ($v['parent_id'] == $info['id']) {
                    $this->db->name('agency')->where(['id' => $sub_id])->update(['parent_id' => $parent_id, 'parents' => $v['parents'], 'update_time' => time()]);
                } else {
                    $this->db->name('agency')->where(['id' => $sub_id])->update(['parents' => $v['parents'], 'update_time' => time()]);
                }
            }
            $this->operateLog($id, '删除代理商');
            // 提交事务
            $this->db->commit();
        } catch (\Exception $e) {
            save_log('sql', $e->getMessage());
            $this->db->rollback();
            return ['code' => 0, 'msg' => lang('操作失败')];
        }
        $this->offline($id);
        return ['code' => 1, 'msg' => lang('操作成功')];
    }

    /**
     * 获取上级代理id集合
     * @param $parent_id
     * @return array
     */
    public function parentAgencyIds($parent_id)
    {
        $query = $this->db->name('agency')
            ->field('id,parents')
            ->where(['id' => $parent_id, 'not_delete' => 1, 'type' => 1])
            ->find();
        if (!$query) {
            return [];
        }
        $parents = [];
        !empty($query['parents']) && $parents = explode(",", $query['parents']);
        array_push($parents, $parent_id);
        return $parents;
    }

    /**
     * 获取所有上级代理商分成比
     * @param $parent_id
     * @param array $data
     * @return array
     */
    public function getAgencyBrokerage($parent_id, $data = [])
    {
        $query = $this->db->name('agency')
            ->field('id,name,parent_id,brokerage')
            ->where(['id' => $parent_id, 'not_delete' => 1, 'type' => 1])
            ->find();

        if (!$query) {
            return $data;
        }
        $parent_id = $query['parent_id'];
        $info = ['id' => $query['id'], 'name' => $query['name'], 'brokerage' => $query['brokerage']];
        array_push($data, $info);

        return $this->getAgencyBrokerage($parent_id, $data);
    }

    /**
     * 列表
     */
    public function agencyList($condition = [], $pages = 20, $isReturn = false)
    {
        $pages = intval($pages);
        $pages < 1 && $pages = 20;
        $where = ['not_delete' => 1, 'type' => 1];
        $pageParam = ['query' => []];

        //选择上级代理弹框,不显示自己及下属代理
        $not = input('not', 0, 'intval');
        if (!empty($not)) {
            $subIds = $this->subAgencyIds($not);
            array_push($subIds, $not);
            $where['id'] = ['NOT IN', $subIds];
            $pageParam['query']['not'] = $not;
        }

        //判断用户状态
        $status = input('status', 0, 'intval');
        if (!empty($status)) {
            $where['status'] = $status;
            $pageParam['query']['status'] = $status;
        }

        //上级代理
        $parent = input('parent', 0, 'intval');
        if (!empty($parent)) {
            $where['parent_id'] = ('999999999' == $parent) ? 0 : $parent;
            $pageParam['query']['parent'] = $parent;
        }

        //判断是否有按名查询
        $name = input('name', '', 'trim');
        if ('' != $name) {
            $where['name'] = ['LIKE', "%{$name}%"];
            $pageParam['query']['name'] = $name;
        }

        //判断手机
        $phone = input('phone', '', 'trim');
        if ('' != $phone) {
            $where['phone'] = ['LIKE', "%{$phone}%"];
            $pageParam['query']['phone'] = $phone;
        }

        $query = $this->db->name('agency')
            ->where($where)
            ->order('id desc')
            ->paginate($pages, false, $pageParam);
        //echo $this->db->name('seller')->getlastsql();
        $paginate = $query->render();
        $list = $query->all();
        $total = $query->total();

        $statusList = [["id" => 1, "name" => lang('正常')], ["id" => 2, "name" => lang('禁用')]];
        $status = array_column($statusList, 'name', 'id');

        $model = \think\Loader::model('Agency');
        $allAgency = $model->allAgency();


        foreach ($list as $k => $v) {
            $v['status'] = $status[$v['status']];
            $v['parent_name'] = '-';
            if (!empty($v['parent_id'])) {
                $parent = $model->getParents($allAgency, $v['id']);
                $parent = array_column($parent, 'name');
                array_pop($parent);
                $v['parent_name'] = implode(" > ", $parent);
            }
            unset($v['password'], $v['not_delete']);
            $list[$k] = $v;
        }
        if ($isReturn) {
            return ['total' => $total, 'list' => $list];
        }

        //有查询则赋值
        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
        $this->assign('statusList', $statusList);
        $this->assign('list', $list);
        $this->assign('paginate', $paginate);
        $this->assign('title', lang('代理商列表'));
    }

    /**
     * 获取下级代理id集合
     * @param $id
     * @return array
     */
    public function subAgencyIds($id)
    {
        $data = $this->db->name('agency')
            ->where(['not_delete' => 1, 'type' => 1])
            ->where('parents', 'exp', "	REGEXP '[[:<:]]" . $id . "[[:>:]]'")
            ->column('id');

        return $data;
    }

    /**
     * 代理数据
     */
    public function agencyData($id)
    {
        $info = $this->db->name('agency')->where(['id' => $id, 'type' => 3, 'not_delete' => 1])->find();
        if (!$info) {
            return ['code' => 0, 'msg' => lang('商户管理员不存在')];
        }
        $data = [
            'id' => $info['id'],
            'name' => $info['name'],
            'brokerage' => $info['brokerage'],
            'agency_id' => 0,
            'agency_name' => '',
            'agency_brokerage' => 100,
            'employee_id' => 0,
            'employee_name' => '',
            'employee_brokerage' => 0,
        ];
        if (!empty($info['parent_id'])) {
            $agency = $this->db->name('agency')->where(['id' => $info['parent_id'], 'type' => 1, 'not_delete' => 1])->find();
            if ($agency) {
                $data['agency_id'] = $agency['id'];
                $data['agency_name'] = $agency['name'];
                $data['agency_brokerage'] = $agency['brokerage'];
            }
        }
        if (!empty($info['employee_id'])) {
            $employee = $this->db->name('agency')->where(['id' => $info['employee_id'], 'type' => 2, 'not_delete' => 1])->find();
            if ($employee) {
                $data['employee_id'] = $employee['id'];
                $data['employee_name'] = $employee['name'];
                $data['employee_brokerage'] = $employee['brokerage'];
            }
        }
        empty($data['agency_id']) && $data['agency_name'] = lang('平台自营');
        return ['code' => 1, 'data' => $data];
    }

}