<?php
//商家列表
namespace app\operate\controller;

use app\operate\controller\Common;
use think\Request;
use think\Db;
use think\File;
use think\Config;
use think\Session;
use think\Cookie;
use Godok\Org\FileManager;
use Qcloud\cos\Api;

class Seller extends Common
{

    /**
     * 列表
     */
    public function index()
    {
        $page_size = input('page_size', 20, 'intval');
        $data = \think\Loader::model('Seller', 'logic')->sellerList([], $page_size, true);
        return $this->successResponse($data, lang('获取成功'));
    }

    /**
     * 列表弹框
     */
    public function listDialog()
    {
        $page_size = input('page_size', 20, 'intval');
        $data = \think\Loader::model('Seller', 'logic')->sellerList([], $page_size, true);
        return $this->successResponse($data, lang('获取成功'));
    }


    /**
     * 实时上传图片
     */
    public function fileUpload()
    {
        $logic = \think\Loader::model('Upload', 'logic');
        $result = $logic->uploadImage('img_file');
        return $this->successResponse($result);
    }

    /**
     * 添加商户
     */
    public function add()
    {
        !Request::instance()->isPost() && $this->error(lang('非法请求'));
        $ret =  \think\Loader::model('Seller', 'logic')->add([]);
        if($ret['code'] === 0){
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
        
    }

    /**
     * TODO 商户详情
     */
    function detail()
    {
        $id = input('id', 1, 'intval');
        $logic = \think\Loader::model('Seller', 'logic');
        if($id > 0 ){
            $seller = $this->db->name('seller')->where(['id' => $id, 'not_delete' => 1])->find();
            if (!$seller) {
                return $this->errorResponse(0, lang('数据不存在'));
            }
            $seller['name'] = json_decode($seller['name'], true);
        }else{
            $seller = [
                'shop_start' => '900',
                'shop_end' => '2200',
                'billing_set' => json_encode([]),
            ];
        }
        $seller = $logic->getDetail($seller);
        unset($seller['password'], $seller['create_time'],$seller['update_time'], $seller['billing_set']);
//        !empty($seller['logo']) && $seller['logo'] = config('qcloudurl') . $seller['logo'];
//        !empty($seller['picture']) && $seller['picture'] = config('qcloudurl') . $seller['picture'];
        return $this->successResponse($seller, lang('获取成功'));

    }


    /**
     * 修改商户
     */
    public function edit($id)
    {
        $logic = \think\Loader::model('Seller', 'logic');
        $info = $this->db->name('seller')->where(['id' => $id, 'not_delete' => 1])->find();
        !$info && $this->error(lang('信息不存在'));
        $ret =  \think\Loader::model('Seller', 'logic')->edit($info, []);
        if($ret['code'] === 0){
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }


    /**
     *删除商户
     */
    public function delete($id = '')
    {
        $ret =  \think\Loader::model('Seller', 'logic')->delete($id, []);
        if($ret['code'] === 0){
            $this->errorResponse(0, lang($ret['msg']));
        }
        $this->successResponse([], lang($ret['msg']));
    }


    /**
     *检测手机号
     */
    public function checkPhone()
    {
        $phone = input('phone');
        $id = input('id');
        $num = $this->db->name('seller')
            ->where(['phone' => $phone, 'id' => ['neq', $id]])
            ->count();
        return json(['code' => 1, 'msg' => $num]);
    }

    /**
     * 加载业务员列表
     */
    public function employeeList()
    {
        $id = input('id', 0, 'intval');
        $employeeList = $this->db->name('agency')->field('id,name,brokerage')->where(['not_delete' => 1, 'parent_id' => $id, 'type' => 2])->select();
        return $this->successResponse($employeeList);
    }

    /**
     * 代理数据
     */
    public function agencyData()
    {
        $id = input('id', 0, 'intval');
        $result = \think\Loader::model('Agency', 'logic')->agencyData($id);
        return $result;
    }

    public function trend()
    {
        $id = input('id', 0, 'intval');
        $id = $this->db->name('seller')->where(['id' => $id])->value('id');
        !$id && $this->error(lang('信息不存在'));
        $data = \think\Loader::model('Stat', 'logic')->sellerTrend($id);
        $this->successResponse($data);
    }
}