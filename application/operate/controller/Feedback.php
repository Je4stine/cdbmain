<?php

namespace app\operate\controller;

class Feedback extends Common
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        $page_size = input('page_size', 20, 'intval');
        $page_size < 1 && $page_size = 20;
        $where = [];
        $pageParam = ['query' => []];
        $status = input('status', 0, 'intval');
        if (!empty($status) && in_array($status, [1, 2])) {
            $where['a.status'] = $status - 1;
            $pageParam['query']['status'] = $status;
        }

        $query = $this->db->name('feedback')
            ->alias('a')
            ->join("user b", 'a.uid = b.id', 'LEFT')
            ->field('a.*,b.member_id,b.nickCode as nick')
            ->where($where)
            ->order('id DESC')
            ->paginate($page_size, false, $pageParam);
        //$paginate = $query->render();
        $total = $query->total();
        $list = $query->all();
        foreach ($list as $k => $v) {
            $list[$k]['nick'] = $this->getCustomerNick($v['nick']);
            $list[$k]['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
            $list[$k]['update_time'] = empty($v['update_time']) ? '' : date("Y-m-d H:i:s", $v['update_time']);
            $list[$k]['status_text'] = empty($v['status']) ? lang('待处理') : lang('已处理');
            $list[$k]['status'] = $v['status'] + 1;
            unset($list[$k]['images']);
        }
        return $this->successResponse(['total' => $total, 'list' => $list], lang('获取成功'));
    }

    //用户反馈
    function detail()
    {
        $id = input('id', 0, 'intval');
        $info = $this->db->name('feedback')
            ->where(['id' => $id])
            ->find();
        !$info && $this->errorResponse(0, lang('数据不存在'));
        $info['images'] = json_decode($info['images'], true);
        return $this->successResponse($info, lang('获取成功'));
    }

    function process()
    {
        $id = input('id', 0, 'intval');
        $remark = input('remark', '', 'trim');
        $info = $this->db->name('feedback')
            ->where(['id' => $id])
            ->find();
        !$info && $this->errorResponse(0, lang('数据不存在'));
        $this->db->name('feedback')->where(['id' => $id])->update(['status' => 1, 'remark' => $remark, 'update_time' => time()]);
        return $this->successResponse([],  lang('操作成功'));
    }

}