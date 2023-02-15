<?php

namespace app\common\logic;

use app\common\logic\Common;
use think\Request;
use think\Db;
use think\File;
use think\Session;
use think\Cookie;
use Godok\Org\FileManager;

/**
 * 常见问题
 * @package app\common\logic
 */
class Faq extends Common
{

    public function _initialize()
    {
        parent::_initialize();
    }


    private function _getParams()
    {
        $params = [
            'id' => input('post.id', 0, 'intval'),
            'question' => input('post.question'),
            'answer' => input('post.answer'),
            'not_delete' => 1,
            'language' => input('language'),
        ];
        $check = $this->db->name('faq')
            ->where(['not_delete' => 1, 'question' => $params['question']])
            ->find();
        if ($check && $check['id'] != $params['id']) {
            $this->error(lang('该问题已存在'));
        }
        if ('' == $params['question']) {
            $this->error(lang('问题标题为空'));
        }
        if ('' == $params['answer']) {
            $this->error(lang('回答内容为空'));
        }
        if ('' == $params['language']){
        //    $this->error(lang('语言表示标识'));
        }
        return $params;
    }

    /**
     * 添加
     */
    public function add()
    {
        $params = $this->_getParams();
        $params['create_time'] = time();

        if ($id = $this->db->name('faq')->insertGetId($params)) {
            $this->operateLog($id, '添加常见问题');
            return ['code' => 1, 'msg' => lang('操作成功')];
        }
        return ['code' => 0, 'msg' => lang('操作失败')];
    }


    /**
     * 修改
     */
    public function edit()
    {
        $params = $this->_getParams();
        $params['update_time'] = time();

        if ($this->db->name('faq')->update($params) !== false) {
            $this->operateLog($params['id'], '修改常见问题');
            return ['code' => 1, 'msg' => lang('操作成功')];
        }
        return ['code' => 0, 'msg' => lang('操作失败')];
    }


    /**
     * 删除
     * @param $id 主键id
     * @return array|void
     */
    public function delete($id)
    {
        $info = $this->db->name('faq')->where(['id' => $id, 'not_delete' => 1])->find();
        if (!$info) {
            return ['code' => 1, 'msg' => lang('操作成功')];
        }
        $info['not_delete'] = 0;
        if ($this->db->name('faq')->update($info)) {
            $this->operateLog($info['id'], '删除常见问题');
            return ['code' => 1, 'msg' => lang('操作成功')];
        }
        return ['code' => 0, 'msg' => lang('操作失败')];
    }


    /**
     * 列表
     */
    public function faqList($pages = 20, $isReturn = false)
    {
        $where = ['not_delete' => 1];
        $pageParam = ['query' => []];

        //判断是否有关键字查询
        $keywords = input('keywords', '', 'trim');
        $language = input('language', '', 'trim');
        if ('' != $keywords) {
            $where['answer|question'] = ['LIKE', "%{$keywords}%"];
            $pageParam['query']['keywords'] = $keywords;
        }

        if ('' != $language) {
            $where['language'] = $language;
            $pageParam['query']['language'] = $language;
        }

        $data = $this->db->name('faq')
            ->where($where)
            ->order('id desc')
            ->paginate($pages, false, $pageParam);
        $total = $data->total();
        $list = $data->all();
        return ['total' => $total, 'list' => $list];
    }
}
