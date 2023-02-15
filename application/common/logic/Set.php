<?php

namespace app\common\logic;

use Qcloud\cos\Api;
use think\Request;


/**
 * 运营商设置
 * @package app\common\logic
 */
class Set extends Common
{

    public function _initialize()
    {
        parent::_initialize();
    }

    public function basic()
    {
        $params = input('post.');
        if ($_FILES['logo']['name']) {
            $file = Request::instance()->file('logo');
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            $file = $info->getSaveName();
            if ($file) {
	            $ret = (new Upload())->uploadFileCloud($file);
	            if (!empty($ret)) {
		            $params['logo'] = $ret;
	            }
            }
        }
        $config = $this->db->name('config')->where(['type' => 'basic_info'])->find();
        $config = json_decode($config['data'], true);
        $params = array_merge($config, $params);
        $params = json_encode($params, JSON_UNESCAPED_UNICODE);
        $this->db->name('config')
            ->where(['type' => 'basic_info'])
            ->update(['data' => $params, 'update_time' => time()]);
        $this->clearCache();
        $this->operateLog(0, '更新基础信息');
        return ['code' => 1, 'msg' => lang('更新成功')];
    }

    function clearCache()
    {
        cache("operator-config:{$this->oCode}", null);
    }


    //微信支付

    public function charge()
    {
        $data = [];
        $recharge = input('post.recharge_amount/a', []);
        $data['recharge_amount'] = implode('|', $recharge);
        $data['deposit'] = input('deposit');
        $data['deposit_delay'] = input('deposit_delay');
        $data['billingtime'] = input('billingtime', 0, 'intval');
        $data['amount'] = input('amount');
        $data['billingunit'] = input('billingunit');
        $data['freetime'] = input('freetime');
        $data['ceiling'] = input('ceiling');
        $data['device_price'] = input('device_price');
        //线充
        $data['wired_amount'] = [];
        // $wired_amount = input('post.wired_amount/a', []);

        $wireds = input('post.wired_amount');
        $wireds = json_decode($wireds, true);
        if (is_array($wireds) && !empty($wireds)) {
            $wired_amount = array_column($wireds, 'amount', 'time');
        }

        $times = [1 => '1' . lang('小时'), 2 => '2' . lang('小时'), 3 => '3' . lang('小时'), 4 => '4' . lang('小时'), 5 => '5' . lang('小时'), 6 => '6' . lang('小时'), 12 => '12' . lang('小时')];
        foreach ($times as $k => $v) {
            $data['wired_amount'][] = ['time' => $k, 'text' => $v, 'amount' => $wired_amount[$k]];
        }
        if (!preg_match("/^[1-9][0-9]*$/", $data['billingtime']) || $data['billingtime'] < 1) {
            return ['code' => 0, 'msg' => lang("计费时间需为正整数")];
        }

        $battery_power = input('battery_power', 0, 'intval');
        if ($battery_power < 1 || $battery_power > 100) {
            return ['code' => 0, 'msg' => lang('请输入正确电量值')];
        }

        $withdrawal_rate = input('withdrawal_rate', 0, 'floatval');
        $withdrawal_amount = input('withdrawal_amount', 0, 'intval');
        $withdrawal_day = input('withdrawal_day', 0, 'intval');
        if (!preg_match("/^[1-9][0-9]*$/", $withdrawal_amount) || $withdrawal_amount < 1) {
            return ['code' => 0, 'msg' => lang("提现金额须为正整数")];
        }
        if (!preg_match("/^[1-9][0-9]*$/", $withdrawal_day) || $withdrawal_day < 1) {
            return ['code' => 0, 'msg' => lang("每日最多提现金额须为正整数")];
        }
        $withdrawal_type = input('post.withdrawal_type');
        $withdrawal_type = explode(",", $withdrawal_type);
        if (empty($withdrawal_type)) {
            return ['code' => 0, 'msg' => lang("请选择提现渠道")];
        }

        $params = json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->db->name('config')
            ->where(['type' => 'charge_info'])
            ->update(['data' => $params, 'update_time' => time()]);

        $data = [
            'rate' => $withdrawal_rate,
            'amount' => $withdrawal_amount,
            'max_amount' => $withdrawal_day,
            'type' => $withdrawal_type,
            'intro' => input('withdrawal_intro')
        ];
        $params = json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->db->name('config')
            ->where(['type' => 'withdrawal'])
            ->update(['data' => $params, 'update_time' => time()]);

        $this->db->name('config')
            ->where(['type' => 'battery_power'])
            ->update(['data' => $battery_power, 'update_time' => time()]);

        $this->clearCache();
        $this->operateLog(0, '更新收费标准');
        return ['code' => 1, 'msg' => lang('更新成功')];
    }

    //微信小程序
    public function wechatApp($oid)
    {
        $path = str_replace('\\', '/', EXTEND_PATH . 'cert/wechat/' . $oid . '/');
        $params = [
            'mchid' => input('mchid', '', 'trim'),
            'mchkey' => input('mchkey', '', 'trim'),
            'appid' => input('appid', '', 'trim'),
            'appsecret' => input('appsecret', '', 'trim'),
        ];
        empty($params['appid']) && $this->error(lang('请输入小程序ID'));
        empty($params['appsecret']) && $this->error(lang('请输入小程序密钥'));
        if ($_FILES['sslcert_path']['name']) {
            $file = Request::instance()->file('sslcert_path');
            $file_info = $file->move($path, '');
            $file_info = $path . $file_info->getSaveName();
            $params['sslcert_path'] = $file_info;
        }
        if ($_FILES['sslkey_path']['name']) {
            $file1 = Request::instance()->file('sslkey_path');
            $file1_info = $file1->move($path, '');
            $file1_info = $path . $file1_info->getSaveName();
            $params['sslkey_path'] = $file1_info;
        }
        $config = $this->db->name('config')->where(['type' => 'wechat_app'])->find();
        $config = json_decode($config['data'], true);
        $type = !empty($config['sslkey_path']) ? 'edit' : 'add';

        $validate = \think\Loader::validate('Wechatpay');
        if (!$validate->scene($type)->check($params)) {
            $this->error(lang($validate->getError()));
        }
        $params = array_merge($config, $params);

        $wechat_credit = input('wechat_credit', 'false', 'trim');
        $wechat_credit != 'true' && $wechat_credit = 'false';
        if ($wechat_credit == 'true') {
            $params['v3_secret'] = input('v3_secret', '', 'trim');
            $params['service_id'] = input('service_id', '', 'trim');
            $params['api_serial_no'] = input('serial_no', '', 'trim');
            empty($params['v3_secret']) && $this->error('请输入V3密钥');
            empty($params['service_id']) && $this->error('请输入service ID');
            empty($params['api_serial_no']) && $this->error('请输入证书号');
        }

        $params = json_encode($params, JSON_UNESCAPED_UNICODE);
        $this->db->name('config')
            ->where(['type' => 'wechat_credit'])
            ->update(['data' => $wechat_credit, 'update_time' => time()]);
        $this->db->name('config')
            ->where(['type' => 'wechat_app'])
            ->update(['data' => $params, 'update_time' => time()]);
        $this->clearCache();
        $this->operateLog(0, lang('更新微信小程序'));
        return ['code' => 1, 'msg' => lang('更新成功')];
    }

    public function alipayApp($oid)
    {

        $path = str_replace('\\', '/', EXTEND_PATH . 'cert/alipay/' . $oid . '/');

        $params = [
            'ali_appid' => input('ali_appid', '', 'trim'),
            'payee_user_id' => input('payee_user_id', '', 'trim'),
        ];
        empty($params['ali_appid']) && $this->error(lang('请输入小程序ID'));
        if ($_FILES['ali_public_key']['name']) {
            $file = Request::instance()->file('ali_public_key');
            $file_info = $file->move($path, '');
            $file_info = $path . $file_info->getSaveName();
            $params['ali_public_key'] = $file_info;
        }
        if ($_FILES['ali_private_key']['name']) {
            $file1 = Request::instance()->file('ali_private_key');
            $file1_info = $file1->move($path, '');
            $file1_info = $path . $file1_info->getSaveName();
            $params['ali_private_key'] = $file1_info;
        }
        $config = $this->db->name('config')->where(['type' => 'alipay_app'])->find();
        $config = json_decode($config['data'], true);
        if (!$config['ali_public_key']) {
            !isset($params['ali_public_key']) && $this->error(lang('请上传公钥'));
            !isset($params['ali_private_key']) && $this->error(lang('请上传私钥'));
        }

        $params = array_merge($config, $params);

        $alipay_credit = input('alipay_credit', 'false', 'trim');
        $alipay_credit != 'true' && $alipay_credit = 'false';
        if ($alipay_credit == 'true') {
            $params['service_id'] = input('service_id', '', 'trim');
            empty($params['service_id']) && $this->error('请输入service ID');
        }

        $params = json_encode($params, JSON_UNESCAPED_UNICODE);

        $this->db->name('config')
            ->where(['type' => 'alipay_credit'])
            ->update(['data' => $alipay_credit, 'update_time' => time()]);
        $this->db->name('config')
            ->where(['type' => 'alipay_app'])
            ->update(['data' => $params, 'update_time' => time()]);
        $this->clearCache();
        $this->operateLog(0, lang('更新支付宝小程序'));
        return ['code' => 1, 'msg' => lang('更新成功')];
    }

    //协议内容更新

    public function sms()
    {
        $params = [
            'provider' => 1,
            'type' => 1,
            'time' => input('time'),
            'appid' => input('appid'),
            'appkey' => input('appkey'),
            'template_id' => input('template_id'),
            'sign_name' => input('sign_name'),
            'param' => input('param'),
            'code_name' => input('code_name'),
            'time_name' => input('time_name'),
        ];


        $validate = \think\Loader::validate('SmsTemplate');
        if (!$validate->check($params)) {
            $this->error(lang($validate->getError()));
        }
        $params = json_encode($params, JSON_UNESCAPED_UNICODE);
        $this->db->name('config')
            ->where(['type' => 'sms_template'])
            ->update(['data' => $params, 'update_time' => time()]);
        $this->clearCache();
        $this->operateLog(0, '更新短信模板');
        return ['code' => 1, 'msg' => lang('更新成功')];
    }

    public function agreement()
    {
        $arr = [
            'registration_agreement' => lang('注册协议'),
            'recharge_agreement' => lang('充值协议'),
            'user_agreement' => lang('用户协议'),
            'privacy_agreement' => lang('隐私协议'),
        ];
        $field = input('post.field');
        $value = input('post.' . $field);
        if (!isset($arr[$field])) {
            return ['code' => 0, 'msg' => lang('更新失败')];
        }
        $this->db->name('config')
            ->where(['type' => $field])
            ->update(['data' => $value, 'update_time' => time()]);
        $this->operateLog(0, "更新注册协议");
        return ['code' => 1, 'msg' => lang('更新成功')];
    }


    public function b2c()
    {
        $value = input('b2c');
        $this->db->name('config')
            ->where(['type' => 'b2c'])
            ->update(['data' => $value, 'update_time' => time()]);
        $this->operateLog(0, "b2c");
        $this->clearCache();
        return ['code' => 1, 'msg' => lang('更新成功')];
    }

    public function c2b()
    {
        $value = input('c2b');
        $this->db->name('config')
            ->where(['type' => 'c2b'])
            ->update(['data' => $value, 'update_time' => time()]);
        $this->operateLog(0, "c2b");
        $this->clearCache();
        return ['code' => 1, 'msg' => lang('更新成功')];
    }


    public function orderAdv()
    {
//        $params = [
//            'remark' => input('post.remark'),
//            'start_time' => input('post.start_time'),
//            'end_time' => input('post.end_time'),
//        ];
        $params = ['second' => input('post.second', 3, 'intval')];
        $params['second'] < 3 && $params['second'] = 3;
        $params['second'] > 60 && $params['second'] = 60;
        $info = $this->db->name('config')->where(['type' => 'order_adv'])->find();
        $info = json_decode($info['data'], true);
        $file_id = input('post.image');
        $file = \think\Loader::model('Upload', 'logic')->useFile($file_id, 'system');
        if ($file['code'] != 1 && empty($info['file'])) {
            $this->error(lang('请上传广告'));
        }
        if ($file['file']) {
            $params['file'] = config('qcloudurl') . $file['file'];
            $params['size'] = $file['size'];
        } else {
            $params['file'] = $info['file'];
            $params['size'] = $info['size'];
        }

        $params = json_encode($params, JSON_UNESCAPED_UNICODE);
        $this->db->name('config')
            ->where(['type' => 'order_adv'])
            ->update(['data' => $params, 'update_time' => time()]);

        $this->clearCache();
        $this->operateLog(0, '订单广告');
        return ['code' => 1, 'msg' => lang('更新成功')];
    }

    public function locationIcon()
    {
        $params = [
            'start_time' => input('post.start_time'),
            'end_time' => input('post.end_time'),
        ];
        $info = $this->db->name('config')->where(['type' => 'location_icon'])->find();
        $info = json_decode($info['data'], true);
        $file_id = input('post.image');
        $file = \think\Loader::model('Upload', 'logic')->useFile($file_id, 'system');
        if ($file['code'] != 1 && empty($info['file'])) {
            $this->error(lang('请上传图标'));
        }
        if ($file['file']) {
            $params['file'] = config('qcloudurl') . $file['file'];
            $params['size'] = $file['size'];
        } else {
            $params['file'] = $info['file'];
            $params['size'] = $info['size'];
        }
        $params = json_encode($params, JSON_UNESCAPED_UNICODE);
        $this->db->name('config')
            ->where(['type' => 'location_icon'])
            ->update(['data' => $params, 'update_time' => time()]);

        $this->clearCache();
        $this->operateLog(0, '定位图标');
        return ['code' => 1, 'msg' => lang('更新成功')];
    }


}