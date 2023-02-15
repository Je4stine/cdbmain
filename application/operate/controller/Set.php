<?php

namespace app\operate\controller;

use think\Request;


/**
 * 系统设置
 * Class Set
 * @package app\operate\controller
 */
class Set extends Common
{
    public function _initialize()
    {
        parent::_initialize();
        $this->logic = \think\Loader::model('Set', 'logic');
    }


    public function info()
    {
        $allow = ['basic_info', 'charge', 'registration_agreement', 'recharge_agreement', 'user_agreement', 'privacy_agreement', 'wechat', 'alipay', 'sms_template', 'order_adv', 'location_icon', 'b2c', 'c2b'];
        $type = input('type', 'charge');
        if (empty($type) || !in_array($type, $allow)) {
            $this->error(lang('信息不存在'));
        }
        if (in_array($type, ['registration_agreement', 'recharge_agreement', 'user_agreement', 'privacy_agreement', 'b2c', 'c2b'])) {
            $config = $this->db->name('config')->where(['type' => $type])->find();
            return $this->successResponse($config['data'], $config['remark']);
        }
        if (in_array($type, ['basic_info', 'sms_template', 'location_icon', 'order_adv'])) {
            $config = $this->db->name('config')->where(['type' => $type])->find();
            $config['data'] = json_decode($config['data'], true);
            $type == 'basic_info' && $config['data']['logo'] = config('qcloudurl').$config['data']['logo'];
            return $this->successResponse($config['data'], $config['remark']);
        }
        if ('charge' == $type) {
            $config = $this->db->name('config')->where(['type' => 'charge_info'])->find();
            $config = json_decode($config['data'], true);
            $config['battery_power']= $this->db->name('config')->where(['type' => 'battery_power'])->value('data');
            $withdrawal = $this->db->name('config')->where(['type' => 'withdrawal'])->find();
            $withdrawal = json_decode($withdrawal['data'], true);
            $config['withdrawal_rate'] = $withdrawal['rate'];
            $config['withdrawal_amount'] = $withdrawal['amount'];
            $config['withdrawal_intro'] = $withdrawal['intro'];
            $config['withdrawal_type'] = $withdrawal['type'];
            $config['withdrawal_day'] = $withdrawal['max_amount'];
            return $this->successResponse($config, '收费信息');
        }
        if ('wechat' == $type) {
            $config = $this->db->name('config')->where(['type' => 'wechat_app'])->find();
            $config = json_decode($config['data'], true);
            $config['wechat_credit'] = $this->db->name('config')->where(['type' => 'wechat_credit'])->value('data');
            unset($config['certificate']);
            return $this->successResponse($config, '微信信息');
        }
        if ('alipay' == $type) {
            $config = $this->db->name('config')->where(['type' => 'alipay_app'])->find();
            $config = json_decode($config['data'], true);
            $config['alipay_credit'] = $this->db->name('config')->where(['type' => 'alipay_credit'])->value('data');
            return $this->successResponse($config, '支付宝信息');
        }
        return $this->error(lang('非法请求'));
    }


    //基本信息更新
    public function save()
    {
        if (!Request::instance()->isPost()) {
            return ['code' => 0, 'msg' => lang('非法请求')];
        }
        $type = input('type');
        $result = ['code' => 0, 'msg' => lang('非法请求')];
        switch ($type) {
            case 'basic_info':
                $result = $this->logic->basic();
                break;
            case 'charge':
                $result = $this->logic->charge();
                break;
            case 'agreement':
                $result = $this->logic->agreement();
                break;
            case 'order_adv':
                $result = $this->logic->orderAdv();
                break;
            case 'location_icon':
                $result = $this->logic->locationIcon();
                break;
            case 'b2c':
                $result = $this->logic->b2c();
                break;
            case 'c2b':
                $result = $this->logic->c2b();
                break;
        }
        return $result;
    }


    //小程序配置
    public function app()
    {
        if (!Request::instance()->isPost()) {
            return ['code' => 0, 'msg' => lang('非法请求')];
        }
        $type = input('type');
        if ('wechat' == $type) {
            return $this->logic->wechatApp($this->oid);
        }
        if ('alipay' == $type) {
            return $this->logic->alipayApp($this->oid);
        }
        return ['code' => 0, 'msg' => lang('操作失败')];


    }

    //短信设置
    public function sms()
    {
        if (!Request::instance()->isPost()) {
            return ['code' => 0, 'msg' => lang('非法请求')];
        }
        $ret = $this->logic->sms();
        $this->successResponse([], lang('操作成功'));
    }

    /**
     * 实时上传图片
     */
    public function fileUpload($type = 'image')
    {
        $logic = \think\Loader::model('Upload', 'logic');
        $result = $logic->uploadImage('img_file');
        if ($result['code'] == 1) {
            $file = $logic->uploadFileCloud($result['path'], 'system');
            if (!$file) {
                return '';
            }
            $result['path'] = config('qcloudurl') . $file;
        }
        $this->operateLog($this->auth_info['uid'],'文件上传');
        $this->successResponse($result, lang('操作成功'));
    }


    //操作日志
    public function log()
    {
        $page_size = input('page_size', 20, 'intval');
        $page_size < 1 && $page_size = 20;
        $query = $this->db->name('operator_log')->order('create_time desc')->paginate($page_size);
        $list = $query->all();
        $total = $query->total();
        if ($list) {
            $uids = array_column($list, 'user_id');
            $uids = array_unique($uids);
            $users = $this->db->name('operator_users')->where(['id' => ['IN', $uids]])->field('id,username')->select();
            $users = array_column($users, 'username', 'id');
            foreach ($list as $k => $v) {
                $list[$k]['name'] = $users[$v['user_id']];
                $list[$k]['querystring'] = lang($v['querystring']);
                $list[$k]['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
            }
        }
        return $this->successResponse(['total' => $total, 'list' => $list], lang('获取成功'));
    }
}
