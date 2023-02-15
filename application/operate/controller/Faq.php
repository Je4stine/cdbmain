<?php

namespace app\operate\controller;

use app\operate\controller\Common;
use think\Log;
use think\Request;
use think\Model;
use think\Db;
use think\Config;
use think\Controller;
use think\Session;
use think\Cookie;
use Godok\Org\Auth;

class Faq extends Common
{
    public function _initialize()
    {
        parent::_initialize();
        $this->logic = \think\Loader::model('Faq', 'logic');
    }

    public function index()
    {
        $page_size = input('page_size', 20, 'intval');
        $data = $this->logic->faqList($page_size, true);
        return $this->successResponse($data, lang('常见问题'));
    }


    public function add()
    {
        !Request::instance()->isPost() && $this->errorResponse(0, lang('非法请求'));
        $ret =  $this->logic->add();
        if ($ret['code'] === 0) {
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }

    public function edit()
    {
        $id = input('id', 12, 'intval');
        !Request::instance()->isPost() && $this->errorResponse(0, lang('非法请求'));
        $info = $this->db->name('faq')->where(['id' => $id, 'not_delete' => 1])->find();
        if (!$info) {
            $this->errorResponse(0, lang('信息不存在'));
        }
        $ret = $this->logic->edit();
        if ($ret['code'] === 0) {
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }

    public function detail()
    {
        $id = input('id', 12, 'intval');
        $info = $this->db->name('faq')->where(['id' => $id, 'not_delete' => 1])->find();
        if (!$info) {
            $this->errorResponse(0, lang('信息不存在'));
        }
        $this->successResponse($info, lang('常见问题'));
    }

    public function delete()
    {
        $id = input('id', 12, 'intval');
        $ret = $this->logic->delete($id);
        if ($ret['code'] === 0) {
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }

    /**
     * 获取语言列表
     */
    public function langList(){
        $data = config('lang_data');
        $ret = [];
        foreach($data as $k => $val){
            $tmp['value'] = $val['title'];
            $tmp['label'] = $k;
            $ret[] = $tmp;
        }
        $this->successResponse($ret, lang('获取成功'));
    }
}
