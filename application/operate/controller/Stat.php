<?php

namespace app\operate\controller;

class Stat extends Common
{


    public function index()
    {
        $date = $this->_assignInfo();
        $result = \think\Loader::model('Stat', 'logic')->operatorDay($date['start_date'], $date['end_date']);
        $this->successResponse($result, lang('获取成功'));
    }

    private function _assignInfo()
    {
        $logic = \think\Loader::model('Stat', 'logic');
        $time = input('time', 'custom', 'trim');
        $unit = "";

        $ranges = [
            'month' => lang('本月'),
            'lastMonth' => lang('上月'),
            'today' => lang('今天'),
            'yesterday' => lang('昨天'),
            'week' => lang('本周'),
            'lastWeek' => lang('上周'),
            'year' => lang('今年'),
            'lastYear' => lang('去年'),
        ];

        if (!isset($ranges[$time]) && 'custom' != $time) {
            $time = 'month';
        }

        if ('custom' == $time) {//自定义
            $start = $logic->dateFormat(input('start'));
            $end = $logic->dateFormat(input('end'));
            empty($start) && $this->error(lang('开始日期为空'));
            empty($end) && $this->error(lang('结束日期为空'));
            ($end < $start) && $this->error(lang('结束日期不能小于开始日期'));
        } else {
            $unit = $ranges[$time];
            $timestamp = getTimeStamp($time);
            $start = $timestamp['start'];
            $end = $timestamp['end'];
        }

        $start_date = date("Y-m-d", $start);
        $end_date = date("Y-m-d", $end);
        return ['start_date' => $start_date, 'end_date' => $end_date];
    }

    public function agency()
    {
        $id = input('id', 0, 'intval');
        $info = $this->db->name('agency')->where(['id' => $id])->find();
        !$info && $this->error(lang('数据不存在'));
        $date = $this->_assignInfo();

        $result = \think\Loader::model('Stat', 'logic')->agencyDay($id, $date['start_date'], $date['end_date']);
        $this->successResponse($result, lang('获取成功'));
    }
}