<?php

namespace app\agency\controller;


class User extends Common
{

    //首页
    public function index()
    {
        $account = $this->db->name('account')
            ->field('total_amount,balance,audit_amount,freeze_amount,withdrawal_amount')
            ->where(['user_type' => $this->auth_info['user_type'], 'relation_id' => $this->auth_info['uid']])
            ->find();
        $account['name'] = $this->auth_info['name'];
        $account['avatar'] = config('seller_img.avatar');
        $account['credit_amount'] = 0;
        $account['total_amount'] = priceFormat($account['total_amount']);
        $account['balance'] = priceFormat($account['balance']);

        //审核资金
        $account['audit_amount'] = (float)$this->db->name("order_brokerage")
            ->where(['relation_id' => $this->auth_info['uid'], 'status' => 1])
            ->sum('amount');
        $account['sum_amount'] = bcadd($account['total_amount'], $account['audit_amount'], 2);

        $query = $this->db->name('order_active')->field('brokerage_data,payment_amount')->where(['type' => 1, 'status' => 2, 'is_pay' => 0])->select();
        foreach ($query as $val) {
            $brokerage_data = json_decode($val['brokerage_data'], true);
            foreach ($brokerage_data as $v) {
                if ($v['id'] == $this->auth_info['uid']) {
                    $account['credit_amount'] = bcadd($account['credit_amount'], $val['payment_amount'], 2);
                    break;
                }
            }
        }

        return $this->successResponse($account, '个人首页');
    }

    //修改密码
    public function password()
    {
        $old_password = input('old_password', '', 'trim');
        $new_password = input('new_password', '', 'trim');
        $confirm_password = input('confirm_password', '', 'trim');
        if (strlen($old_password) < 6) {
            $this->errorResponse(0, lang('原密码至少六位数'));
        }
        if (strlen($new_password) < 6) {
            $this->errorResponse(0, lang('新密码至少六位数'));
        }
        if ($new_password != $confirm_password) {
            $this->errorResponse(0, lang('两次密码不一致'));
        }

        switch ($this->auth_info['role']) {
            case 'agency'://代理
                $table = 'agency';
                $password = $this->db->name('agency')->where(['id' => $this->auth_info['uid'], 'type' => 1])->value('password');
                break;
            case 'employee'://业务员
                $table = 'agency';
                $password = $this->db->name('agency')->where(['id' => $this->auth_info['uid'], 'type' => 2])->value('password');
                break;
            case 'seller'://商户
                $table = 'agency';
                $password = $this->db->name('agency')->where(['id' => $this->auth_info['uid'], 'type' => 3])->value('password');
                break;
            default:
                return $this->errorResponse(403, '请先登录');
        }

        if ($password != md123($old_password)) {
            $this->errorResponse(0, '原密码错误');
        }
        $this->db->name($table)->where(['id' => $this->auth_info['uid']])->update(['password' => md123($new_password)]);
        return $this->successResponse([], '修改密码成功');
    }


    /**
     * 添加提现账户
     */
    public function accountAdd()
    {
        $code = input('code', '', 'trim');
        $phone = input('phone', '');

        $result = \think\Loader::model('ValidateToken', 'logic')
            ->checkCode($phone, 'bank', $code);

        if (1 != $result['code']) {
            !isset($result['data']) && $result['data'] = [];
            return $this->errorResponse($result['code'], $result['msg'], $result['data']);
        }
        $data = ['user_type' => $this->auth_info['user_type'], 'relation_id' => $this->auth_info['uid']];
        $result = \think\Loader::model('Withdrawal', 'logic')->accountAdd($data);
        if (1 == $result['code']) {
            return $this->successResponse([], '添加提现账户成功');
        }
        return $this->errorResponse(0, $result['msg']);
    }

    /**
     * 修改提现账户
     */
    public function accountEdit()
    {
        $code = input('code', '', 'trim');
        $phone = input('phone', '');

        $result = \think\Loader::model('ValidateToken', 'logic')
            ->checkCode($phone, 'bank', $code);

        if (1 != $result['code']) {
            !isset($result['data']) && $result['data'] = [];
            return $this->errorResponse($result['code'], $result['msg'], $result['data']);
        }

        $data = ['user_type' => $this->auth_info['user_type'], 'relation_id' => $this->auth_info['uid']];
        $result = \think\Loader::model('Withdrawal', 'logic')->accountEdit($data);
        if (1 == $result['code']) {
            return $this->successResponse([], '修改提现账户成功');
        }
        return $this->errorResponse(0, $result['msg']);
    }


    /**
     * 获取提现账户
     */
    public function withdrawalAccount()
    {
        $account = $this->db->name('withdrawal_account')
            ->where(['user_type' => $this->auth_info['user_type'], 'relation_id' => $this->auth_info['uid']])
            ->find();
        $bank_list = [
            '中国银行',
            '中国工商银行',
            '中国农业银行',
            '中国建设银行',
            '交通银行',
            '中国邮政储蓄银行',
            '中国民生银行',
            '中国光大银行',
            '招商银行',
            '中信银行',
            '兴业银行',
            '华夏银行',
        ];
        $withdrawal_config = $this->getOperatorConfig('withdrawal');
        $withdrawal_config = json_decode($withdrawal_config, true);
        $types = [['id' => 1, 'text' => '微信'], ['id' => 2, 'text' => '支付宝'], ['id' => 3, 'text' => '银行卡']];
        $text_arr = array_column($types, 'text', 'id');

        foreach ($types as $k => $v) {
            if (!in_array($v['id'], $withdrawal_config['type'])) {
                unset($types[$k]);
            }
        }
        $types = array_values($types);
        $phone = $this->db->name('agency')->where(['id' => $this->auth_info['id'], 'not_delete' => 1])->value('phone');
        if (!$account) {
            return $this->errorResponse(301, '暂无提现账户', ['bank_list' => $bank_list, 'types' => $types, 'phone' => $phone]);
        }

        $account['balance'] = $this->db->name('account')
            ->where(['user_type' => $this->auth_info['user_type'], 'relation_id' => $this->auth_info['uid']])
            ->value('balance');
        $account['balance'] = priceFormat($account['balance']);
        $account['rate'] = $withdrawal_config['rate'];//提现费率
        $account['intro'] = $withdrawal_config['intro'];//提现说明
        $account['phone'] = $phone;
        $account['type_text'] = $text_arr[$account['pay_type']];
        $account['bank_list'] = $bank_list;
        $account['types'] = $types;
        return $this->successResponse($account, '获取提现账户');
    }

    /**
     * 提现申请
     */
    public function withdrawalApply()
    {
        $result = \think\Loader::model('Withdrawal', 'logic')->apply($this->auth_info['user_type'], $this->auth_info['uid']);
        if (1 == $result['code']) {
            return $this->successResponse([], '提交提现申请成功');
        }
        return $this->errorResponse(0, $result['msg']);
    }

    /**
     * 提现记录
     */
    public function withdrawalLog()
    {
        $page_size = input('page_size', 10, 'intval');
        $page_size < 1 && $page_size = 10;
        $result = \think\Loader::model('Withdrawal', 'logic')
            ->withdrawalList(['user_type' => $this->auth_info['user_type'], 'user_id' => $this->auth_info['uid']], $page_size, true);
        return $this->successResponse($result, '提现记录');
    }


    /**
     * 分成月数据
     */
    public function brokerageMonth()
    {
        $month = input('month', date("Y-m"), 'trim');
        $table = intval(str_replace("-", "", $month));
        if ($table <= date("Ym")) {
            $table = getTableByDate("order_brokerage", $table);
            $query = $this->db->query("SHOW TABLES LIKE '" . $table . "'");
        }

        if (!$query) {
            $this->successResponse([], '分成月数据');
        }
        $start = $month . "-01";
        $end = strtotime("$start +1 month -1 day");
        $end > time() && $end = time();
        $end = date("Y-m-d", $end);
        $query = $this->db->name('stat_agency')
            ->field('date,brokerage_settle as amount')
            ->where(['agency_id' => $this->auth_info['uid'],'date' => ['between', [$start, $end]]])
            ->select();
        $query = array_column($query,NULL,'date');
        $date = [];
        for ($dateline = strtotime($start); $dateline <= strtotime($end); $dateline = $dateline + 86400) {
            $date = date("Y-m-d", $dateline);
            if(isset($query[$date])){
                $list[] = $query[$date];
            }else{
                $list[] = [
                    'date'=> $date,
                    'amount' => 0,
                ];
            }
        }
        $this->successResponse($list, '分成月数据');
    }


    /**
     * 分成记录
     */
    public function brokerageLog()
    {
        $page_size = input('page_size', 10, 'intval');
        $page_size < 1 && $page_size = 10;
        $result = \think\Loader::model('Finance', 'logic')
            ->brokerageLog(['user_type' => $this->auth_info['user_type'], 'user_id' => $this->auth_info['uid']], $page_size, true);

        return $this->successResponse($result, '分成记录');
    }

    //查找会员
    public function searchMember()
    {
        $member_id = input('member_id', '0', 'intval');
        $where = ['member_id' => $member_id];
        if ($this->auth_info['status'] != 1) {//禁止
            $where['id'] = 0;
        }
        $info = $this->db->name('user')
            ->field('id,nick,nickCode,avatar,openid')
            ->where($where)
            ->find();
        if ($info) {
            $info['nick'] = $this->getCustomerNick($info['nickCode']);
            empty($info['avatar']) && $info['avatar'] = config('customer.avatar');
        } else {
            $info = [];
        }
        return $this->successResponse($info, '查找会员');
    }
}
