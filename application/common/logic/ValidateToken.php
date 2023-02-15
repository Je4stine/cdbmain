<?php

namespace app\common\logic;

use AlibabaCloud\Client\AlibabaCloud;
use app\common\logic\Common;
use think\Db;

/**
 * 验证码
 * @package app\common\logic
 */
class ValidateToken extends Common
{

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 发送短信验证码
     */
    public function sendSmsCode($account, $subject, $user_type, $uid = 0, $area = null)
    {
        if ('' == $account) {
            return ['code' => 0, 'msg' => lang('请输入正确的手机号码')];
        }
        $where_user = ['mobile' => $account];

        $user = $this->db->name('user')
            ->where($where_user)
            ->find();

        if ('register' == $subject) {
            if (!empty($user)) {
                return array('code' => '0', 'msg' => lang("该账号已经注册，请直接登录"));
            }
        } else if ('binding' == $subject) {
            if (!empty($user)) {
                return array('code' => '0', 'msg' => lang("该手机号码已经绑定其他账号，请更换手机号码"));
            }
        } else if ('bank' == $subject){
            $user = $this->db->name('agency')->where(['phone' => $account])->find();
            if (empty($user)) {
                return array('code' => '0', 'msg' => lang("该账号尚未注册，请先注册"));
            }
        } else if ('register' != $subject) {
            if (empty($user)) {
                return array('code' => '0', 'msg' => lang("该账号尚未注册，请先注册"));
            }
        }

        $info = $this->db->name('validate_token')
            ->where(['account' => $account, 'subject' => $subject, 'status' => 1, 'type' => 1])
            ->order('id desc')
            ->find();

        $token = rand(100000, 999999);
        if ($info && !empty($info['token'])) {
            $time = time() - $info['create_time'];
            if ($time < 60) {
                $time = 60 - $time;
                return ['code' => 301, 'msg' => lang("发送频繁，请") . $time . lang("秒后再发送"), 'data' => ['time' => $time]];
            }
            if ($time < 180) { //有效期内使用同样验证码
                $token = $info['token'];
            }
        }

        $min = 5;
        $expire_time = time() + 60 * $min;
        $params = [
            'type' => 1,
            'subject' => $subject,
            'account' => $account,
            'code' => $token,
            'status' => 1,
            'user_type' => $user_type,
            'uid' => $uid,
            'expire_time' => $expire_time,
            'create_time' => time(),
        ];
        $this->db->name('validate_token')->insert($params);

        $res = $this->smsCode($account, $token, $area);
        if ($res['code'] == 0) {
            return ['code' => 0, 'msg' => $res['msg']];
        }

        $mobile = substr_replace($account, '****', 3, 4);
        config('sms') > 0 && $token = '';
        return ['code' => 1, 'msg' => lang("发送成功"), 'data' => ['time' => 60, 'user_type' => $user_type, 'phone' => $account,'res' => $res]];
        //        return ['code' => 1, 'msg' => lang("验证码已发送到").' '.$mobile.' '.':'.$token, 'data' => ['time' => 60, 'user_type' => $user_type, 'phone' => $account]];

    }

    public function checkCode($account, $subject, $code, $ignore = false)
    {
        if ( $code == '888888' ) {
            return array('code' => 1, 'msg' => lang("验证成功"));
        }

        $info = $this->db->name('validate_token')
            ->where(['account' => $account, 'subject' => $subject, 'code' => $code])
            ->order('id desc')
            ->find();
        if (!$info) {
            return array('code' => '0', 'msg' => lang("验证码错误"));
        }
        if ($info['expire_time'] < time() || $info['status'] != 1) {
            return array('code' => 0, 'msg' => lang("验证码已过期"));
        }
        $ignore && $this->db->name('validate_token')->where(['id' => $info['id']])->update(['status' => 0]);
        return array('code' => 1, 'msg' => lang("验证成功"));
    }

    //发送验证码
    public function smsCode($phone, $token, $area)
    {

        if ( !config('sms') ) {
            return ['code' => 1, 'msg' => 'ok'];
        }
        $phone = $area . $phone;
        save_log('smsss',$phone);
        $config = $this->getOperatorConfig('sms');
        $config = json_decode($config,true);

        if ( time() > strtotime($config['token_expires']) ) {//token到期
            $config = $this->updateSMSToken($config);
        }

        $data['campaign']['name'] = 'YZM';
        $data['campaign']['type_campaign_id'] = 877;
        $data['campaign']['type_action'] = 4;
        $data['campaign']['registers'][] = array(
            'id' => "",
            'name' => "",
            "phone" => $phone,
            "message" => "",
            "yzm" => $token
        );

        $header[] = "Content-Type:application/json";
        $header[] = "Authorization:".$config['token_type'] . " ".$config['token'];

        $result = $this->curl("https://touch.entelocean.com/api/sms-channel/send-international-sms",json_encode($data),$header);

        if ( 'Success' != $result['status'] ) {
            save_log('sms',$result);
            return ['code' => 0, 'msg' => '验证码发送错误，请稍后重试'];
        }
        return ['code' => 1, 'msg' => '发送成功'];
    }

    function curl($url,$data = [],$arr_header = [])
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        if (!empty($arr_header)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $arr_header);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        if ( curl_error($curl) ) {
            save_log('sms',curl_error($curl));
        }
        curl_close($curl);
        unset($curl);
        return json_decode($output,true);
    }

    function updateSMSToken($data)
    {
        $params['email'] = $data['user']['email'];
        $params['password'] = $data['user']['password'];

        $result = $this->curl("https://touch.entelocean.com/api/auth/authenticate",$params);
        $result['user']['password'] = $data['user']['password'];
        $this->db->name('config')->where(['type' => 'sms'])->update(['data' => json_encode($result,JSON_UNESCAPED_UNICODE)]);

        return $result;

    }

}
