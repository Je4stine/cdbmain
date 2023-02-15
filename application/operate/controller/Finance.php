<?php

namespace app\operate\controller;

/**
 *财务
 */
class Finance extends Common
{
    public function _initialize()
    {
        parent::_initialize();
    }

    //充值记录
    public function rechargeLog()
    {
        $data = \think\Loader::model('Finance', 'logic')->rechargeLog();
        $this->successResponse($data, lang('获取成功'));
    }


    //退款记录
    public function refundLog()
    {
        $data = \think\Loader::model('Finance', 'logic')->refundLog();
        $this->successResponse($data, lang('获取成功'));
    }

    //资金流水记录
    public function tradeLog()
    {
        $page_size = input('page_size', 20, 'intval');
        $data = \think\Loader::model('Finance', 'logic')->tradeLog([], $page_size, true);
        return $this->successResponse($data, lang('获取成功'));
    }

    //提现记录
    function withdrawalList()
    {
        $page_size = input('page_size', 20, 'intval');
        $data = \think\Loader::model('Withdrawal', 'logic')->withdrawalList([], $page_size, true);
        return $this->successResponse($data, lang('获取成功'));
        //return $this->fetch('withdrawalList');
    }

    //提现详情
    function withdrawalDetail()
    {
        $id = input('id', 0, 'intval');
        $info = $this->db->name('withdrawal_log')
            ->where(['id' => $id])
            ->find();
        !$info && $this->errorResponse(0, lang('信息不存在'));
        $info['account_info'] = json_decode($info['account_info'], true);
        $info['user_type'] = config("user_type_name." . $info['user_type']);
        $info['create_time'] = date("Y-m-d H:i:s", $info['create_time']);
        if (1 == $info['status']) {
            $info['pay_amount'] = bcsub($info['withdrawal_amount'], $info['service_amount'], 2);
        }
        $pay_type = [3 => lang('银行卡')];
        $status_text = ['1' => lang('审核中'), '2' => lang('已打款'), '3' => lang('已拒绝')];
        $info['pay_type_text'] = $pay_type[$info['pay_type']];
        if ($info['pay_type'] != 3) {
            $info['account'] = $info['account_info']['account'];
        } else {
            $info['account'] = $info['account_info']['bank'] . " " . $info['account_info']['bank_branch'] . " " . $info['account_info']['account'];
        }
        $info['real_name'] = $info['account_info']['real_name'];
        $info['status_text'] = $status_text[$info['status']];
        unset($info['account_info'], $info['update_time'], $info['relation_id']);
        return $this->successResponse($info, lang('获取成功'));
    }

    //提现审核
    function withdrawalAudit()
    {
        $id = input('id', 0, 'intval');
        $info = $this->db->name('withdrawal_log')
            ->where(['id' => $id])
            ->find();
        !$info && $this->errorResponse(0, lang('数据不存在'));
        1 != $info['status'] && $this->errorResponse(0, lang('提现已审核'));
        $status = input('status', 0, 'intval');
        $pay_amount = input('pay_amount', 0, 'floatval');
        $remark = input('remark', '', 'trim');
        if (empty($status) || !in_array($status, [2, 3])) {
            $this->errorResponse(0, lang('提现已审核'));
        }
        if (2 == $status) { //通过
            if (!preg_match("/^[0-9]+(.[0-9]{1,2})?$/", $pay_amount) || $pay_amount < 0.01) {
                return $this->errorResponse(0, lang('请输入正确的打款金额'));
            }
        } else {
            if ('' == $remark) {
                $this->errorResponse(0, lang('拒绝打款请输入备注'));
            }
            $pay_amount = 0;
        }

        $this->db->startTrans();
        try {
            $this->db->name('withdrawal_log')->where(['id' => $id])
                ->update(['status' => $status, 'pay_amount' => $pay_amount, 'remark' => $remark, 'update_time' => time()]);
            if (2 == $status) { //通过
                $sql = "UPDATE account SET freeze_amount = '0',
                      update_time = '" . time() . "',
                      withdrawal_amount = withdrawal_amount + " . $info['withdrawal_amount'] . "  
                      WHERE user_type = '{$info['user_type']}' AND relation_id = '{$info['relation_id']}'";
            } else { //拒绝
                $sql = "UPDATE account SET freeze_amount = '0',
                      update_time = '" . time() . "',
                      balance = balance+ " . $info['withdrawal_amount'] . "  
                      WHERE user_type = '{$info['user_type']}' AND relation_id = '{$info['relation_id']}'";
            }
            $this->db->execute($sql);
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            save_log('sql', $e->getMessage());
            $this->errorResponse(0, lang('操作失败'));
        }

        $this->successResponse([], lang('操作成功'));
    }


    //代理商账户
    function agency()
    {
        $page_size = input('page_size', 20, 'intval');
        $page_size < 1 && $page_size = 20;
        $where = ['a.not_delete' => 1];
        $pageParam = ['query' => []];

        //身份
        $type = input('type', 0, 'intval');
        if (!empty($type)) {
            $where['o.user_type'] = $type;
            $pageParam['query']['type'] = $type;
        }

        //判断是否有按名查询
        $name = input('name', '', 'trim');
        if ('' != $name) {
            $where['a.name'] = ['LIKE', "%{$name}%"];
            $pageParam['query']['name'] = $name;
        }

        //判断是否有按手机查询
        $phone = input('phone', '', 'trim');
        if ('' != $phone) {
            $where['a.phone'] = ['LIKE', "%{$phone}%"];
            $pageParam['query']['phone'] = $phone;
        }
        $role = config('user_type_name');

        $query = $this->db->name('account')
            ->alias('o')
            ->join("agency a", 'o.relation_id = a.id', 'LEFT')
            ->field('o.*,a.name,a.phone')
            ->where($where)
            ->order('o.id DESC')
            ->paginate($page_size, false, $pageParam);

        $list = $query->all();
        //$paginate = $query->render();
        $total = $query->total();

        foreach ($list as $k => $v) {
            $v['role'] = $role[$v['user_type']];
           // $amount = $this->db->name('order_brokerage')->where(['status' => 1, 'relation_id' => $v['relation_id']])->sum('amount');
            unset($v['create_time'], $v['update_time'], $v['wechat_num'], $v['alipay_num']);
            $list[$k] = $v;
        }
        return $this->successResponse(['total' => $total, 'list' => $list], lang('代理商账户'));
    }
}
