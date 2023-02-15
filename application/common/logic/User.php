<?php

namespace app\common\logic;

use app\app\validate\User as UserValidate;
use think\Log;

/**
 * 统计
 * @package app\common\logic
 */
class User extends Common
{
    /**
     * 用户注册
     */
    public function register()
    {
        $area = input('area', '', 'trim');
        $code = input('code', '', 'trim');
        $phone = input('phone', '', 'intval');
        $type = input('type', 'register', '');
        $password = input('password', '', 'trim');
        $nick = input('nick', '', 'trim');
        $email = input('email');
        $openid = input('openid');

        if ( empty($openid) ) {
            //验证 验证码
            $result = \think\Loader::model('ValidateToken', 'logic')
                ->checkCode($phone, 'register', $code, true);
            if ('0' == $result['code']) {
                return ['code' => 0, 'msg' => $result['msg']];
            }
        }

        if (!in_array($type, config('app_name'))) {
            return ['code' => 0, 'msg' => lang('类型错误')];
        }

        if ( empty($password) && empty($openid) ) {
            return ['code' => 0, 'msg' => lang('密码不能为空')];
        }

        if ( !empty($phone) ) {
            //验证账户是否已注册
            $check_mobile = $this->db->name('user')->where(['mobile' => $phone])->value('id');
        }

        if ( !empty($openid) ) {
            $check_mobile = $this->db->name('user')->where(['openid' => $openid])->value('id');
        }

        if ( !empty($check_mobile) ) {
            $userArr = db('user')->where(['id' => $check_mobile])->find();
            $userArr['is_first_login'] = 1;


            //注册成功 自动登陆
            return $this->login($userArr);
        }


        $user = array(
            'area' => $area,
            'mobile' => $phone,
            'openid' => $openid,
            'nick' => $nick,
            'email' => $email,
            'password' => md123($password),
            'create_time' => time(),
            'last_login' => time(),
            'app_type' => config('app_type.'.$type),
            'nickCode' => base64_encode(config('customer.nick'))
        );

        //生成唯一邀请码
        $user['inviter_code'] = $this->getInviteCode();

        //注册
        $uid = $this->db->name('user')->insertGetId($user);

        if ( empty($uid) ) {
            return ['code' => 0, 'msg' => lang('注册失败')];
        }

        $auth_data = [
            'uid' => $uid,
            'type' => $user['app_type'],
            'openid' => '',
            'update_time' => time()
        ];
        $this->db->name('auth_account')->insert($auth_data);

        //会员卡号
        $member_id = 10181111 + $uid;
        $this->db->name('user')
            ->where(['id' => $uid])
            ->update([
                'member_id' => $member_id,
                'last_login' => time()
            ]);

        //首次登陆
        $userArr = db('user')->where(['id' => $uid])->find();
        $userArr['is_first_login'] = 1;


        //注册成功 自动登陆
        return $this->login($userArr);
    }

    public function invitation()
    {
        $inviter_code = input('inviter_code', '');

        //验证是否 已经被邀请过
        $verify = $this->db->name('user_inviter')->where(['be_inviter_id' => $this->auth_info['id']])->find();
        if ( $verify ) {
            return ['code' => 0, 'msg' => lang('邀请失败')];
        }

        $id = $this->db->name('user')->where(['inviter_code' => $inviter_code])->value('id');

        if ( empty($id) ) {
            save_log('inviter','邀请出错:'.$inviter_code);
            return ['code' => 0, 'msg' => lang('邀请码无效')];
        }
        $result = $this->inviter($inviter_code,$this->auth_info['id'],$id);

        if ( $result['code'] == 1 ) {
            return ['code' => 1, 'msg' => lang('邀请成功')];
        }

        return ['code' => 0, 'msg' => lang('邀请失败')];
    }

    function inviter($code = '', $uid = 0,$id = 0)
    {
        $params['inviter_code'] = $code;
        $params['be_inviter_id'] = $uid;
        $params['inviter_id'] = $id;
        $params['free_time'] = config('inviter_free');
        $params['create_time'] = time();

        $this->db->name('user_inviter')->insertGetId($params);
        $this->db->name('user')->where(['id' => $id])->setInc('point_time',$params['free_time']);
        return ['code' => 1, 'msg' => '操作成功'];
    }

    /**
     * 获取唯一邀请码
     */
    private function getInviteCode()
    {
        $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand = $code[rand(0,25)]
            .strtoupper(dechex(date('m')))
            .date('d')
            .substr(time(),-5)
            .substr(microtime(),2,5)
            .sprintf('%02d',rand(0,99));
        for(
            $a = md5( $rand, true ),
            $s = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            $d = '',
            $f = 0;
            $f < 7;
            $g = ord( $a[ $f ] ),
            $d .= $s[ ( $g ^ ord( $a[ $f + 8 ] ) ) - $g & 0x1F ],
            $f++
        );

        $verify = $this->db->name('user')->where(['inviter_code' => $d])->value('inviter_code');

        if ( !empty($verify) ) {
            $this->getInviteCode();
        }

        return $d;
    }

    /**
     * 登录
     */
    public function login($data = array())
    {
        if (empty($data)) {
            $data = [
                'openid' => input('openid', '', 'trim'),
                'type' => input('type', 'phone', ''),
                'area'  => input('area', '', ''),
                'password'  =>  input('password', '', 'trim'),
                'phone'    => input('phone', '', 'trim'),
            ];
            $validate = new UserValidate();
            $scene = $data['type'] . '_login';
            if (!$validate->scene($scene)->check($data)) {
                Log::notice('用户登录参数错误:'.$validate->getError());
                return ['code' => 0, 'msg' => 'parm error'];
            }
            if ($data['type'] == 'phone') {
                $mobile = $data['phone'];
                $user_arr = db('user')->where(['mobile' => $mobile])->find();
                if (empty($user_arr) || $user_arr['password'] != md123($data['password'])) {
                    return ['code' => 0, 'msg' => lang('账户不存在或密码错误')];
                }
            } else {
                $app_type = config('app_type')[$data['type']];
                if (empty($app_type)) {
                    return ['code' => 0, 'msg' => lang('类型错误')];
                }
                $user_arr = db('user')->where(['openid' => $data['openid'], 'app_type' => $app_type]);
            }
            if (1 == $user_arr['is_display']) {
                return ['code' => 0, 'msg' => lang('账号被禁用')];
            }
        } else {
            $user_arr = $data;
            $ret['is_first_login'] = true;
        }
        $token = randCode(16);
        //更新最后登录时间
        db('user')->where(['id' => $user_arr['id']])->update(['last_login' => time(), 'token' => $token]);
        cache("customer-token:{$this->oid}:{$user_arr['token']}", null);
        cache("customer-token:{$this->oid}:{$token}", $user_arr);
        $ret = ['token' => $token ];
        return ['code' => 1, 'data' => $ret];
    }

    /**
     * 忘记密码
     */
    public function forgetPassword()
    {
        $data = [
            'area' => input('area', '', ''),
            'code' => input('code', '', 'trim'),
            'phone' =>  input('phone', '', 'intval'),
            'password' => input('password', '', 'trim'),
            'repassword' => input('repassword', '', 'trim'),
        ];
        $validate = new UserValidate();
        if(!$validate->scene('forget')->check($data)){
            Log::notice('忘记密码输入参数错误:'.$validate->getError());
            return ['code' => 0, 'msg' => 'param error'];
        }
        $mobile = $data['phone'];
        $user = $this->db->name('user')->where(['mobile' => $mobile])->find();
        if (empty($user)) {
            return ['code' => 0, 'msg' => lang('该账号尚未注册，请先注册')];
        }
        $result = \think\Loader::model('ValidateToken', 'logic')
            ->checkCode($mobile, 'repassword', $data['code'], true);

        if (1 != $result['code']) {
            !isset($result['data']) && $result['data'] = [];
            return ['code' => 0, 'msg' => $result['msg']];
        }
        $this->db->name('user')->where(['mobile' => $mobile])->update(['password' => md123($data['password'])]);
        return ['code' => 1, 'msg' => 'ok'];
    }

    /**
     * @param $uid
     * TODO 获取用户信息
     */
    public function  getInfo($uid){
       return $this->db->name('user')->where(['id' => $uid])->field('password', true)->find();
    }
}
