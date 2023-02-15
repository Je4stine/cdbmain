<?php

namespace app\common\service;

use Stripe\Card;

//👇这里可以看到可以引入Stripe各种API类，用来下面调用。
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Dispute;
use Stripe\Error\Base;
use Stripe\Exception\ApiErrorException;
use Stripe\Refund;
use Stripe\Source;
use Stripe\Stripe;
use think\Exception;
use think\Log;


class StripePay extends \app\common\service\Base
{
    var $bank_info = [];
    var $user_info = [];

    public function __construct($user_info)
    {
        Stripe::setApiKey(config('stripe_api_key'));
        $this->user_info = $user_info;
    }


    function addCard($params)
    {
        try {
            $customer = \Stripe\Customer::create([
                'name' => $this->user_info['nick'],
                'email' => $this->user_info['email'],
                'phone' => $this->user_info['mobile'],
                'payment_method' => $params['payment_method_id'],
            ]);
            if (empty($customer)) {
                return ['code' => 0, 'msg' => 'not card'];
            }
//            $res = db()->name('user')->where(['id' => $this->user_info['id']])
//                ->update(['customer_id' => $customer->id, 'default_card_id' => $payment_method_id]);
//            if (empty($res)) {
//                $msg = lang('操作失败');
//                save_log('stripe_pay', $customer);
//                return ['code' => 0, 'msg' => $msg];
//            }
            return ['code' => 1, 'data' => ['customer_id' => $customer->id, 'default_card_id' => $params['payment_method_id']]];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            save_log('stripe_pay', '添加银行卡失败:' . $e->getMessage());
            return ['code' => 0, 'msg' => '操作失败'];
        }
    }


    public function getCardList($is_client = false)
    {
        try {
            $data = [];
            if (empty($this->user_info['customer_id'])) {
                return $data;
            }
            $res = \Stripe\PaymentMethod::all([
                'customer' => $this->user_info['customer_id'],
                'type' => 'card',
            ]);
            $data = [];
            $data_client = [];
            if (!empty($res['data'])) {
                foreach ($res as $re) {
                    $da['id'] = $re['id'];
                    $da['type'] = $re['card']['brand'];
                    $da['card_number'] = '**** **** **** ' . $re['card']['last4'];
                    $da['fingerprint'] = $re['card']['fingerprint'];
                    $da['is_default'] = '0';
                    $da['exp_month'] = $re['card']['exp_month'];
                    $da['exp_year'] = $re['card']['exp_year'];
                    if (!isset($data[$re['card']['fingerprint']])) {
                        if ($this->user_info['default_card_id'] == $re['id']) {
                            $da['is_default'] = '1';
                        }
                        $data[$re['card']['fingerprint']] = $da;
                        $data_client[] = $da;
                    }
                }
            }

            if ($is_client) {
                return $data_client;
            }
            return $data;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            save_log('stripe_pay', '获取银行卡列表失败:' . $e->getMessage());
            return ['code' => 0, 'msg' => '获取失败'];
        }
    }


    /**
     * 删除银行卡
     */
    public function deleteCard($params)
    {
        try {
            $payment_method = \Stripe\PaymentMethod::retrieve(
                $params['card_id']
            );
            $payment_method->detach();
//            db()->name('user')->where('id', $this->user_info['id'])->update(['customer_id' => null]);
            return ['code' => 1, 'msg' => '操作成功'];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            save_log('stripe_pay', '删除银行卡失败:' . $e->getMessage());
        }
        return ['code' => 0, 'msg' => '操作失败'];
    }

    /**
     * 付款处理
     */
    public function generateTrade($order_info = [], $card_id = null)
    {
        try {
            try {
                $where = [
                    'amount' => $order_info['amount'] * 100,
                    'currency' => 'USD',
                    'customer' => $this->user_info['customer_id'],
                    'payment_method' => $card_id,
                    'off_session' => true,
                    'confirm' => true,
                ];
                if (empty($card_id)) {
                    $card = $this->getCardList(true);
                    if (empty($card)) {
                        return ['code' => 0, 'msg' => lang('您未选择支付卡，请绑定后再试')];
                    }
                    $where['payment_method'] = $card[0]['id'];
                }

                $intent = \Stripe\PaymentIntent::create($where);
                save_log('stripe_pay', $intent);

                if (!empty($intent) && 'succeeded' != $intent->status) {
                    return ['code' => 0, 'msg' => lang('支付失败')];
                }
            } catch (\Stripe\Exception\ApiErrorException $e) {
                save_log('stripe_pay', 'Error message is:' . $e->getError()->message);
                save_log('stripe_pay', 'Error code is:' . $e->getError()->code);
                return ['code' => 0, 'msg' => lang('支付失败') . $e->getError()->message];
            }
            return ['code' => 1, 'msg' => 'ok', 'data' => ['trade_no' => $intent->charges->data[0]['id']]];
        } catch (\Stripe\Exception\CardException $e) { //catch Stripe的API基本异常
            // Display a very generic error to the user, and maybe send
            // yourself an email
            save_log('stripe_pay', '支付错误:' . $e->getStripeCode());
            save_log('stripe_pay', '支付错误:' . $e->getMessage());

            return ['code' => 0, 'msg' => 'no'];
        } catch (Exception $e) {
            db()->rollback();
            // Something else happened, completely unrelated to Stripe
            save_log('stripe_pay', 'stripe支付失败:' . $e->getMessage());
            return ['code' => 0, 'msg' => 'no'];
        }
    }


    public function refund($order_no, $refund_no, $amount, $money = null, $transaction_id = '')
    {
//        $trade_no = db()->name('recharge_log')->where(['order_no' => $order_no])->value('trade_no');
        $where = ["charge" => $refund_no];
        if (!empty($money)) {
            $where['amount'] = $money * 100;
        }
        $re = \Stripe\Refund::create($where);

        if ('succeeded' != $re->status) {
            save_log('stripe_pay', $re);
            return ['code' => 0, 'msg' => lang('退款失败')];
        }

        return ['code' => 1, 'msg' => lang('退款成功')];
    }

    public function getSetupIntent()
    {
        $setup_intent = \Stripe\SetupIntent::create();
        $client_secret = $setup_intent->client_secret;
        return $client_secret;
    }

    /**
     * 获取 银行卡ID
     */
    public function paymentMethod()
    {
        $ret = \Stripe\paymentMethod::create([
            'type' => 'card',
            'card' => [
                'number' => '4242424242424242',
                'exp_month' => 10,
                'exp_year' => 2021,
                'cvc' => '314',
            ],
        ]);
        return $ret;
    }
}
