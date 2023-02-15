<?php

namespace app\common\logic;

use app\common\service\Office;

/**
 * 提现相关
 * @package app\common\logic
 */
class Withdrawal extends Common
{

    public function _initialize()
    {
        parent::_initialize();
    }

    function withdrawalList($condition = [], $pages = 20, $isReturn = false)
    {
        $pages = intval($pages);
        $pages < 1 && $pages = 20;
        $where = [];
        $pageParam = ['query' => []];

        //状态
        $status = input('status', 0, 'intval');
        if (!empty($status)) {
            $where['a.status'] = $status;
            $pageParam['query']['status'] = $status;
        }
        //按名查询
        $name = input('name', '', 'trim');
        if ('' != $name) {
            $where['a.name'] = ['LIKE', "%{$name}%"];
            $pageParam['query']['name'] = $name;
        }
        //代理
        $agency = input('agency', '', 'trim');
        if ('' != $agency) {
            $where['b.name'] = ['LIKE', "%{$agency}%"];
            $pageParam['query']['agency'] = $agency;
        }

        //支付方式
        $pay_type = input('pay_type', 0, 'intval');
        if (!empty($pay_type)) {
            $where['a.pay_type'] = $pay_type;
            $pageParam['query']['pay_type'] = $pay_type;
        }

        //用户类型
        if (isset($condition['user_type'])) {
            $user_type = $condition['user_type'];
            $where['a.user_type'] = $user_type;
            $pageParam['query']['user_type'] = $user_type;
        }
        //用户id
        if (isset($condition['user_id'])) {
            $user_id = $condition['user_id'];
            $where['a.relation_id'] = $user_id;
            $pageParam['query']['relation_id'] = $user_id;
        }

        //时间查询
        $start_time = input('start_time', '', 'trim');
        $end_time = input('end_time', '', 'trim');
        $patten = "/^[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/";
        if (preg_match($patten, $start_time) && preg_match($patten, $end_time)) {
            $where['a.create_time'] = ['between', [strtotime($start_time), strtotime($end_time)]];
            $pageParam['query']['start_time'] = $start_time;
            $pageParam['query']['end_time'] = $end_time;
        } else if (preg_match($patten, $start_time)) {
            $where['a.create_time'] = ['>=', strtotime($start_time)];
            $pageParam['query']['start_time'] = $start_time;
        } else if (preg_match($patten, $end_time)) {
            $where['a.create_time'] = ['<=', strtotime($end_time)];
            $pageParam['query']['end_time'] = $end_time;
        }

        $is_execl = input('is_execl', 0, 'trim');
        $pay_text = [3 => lang('银行卡')];
        //是否导出
        if (1 == $is_execl) {
            $query = $this->db->name('withdrawal_log')
                ->alias('a')
                ->join("agency b", 'a.relation_id = b.id', 'LEFT')
                ->field('a.*,b.name as agency')
                ->where($where)
                ->order('id DESC');
            $list = $query->select();

            if (empty($list)) {
                $this->error(lang('给出的条件数据不存在'));
            }

            $user_type = config('user_type_name');
            $status_text = ['1' => lang('审核中'), '2' => lang('已打款'), '3' => lang('已拒绝')];

            foreach ($list as &$v) {
                $v['user_type'] = $user_type[$v['user_type']];
                $v['status_text'] = $status_text[$v['status']];
                $v['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
                $v['update_time'] = empty($v['update_time']) ? '' : date("Y-m-d H:i:s", $v['update_time']);
                $account = json_decode($v['account_info'], true);
                $v['pay_type'] = $pay_text[$account['pay_type']];
                $v['account'] = "\t" . $account['account'] . "\t";
                $v['bank'] = $account['bank'] ?: '支付宝';
                $v['real_name'] = $account['real_name'];
                $v['identity'] = isset($account['identity_info']) ? $account['identity_info'] : '';
                $v['bank_branch'] = "\t" . $account['bank_branch'] . "\t";
                $v['order_no'] = "\t" . $v['order_no'] . "\t";
                $v['need_amount'] = $v['withdrawal_amount'] - $v['service_amount'] - $v['pay_amount'];
            }
            $title = array(
                lang('提现单号'),
                lang('提现时间'),
                lang('用户类型'),
                lang('用户名称'),
                lang('提现金额'),
                lang('手续费率'),
                lang('手续费'),
                lang('收款类型'),
                lang('收款账户'),
                lang('收款银行'),
                lang('开户行'),
                lang('收款人姓名'),
                lang('状态'),
                lang('已打款'),
                lang('需要打款')
            );
            $filename = lang('代理商提现相关信息导出') . '-' . $name . '-' . date('Y-m-d');

            $excel = new Office();

            //数据中对应的字段，用于读取相应数据：
            $keys = ['order_no', 'create_time', 'user_type', 'name', 'withdrawal_amount', 'rate', 'service_amount', 'pay_type', 'account', 'bank', 'bank_branch', 'real_name', 'status_text', 'pay_amount', 'need_amount'];
            $tmp = time() . rand(1000, 9999) . ".xlsx";
            $excel->outdata($filename, $list, $title, $keys, $tmp);
            return $this->errorResponse(301, config('website') . '/uploads/' . $tmp);

        }

        $query = $this->db->name('withdrawal_log')
            ->alias('a')
            ->join("agency b", 'a.relation_id = b.id', 'LEFT')
            ->field('a.*,b.name as agency')
            ->where($where)
            ->order('id DESC')
            ->paginate($pages, false, $pageParam);
        $paginate = $query->render();
        $list = $query->all();
        $total = $query->total();
        $user_type = config('user_type_name');
        $status_text = ['1' => lang('审核中'), '2' => lang('已打款'), '3' => lang('已拒绝')];

        foreach ($list as $k => $v) {
            $v['user_type'] = $user_type[$v['user_type']];
            $v['status_text'] = $status_text[$v['status']];
            $v['pay_type'] = $pay_text[$v['pay_type']];
            $v['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
            $v['update_time'] = empty($v['update_time']) ? '' : date("Y-m-d H:i:s", $v['update_time']);
            $v['pay_amount'] = priceFormat($v['pay_amount']);
            $v['account_info'] = json_decode($v['account_info'], true);
            $v['real_name'] = $v['account_info']['real_name'];
            $v['identity'] = $v['account_info']['identity_info'];
            unset($v['account_info']);
            if ($isReturn) {
                unset($v['user_type'], $v['relation_id'], $v['update_time']);
            }
            $list[$k] = $v;
        }

        $sum = $this->db->name('withdrawal_log')
            ->alias('a')
            ->join("agency b", 'a.relation_id = b.id', 'LEFT')
            ->field('sum(a.pay_amount) as pay_amount,sum(a.withdrawal_amount) as withdrawal_amount')
            ->where($where)
            ->find();

        if ($isReturn) {
            return ['total' => $total, 'list' => $list, 'pay_amount' => $sum['pay_amount'], 'withdrawal_amount' => $sum['withdrawal_amount']];
        }

        $this->assign('title', lang('提现记录'));
        $this->assign('list', $list);
        $this->assign('paginate', $paginate);
        !empty($pageParam['query']) && $this->assign('params', $pageParam['query']);
    }

    /**
     * 添加提现账户
     */
    public function accountAdd($data = [])
    {
        $params = $this->_getAccountParams($data);
        if (!is_array($params)) {
            return ['code' => 0, 'msg' => $params];
        }
        $params['create_time'] = time();
        $id = $this->db->name('withdrawal_account')->insertGetId($params);
        if ($id) {
            $this->operateLog($id, '添加账户');
            return ['code' => 1, 'msg' => lang('添加账户信息成功')];
        }
        return ['code' => 0, 'msg' => lang('添加账户信息失败')];
    }

    /**
     * 提现账户参数
     * @param array $data
     * @return array|string
     */
    private function _getAccountParams($data = [])
    {
        $id = input('id', '', 'trim');
        $params = [
            'pay_type' => 3,
            'account' => input('account', 0),
            'real_name' => input('real_name'),
        ];

        if (empty($id)) {
            $info = $this->db->name('withdrawal_account')
                ->where(['user_type' => $data['user_type'], 'relation_id' => $data['relation_id']])
                ->find();
            if ($info) {
                return lang('已经添加提现账户');
            }
        } else {
            $params['id'] = $id;
        }

        $params['bank'] = input('bank');
        $params['bank_branch'] = input('bank_branch');

        if ('' == $params['account']) {
            return lang('请输入账户');
        }
        if ('' == $params['real_name']) {
            return lang('请输入真实姓名');
        }
        if ('' == $params['bank']) {
            return lang('请选择所属银行');
        }
        $params['identity_info'] = [

        ];

        $params['identity_info'] = json_encode($params['identity_info'], JSON_UNESCAPED_UNICODE);
        return array_merge($params, $data);

    }

    /**
     * 修改提现账户
     */
    public function accountEdit($data = [])
    {
        $params = $this->_getAccountParams($data);

        if (!is_array($params)) {
            return ['code' => 0, 'msg' => $params];
        }
        $params['update_time'] = time();
        if ($this->db->name('withdrawal_account')->update($params)) {
            $this->operateLog(0, '修改提现账户');
            return ['code' => 1, 'msg' => lang('修改账户信息成功')];
        }
        return ['code' => 0, 'msg' => lang('修改账户信息失败')];
    }

    /**
     * 提现申请
     */
    /**
     * 提现申请
     */
    public function apply($user_type, $uid)
    {
//        用户下单完成后，产生分润
//        单笔订单3天内可以退款
        $amount = input('amount', 0, 'floatval');
        $account_id = input('account_id', 0, 'intval');
//        if (!preg_match("/^[0-9]+(.[0-9]{1,2})?$/", $amount) || $amount < 0.01) {
//            return ['code' => 0, 'msg' => '请输入正确的金额'];
//        }
        if (!preg_match("/^[1-9][0-9]*$/", $amount) || $amount < 1) {
            return ['code' => 0, 'msg' => lang("提现金额须为正整数")];
        }
        $withdrawal_config = $this->getOperatorConfig('withdrawal');
        $withdrawal_config = json_decode($withdrawal_config, true);
        if ($amount < $withdrawal_config['amount']) {
            return ['code' => 0, 'msg' => lang("最低提现金额为") . $withdrawal_config['amount']];
        }

        if (empty($account_id)) {
            return ['code' => 0, 'msg' => lang('请选择提现账号')];
        }

        $withdrawal_account = $this->db->name('withdrawal_account')
            ->field('pay_type,account,bank,bank_branch,real_name,identity_info')
            ->where(['id' => $account_id, 'user_type' => $user_type, 'relation_id' => $uid])
            ->find();
        if (!$withdrawal_account) {
            return ['code' => 0, 'msg' => lang('提现账号不存在')];
        }
        $identity_info = json_decode($withdrawal_account['identity_info'], true);
        $withdrawal_account['identity_info'] = $identity_info['card'] . " " . $identity_info['address'];

        $log = $this->db->name('withdrawal_log')
            ->where(['user_type' => $user_type, 'relation_id' => $uid, 'status' => 1])
            ->find();
        if ($log) {
            return ['code' => 0, 'msg' => lang('尚有提现申请未处理')];
        }

        $account = $this->db->name('account')->where(['user_type' => $user_type, 'relation_id' => $uid])->find();
        if (!$account) {
            return ['code' => 0, 'msg' => lang('用户信息不存在')];
        }

        $max_amount = intval($withdrawal_config['max_amount']);
        $real_amount = $amount;//真实提现金额


        $sum = $this->db->name('withdrawal_log')->where(['relation_id' => $uid, 'status' => 2, 'create_time' => ['>=', strtotime(date("Y-m-d"))]])->sum('withdrawal_amount');
        $sum = floatval($sum);
        if ($sum >= $max_amount) {
            return ['code' => 0, 'msg' => lang("每天最多可提现") . ": {$max_amount}"];
        }

        if ($amount > $account['balance']) {
            return ['code' => 0, 'msg' => lang("账户可提现金额为") . ": {$amount}"];
        }
        $max = bcsub($max_amount, $sum, 2);
        if ($max <= 0) {
            return ['code' => 0, 'msg' => lang("每天最多可提现") . ": {$max_amount}"];
        }
        if ($amount > $max) {
            return ['code' => 0, 'msg' => lang("今天可提现金额为") . ": {$max}"];
        }

        //手续费,最低1分
        $rate = $withdrawal_config['rate'];
        $service_amount = $real_amount * $rate;
        $service_amount = ceil($service_amount) / 100;

        $lock_id = 'withdrawal:' . $this->oCode . ":{$uid}";
        $lock = cache($lock_id);
        if ($lock) {
            return ['code' => 0, 'msg' => lang('提现处理中')];
        }
        cache($lock_id, 1, 30);
        $params = [
            'order_no' => date("YmdHis") . mt_rand(1000, 9999),
            'account_info' => json_encode($withdrawal_account, JSON_UNESCAPED_UNICODE),
            'name' => $withdrawal_account['real_name'],
            'user_type' => $user_type,
            'relation_id' => $uid,
            'pay_type' => $withdrawal_account['pay_type'],
            'rate' => $rate,
            'withdrawal_amount' => $amount,
            'service_amount' => $service_amount,
            'status' => 1,
            'create_time' => time()
        ];
        $balance = bcsub($account['balance'], $amount, 2);


        $this->db->startTrans();
        try {
            $this->db->name('withdrawal_log')->insert($params);
            $this->db->name('account')->where(['id' => $account['id']])->update(['freeze_amount' => $amount, 'balance' => $balance, 'update_time' => time()]);
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            save_log('sql', $e->getMessage());
            return ['code' => 0, 'msg' => lang('信息提交失败，请稍后重试')];
        }
        return ['code' => 1, 'msg' => lang('信息提交成功')];

    }


}
