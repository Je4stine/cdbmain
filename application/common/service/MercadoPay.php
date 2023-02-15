<?php


namespace app\common\service;
use MercadoPago;
use MercadoPago\Payer;
use MercadoPago\Payment;
use Overtrue\Socialite\AuthorizeFailedException;


class MercadoPay extends Base
{
    public function _initialize()
    {
        parent::_initialize();
        MercadoPago\SDK::setAccessToken(config('mercadoPago_access_token'));
    }


    public function generateTrade($params)
    {

        $payment = new MercadoPago\Payment();
        $payment->transaction_amount = $params['amount'];
        $payment->token = $params['token'];
        $payment->installments = 1;
        $payment->description = "Arriendo de cargador portátil GoBattery";
        $payment->statement_descriptor = "GoBattery";
        $payment->payer = array(
            "type" => "customer",
            "id" => $params['customerId']
        );
        $payment->additional_info = array(
            'items' => array(
                array(
                    'id' => 'PR0001',
                    'title' => 'GOBATTERY',
                    'description' => 'Arriendo de cargador portátil GoBattery',
                    'category_id' => 'phones',
                    'quantity' => 1,
                    'unit_price' => $params['amount']
                )
            )
        );
        $payment->notification_url = "https://cdb.gobattery.cl/app/callback/mercadopago";

        if ( $payment->save() ) {
            save_log('mercadopago', '创建付款成功:'.$payment->__get('id'));
            return ['code' => 1, 'data' => ['pay_id' => $payment->__get('id')]];
        }

        save_log('mercadopago_error', '创建付款失败:');
        save_log('mercadopago_error', $payment->error());
        save_log('mercadopago_error', $params);
        return ['code' => 0, 'msg' => lang("支付失败")];
    }

    public function generateTradeFreeze($params)
    {
        MercadoPago\SDK::setAccessToken('TEST-6691522837724442-120802-b9e34191c218b8dbe2cb6ddbe0b24dd2-662227956');
//        $payment = new MercadoPago\Payment();
//        $payment->transaction_amount = $params['amount'];
//        $payment->token = $params['token'];
//        $payment->installments = 1;
//        $payment->description = "Arriendo de cargador portátil GoBattery";
//        $payment->statement_descriptor = "GoBattery";
//        $payment->capture = false;
//        $payment->payer = array(
//            "type" => "customer",
//            "id" => $params['customerId']
//        );
//        $payment->additional_info = array(
//            'items' => array(
//                array(
//                    'id' => 'PR0001',
//                    'title' => 'GOBATTERY',
//                    'description' => 'Arriendo de cargador portátil GoBattery',
//                    'category_id' => 'phones',
//                    'quantity' => 1,
//                    'unit_price' => $params['amount']
//                )
//            )
//        );
        $payment = new MercadoPago\Payment();

        $payment->transaction_amount = $params['amount'];
        $payment->token = $params['token'];
        $payment->description = "Title of what you are paying for";
        $payment->installments = 1;
        $payment->payment_method_id = "visa";
        $payment->payer = array(
            "type" => "customer",
            "id" => $params['customerId']
        );
        $payment->notification_url = "https://cdb.gobattery.cl/app/callback/mercadopagoFreeze";
        $payment->capture=false;


        if ( $payment->save() ) {
            save_log('mercadopago_freeze', '预授权成功:'.$payment->__get('id'));
            return ['code' => 1, 'data' => ['pay_id' => $payment->__get('id')]];
        }

        save_log('mercadopago_freeze', '预授权失败:');
        save_log('mercadopago_freeze', $payment->error());
        save_log('mercadopago_freeze', $params);
        return ['code' => 0, 'msg' => lang("支付失败")];
    }

    //预授权扣款
    public function checkout($params)
    {
        MercadoPago\SDK::setAccessToken('TEST-6691522837724442-120802-b9e34191c218b8dbe2cb6ddbe0b24dd2-662227956');
        $payment = MercadoPago\Payment::find_by_id($params['payment_id']);
        $payment->capture = true;
        $payment->transaction_amount = $params['amount'];
        $payment->update();

        save_log('mercadopago_checkout', $payment->update());
        save_log('mercadopago__checkout_error', $payment->error());
    }


    /**
     * 修改客户信息
     * $params id           id
     * $params card_num     卡号
     * $params first_name   持卡人名
     * $params last_name    持卡人姓
     * $params area_code    区号
     * $params phone_number 手机号
     * $params type         标识
     * $params number       标识/安全码
     * $params zip_code     邮政编码
     * $params street_name  详细地址
     * $params email        邮箱
     */
    public function updateCard()
    {
        $id           = input('id', '');
        $card_num     = input('card_num', '');
        $first_name   = input('first_name', '');
        $last_name    = input('last_name', '');
        $area_code    = input('area_code', '');
        $phone_number = input('phone_number', '');
        $type         = input('type', '');
        $number       = input('number', '');
        $zip_code     = input('zip_code', '');
        $street_name  = input('street_name', '');
        $email        = input('email', '');
        $expiration_month        = input('expiration_month', '');
        $expiration_year        = input('expiration_year', '');
        if ( empty($id) ) {
            return ['code' => 0, 'msg' => lang('id is empty')];
        }
        $card = array();
        $card['email'] = $email;
        $card['card_num'] = $card_num;
        $card['first_name'] = $first_name;
        $card['last_name'] = $last_name;
        $card['type'] = $type;//身份证识别号
        $card['number'] = $number;//身份证
        $card['area_code'] = $area_code;
        $card['phone_number'] = $phone_number;
        $card['zip_code'] = $zip_code;
        $card['street_name'] = $street_name;
        $card['expiration_month'] = $expiration_month;
        $card['expiration_year'] = $expiration_year;
        $card['update_time'] = time();
        $result = $this->db->name('card')->where(['id' => $id])->update($card);
        if ( $result($id) ) {
            return ['code' => 1, 'msg' => '更新成功'];
        }

        return ['code' => 0, '更新失败'];

    }

    /**
     * 获取卡信息
     * @param $uid
     * @return mixed
     */
    public function getCardInfo($uid)
    {
        return $this->db->name('card')->where(['uid' => $uid])->find();
    }

    /**
     * 验证客户是否存在
     * @param $email
     * @return bool
     */
    public function getCustomerByEmail($email)
    {
        $customer_id = 0;
        $response = MercadoPago\SDK::get('/v1/customers/search?email='.$email);
        save_log('test_123',$response);
        save_log('test_123',$response['body']['results']);
        foreach ($response['body']['results'] as $key => $value)
        {
            save_log('test_123',"1-".$email);
            save_log('test_123',"2-".$value['email']);
            if ($email == $value['email']) {
                $customer_id = $value['id'];
            }
        }
        return $customer_id;
    }

    /**
     * 创建客户
     * @param $email
     * @return array
     */
    public function createCustomer($email)
    {
        $customer = new MercadoPago\Customer();
        $customer->email = $email;
        if ( $customer->save() ){
            return $customer->__get('id');
        }

        //创建客户失败
        save_log('mercadopago','create customer fail:'.$email);
        save_log('mercadopago',config('mercadoPago_access_token'));
        return false;
    }


    public function testPay($params)
    {

        //1242565082
        $payment = MercadoPago\Payment::find_by_id("1242573456");
        print_r($payment->__get('status'));
        print_r($payment);exit;

//        $customer = MercadoPago\Customer::find_by_id("1003617575-MJjKnoElwV9vKK");
//        $cards = $customer->__get('cards');
//        print_r($cards);exit;



        $payment = new MercadoPago\Payment();
        $payment->transaction_amount = $params['amount'];
        $payment->token = $params['token'];
        $payment->installments = 1;
        $payment->payer = array(
            "type" => "customer",
            "id" => $params['customerId']
        );
        $payment->notification_url = "https://cdb.gobattery.cl/app/callback/mercadopago";
        var_dump($payment->save());
        print_r($payment->error());
        $pay = MercadoPago\Payment::find_by_id($payment->__get('id'));
        print_r($pay);exit;
    }

    //绑卡
    function addCard($params)
    {

        $custId = $this->getCustomerByEmail($params['email']);
        if ( $custId ) {//验证邮箱
            save_log('card', '邮箱已存在换绑' . $params['email']);
            $customerId['data']['customer_id'] = $custId;
        }else{
            $customerId = $this->addCustomer($params['email']);
        }

        if ( empty($customerId['data']['customer_id']) ) {
            return ['code' => 0, 'msg' => '请输入正确的邮箱'];
        }

//            $customerId = "1004342443-UPJR9mN0kItVcY";
        $card = new MercadoPago\Card();
        $card->token = $params['cardToken'];//信用卡 token
        $card->customer_id = $customerId['data']['customer_id'];//客户id
        if ( $card->save() ) {
            return ['code' => 1, 'data' => ['customer_id' => $customerId['data']['customer_id'], 'card_id' => $card->__get('id'),'card_token' => $params['cardToken'],'email' => $params['email']]];
        }
        save_log('mercadopago_error', '绑卡失败:');
        save_log('mercadopago_error', $card->error());
        save_log('mercadopago_error', $params);
        return ['code' => 0, 'msg' => '绑卡失败，暂时不支持该卡'];
    }

    //创建客户
    function addCustomer($email)
    {
        if ( empty($email) ) {
            return ['code' => 0, 'msg' => lang('邮箱为空')];
        }

        $customer = new MercadoPago\Customer();
        $customer->email = $email;

        if ( $customer->save() ) {
            return ['code' => 1, 'data' => ['customer_id' => $customer->__get('id')]];
        }
        save_log('mercadopago_error', '创建客户失败:');
        save_log('mercadopago_error', $customer->error() . $email);
        return ['code' => 0, 'msg' => lang("操作失败")];
    }

    function payNotify($payId)
    {
        if ( empty($payId) ) {
            save_log('mercadopago_error',   '支付id为空');
            return ['code' => 0, 'msg' => '支付id为空'];
        }
        $payment = MercadoPago\Payment::find_by_id($payId);
        $status = $payment->__get("status");
        if ($status == 'approved') {
            save_log('mercadopago', '支付成功:'.$payId . ':' . $status);
            return ['code' => 1, 'msg' => '支付成功'];
        }
        save_log('mercadopago', '支付失败:'.$payId . ':' . $status);
        return ['code' => 0, 'msg' => '支付失败:'.$payment->__get('status_detail')];
    }

    //删除卡
    function deleteCard($params = [])
    {
        $card = new MercadoPago\Card();
        $card->customer_id = $params['customer_id'];
        $card->id = $params['card_id'];
        if ( $card->delete() ) {
            return ['code' => 1, 'msg' => '删除成功'];
        }

        save_log('mercadopago_error', '删除卡失败:');
        save_log('mercadopago_error', $params);
        save_log('mercadopago_error', $card->error());
        return ['code' => 0, 'msg' => lang("操作失败")];
    }

    //获取卡详情
    function cardInfo($customerId = '')
    {
        $customer = MercadoPago\Customer::find_by_id($customerId);
        $cards = $customer->__get('cards');

        if ( !empty($cards) ) {
            return ['code' => 1, 'data' => $cards[0], 'msg' => '卡信息'];
        }

        $list = [];
        return ['code' => 0, 'data' => $list, 'msg' => '卡不存在'];
    }

    //退款
    function refund($amount = 0, $payId = '')
    {
        if ( config('debug_refud') ) {
            return ['code' => 1,  'msg' => '退款成功'];
        }

        $payment = MercadoPago\Payment::find_by_id($payId);

        if ( $payment->refund($amount) ) {
            return ['code' => 1,  'msg' => '退款成功'];
        }

        save_log('refund', '退款失败:'.$amount);
        save_log('refund', $payId);
        save_log('refund', $payment->error());
        return ['code' => 0, 'msg' => lang("退款失败")];
    }
}