<?php
//客户列表
namespace app\operate\controller;

/**
 *
 */
class Customer extends Common
{
    public function _initialize()
    {
        parent::_initialize();
        $this->logic = \think\Loader::model('Customer', 'logic');
    }

    public function index()
    {
        $page_size = input('page_size', 20, 'intval');
        $result = $this->logic->customerList([], $page_size, true);
        return $this->successResponse($result, '用户列表');
    }


    public function detail()
    {
        $id = input('id', 0, 'intval');
        $info = $this->db->name('user')->where('id', $id)->find();
        !$info && $this->errorResponse(lang('用户不存在'));
        $info['nick'] = empty($info['nick']) ? 'Usuario Misterioso' :$info['nick'];
        $table = getTableNo('recharge_user', 'hash', 4, $id);
        $info['recharge'] = (float)$this->db->name($table)->where(['uid' => $id])->sum('amount');
        $info['refund'] = (float)$this->db->name('refund_log')->where(['uid' => $id, 'status' => 1])->sum('amount');
        empty($info['avatar']) && $info['avatar'] = config('customer.avatar');
        $info['create_time'] = date("Y-m-d H:i", $info['create_time']);
        $info['last_login'] = date("Y-m-d H:i", $info['last_login']);
        $user_table = getTableNo('order_user', 'hash', 16, $id);
        $info['order_num'] = $this->db->name($user_table)->where(['uid' => $id])->count();

        $info['order_active'] = $this->db->name('order_active')->where(['uid' => $id, 'status' => 1, 'expire_time' => ['>', time()]])->count();
        $info['order_overdue'] = $this->db->name('order_active')->where(['uid' => $id, 'status' => 1, 'end_time' => ['exp', 'is null'], 'expire_time' => ['<', time()]])->count();
        return $this->successResponse($info, lang('用户详情'));
    }

    //退押金
    public function redeposit()
    {
        $id = input('id', 0, 'intval');
        $info = $this->db->name('user')->where('id', $id)->find();
        !$info && $this->errorResponse(0, lang('信息不存在'));
        $info['deposit'] < 0.01 && $this->errorResponse(0, lang('没有可退押金'));

        $balance = bcadd($info['balance'], $info['deposit'], 2);
        $result = $this->db->name('user')->where('id', $id)->update(['balance' => $balance, 'deposit' => 0, 'update_time' => time()]);
        if (!$result) {
            $this->errorResponse(0, lang('操作失败'));
        }
        $this->operateLog($id, '退押金');
        $this->successResponse([], lang('操作成功'));
    }

    /**
     * 退款
     */
    public function rebalance()
    {
        $id = input('id', 0, 'intval');
        $info = $this->db->name('user')->where('id', $id)->find();
        !$info && $this->errorResponse(0, lang('用户不存在'));
        $amount = priceFormat($info['balance']);
        if ($amount < 0.01) {
            $this->errorResponse(0, lang('没有可退余额'));
        }
        $table = getTableNo('recharge_user', 'hash', 4, $id);
        $max = (float)$this->db->name($table)->where(['uid' => $id])->sum('balance');
        if ($amount > $max) {
            $this->errorResponse(0, lang('最多可退余额') . lang('￥') . $max);
        }
        $app_type = config('app_type');
        $app_type = array_flip($app_type);
        $result = \think\Loader::model('Payment', 'logic')->refund($info['id'], $amount, 'mercadopago');
        if ($result['code'] != '1') {
            $this->errorResponse(0, lang($result['msg']));
        }
        $this->operateLog($id, '用户退款');
        $this->successResponse([], lang('共退款') . lang('￥') . $result['refund']);
    }
}
