<?php

namespace app\operate\controller;

use app\operate\controller\Common;
use think\Controller;
use think\Db;
use think\Request;
use think\Config;
use think\Session;
use think\Cookie;


class Debug extends Common
{
    var $serverFolder = 'server';
    var $files = [];


    // 列出指定目录下所有目录和文件
    function scandir($dir)
    {
        //定义一个数组
        $files = array();
        //检测是否存在文件
        if (is_dir($dir)) {
            //打开目录
            if ($handle = opendir($dir)) {
                //返回当前文件的条目
                while (($file = readdir($handle)) !== false) {
                    //去除特殊目录
                    if ($file != "." && $file != "..") {
                        //判断子目录是否还存在子目录
                        if (is_dir($dir . "/" . $file)) {
                            //递归调用本函数，再次获取目录
                            $this->files[] = $this->scandir($dir . "/" . $file);
                        } else {
                            //获取目录数组
                            $this->files[] = $dir . "/" . $file;
                        }
                    }
                }
                //关闭文件夹
                closedir($handle);
                //返回文件夹数组
                return $files;
            }
        }
    }


    function translate()
    {
        $title = array(
            '中文',
            'English',
        );
        $filename = '密码线记录';
        $excel = new Office();

        $list = [];
        $keys = ['zh', 'cn'];

        foreach ($arr as $k => $v) {
            $list[] = [
                'zh' => $k,
                'cn' => $v,
            ];
        }
        $excel->outdata($filename, $list, $title, $keys);
    }


    function lang()
    {
        echo '<pre>';
        $folder = str_replace("\\", "/", APP_PATH);
        $this->scandir($folder);
        //print_r($this->files);

        $data = [];
        foreach ($this->files as $file) {
            if (empty($file)) {
                continue;
            }
            $contents = file_get_contents($file);
            $preg = "/lang\('(.*?)'\)/";
            preg_match_all($preg, $contents, $match);
            foreach ($match[1] as $v) {
                $data[] = $v;
            }
        }
        $data = array_unique($data);
        $data = array_values($data);
        print_r($data);

        exit;
    }

    function log()
    {
        $date = input('date');
        $file = input('file', '');
        if (!preg_match("/^\d{4}[\-](0?[1-9]|1[012])[\-](0?[1-9]|[12][0-9]|3[01])?$/", $date)) {
            $date = date("Y-m-d");
        }

        $folder = str_replace("\\", "/", ROOT_PATH) . "../" . $this->serverFolder . "/log/" . str_replace("-", "", $date);
        $files = $this->getFiles($date);

        $file_path = $folder . "/" . $file; //文件路径
        $content = '';
        $line = 0;
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            $content = str_replace("\n", "<br/>", $content);
            $line = $this->getLines($file_path);
        }
        $this->assign('title', '查看日志'); //文件行数
        $this->assign('content', $content);
        $this->assign('files', $files);
        $this->assign('file', $file); //日志文件
        $this->assign('date', $date); //日期
        $this->assign('line', $line); //文件行数
        return $this->fetch('logs/log');
    }


    function login()
    {
        $date = input('date');
        if (!preg_match("/^\d{4}[\-](0?[1-9]|1[012])[\-](0?[1-9]|[12][0-9]|3[01])?$/", $date)) {
            $date = date("Y-m-d");
        }

        $folder = str_replace("\\", "/", ROOT_PATH) . "../" . $this->serverFolder . "/log/" . str_replace("-", "", $date);
        $files = $this->getFiles($date);


        $data = [];
        $date = date("Y-m-d");
        foreach ($files as $v) {
            $cabinet_id = str_replace(".log", "", $v);
            $cache = cache("dev-lid:{$cabinet_id}");
            if (!$cache || $cache['date'] != $date) {
                continue;
            }
            $data[$cabinet_id] = $cache['num'];
        }
        arsort($data);
        foreach ($data as $k => $v) {
            echo $k . "   登录：{$v}次<br/>";
        }
    }


    function view()
    {
        $date = input('date');
        $file = input('file', '');
        if (!preg_match("/^\d{4}[\-](0?[1-9]|1[012])[\-](0?[1-9]|[12][0-9]|3[01])?$/", $date)) {
            $date = date("Y-m-d");
        }

        $folder = str_replace("\\", "/", ROOT_PATH) . "../" . $this->serverFolder . "/log/" . str_replace("-", "", $date);

        $file_path = $folder . "/" . $file; //文件路径
        $content = '';
        $line = 0;
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            $content = str_replace("\n", "<br/>", $content);
            $line = $this->getLines($file_path);
        }

        $this->assign('content', $content);
        $this->assign('file', $file); //日志文件
        $this->assign('date', $date); //日期
        $this->assign('line', $line); //文件行数
        return $this->fetch('logs/view');
    }


    function loadLog()
    {
        $date = input('date', '');
        $file = input('file', '');
        $line = input('line', 0, 'intval');
        $start_time = input('start_time', '2020-11-10 07:00:00');
        $end_time = input('end_time', '');
        $folder = str_replace("\\", "/", ROOT_PATH) . "../" . $this->serverFolder . "/log/" . str_replace("-", "", $date);
        $file_path = $folder . "/" . $file; //文件路径
        $result = ['code' => 0, 'content' => ''];
        if (!file_exists($file_path)) {
            $result['error'] = '日志不存在';
            return $result;
        }
        $file_line = $this->getLines($file_path);
        if ($file_line < 1) {
            $result['error'] = '日志为空';
            return $result;
        }

        if ($file_line <= $line) { //没有新的日志
            $result['error'] = '没有新日志';
            return $result;
        }

        $fp = fopen($file_path, 'r');
        if (!$fp) {
            $result['error'] = '打开日志失败';
            return $result;
        }

        if ($line < 1) { //页面上没有任何日志
            $content = file_get_contents($file_path);
            $content = nl2br($content);
            // dump($content);exit;
            if (!empty($start_time) || !empty($end_time)) {
                if (!empty($start_time)) {
                    $start_time = strtotime($start_time);
                } else {
                    $start_time = strtotime(date('Ymd 00:00:00'));
                }
                if (!empty($end_time)) {
                    $end_time  = strtotime($end_time);
                } else {
                    $end_time = strtotime(date('Ymd 23:59:59'));
                }
                $ret = '';
                $content = preg_split("/(<br \/>\r\n){2}/si", $content);
                foreach ($content as $val) {
                    preg_match("/\d{4}[\-](0?[1-9]|1[012])[\-](0?[1-9]|[12][0-9]|3[01])\s\d{2}:\d{2}:\d{2}?/", $val, $tmp);
                    if ($tmp) {
                        $tmp['time'] = strtotime($tmp[0]);
                        if ($tmp['time'] >= $start_time && $tmp['time'] <= $end_time) {
                            $ret .= $val;
                        }
                    }
                }
                $content = $ret;
            }
            $result = ['code' => 1, 'content' => $content, 'line' => $file_line];
            return $result;
        }


        $result = ['line' => $file_line, 'code' => 1]; //当前日志有多少行

        $line = $file_line - $line;
        $pos = -2;        //偏移量
        $eof = " ";        //行尾标识
        $output = "";
        while ($line > 0) { //逐行遍历

            while ($eof != "\n") { //不是行尾
                fseek($fp, $pos, SEEK_END); //fseek成功返回0，失败返回-1
                $eof = fgetc($fp); //读取一个字符并赋给行尾标识
                $pos--; //向前偏移
            }
            $eof = " ";
            $output = fgets($fp) . "<br/>" . $output;
            $line--;
        }

        fclose($fp); //关闭文件
        $result['content'] = $output;
        return ($result);
    }


    private function getFiles($date)
    {
        if (!preg_match("/^\d{4}[\-](0?[1-9]|1[012])[\-](0?[1-9]|[12][0-9]|3[01])?$/", $date)) {
            $date = date("Y-m-d");
        }

        $date = str_replace("-", "", $date);
        $folder = str_replace("\\", "/", ROOT_PATH) . "../" . $this->serverFolder . "/log/{$date}/";
        $files = [];
        if (!is_dir($folder)) {
            return $files;
        }
        $list = scandir($folder);
        foreach ($list as $v) {
            if (pathinfo($v, PATHINFO_EXTENSION) == 'log') {
                $files[] = $v;
            }
        }
        return $files;
    }

    private function getLines($file)
    {
        $line = 0; //初始化行数
        $fp = fopen($file, 'r');
        if ($fp) {
            //获取文件的一行内容，注意：需要php5才支持该函数；
            while (stream_get_line($fp, 8192, "\n")) {
                $line++;
            }
            fclose($fp); //关闭文件
        }
        return $line;
    }
}
