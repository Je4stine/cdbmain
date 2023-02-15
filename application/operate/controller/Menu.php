<?php

namespace app\operate\controller;


class Test extends Common
{


    function index()
    {
        echo '<pre>';
        $module = 'operate';
        $all_controller = $this->getController($module);
        $data = [];
        foreach ($all_controller as $controller) {
            $all_action = $this->getAction($module, $controller);
            foreach ($all_action as $action) {
                $controller = str_replace('Controller', '', $controller);
                $controller = lcfirst($controller);
                $data[$controller][] = $controller."/".$action;
            }
        }
        print_r($data);
        exit;
    }


    //获取所有控制器名称
    private function getController($module) {
        if (empty($module)) {
            return null;
        }
        $module_path = APP_PATH . '/' . $module . '/controller/';  //控制器路径

        if (!is_dir($module_path)) {
            return null;
        }
        $module_path .= '/*.php';
        $ary_files = glob($module_path);

        foreach ($ary_files as $file) {
            if (is_dir($file)) {
                continue;
            } else {
                $files[] = basename($file, '.php');
            }
        }
        return $files;
    }


    //获取所有方法名称
    protected function getAction($module, $controller) {
        if (empty($controller)) {
            return null;
        }
        $customer_functions = [];
        $file = APP_PATH . $module . '/controller/' . $controller . '.php';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            preg_match_all("/.*?public.*?function(.*?)\(.*?\)/i", $content, $matches);
            $functions = $matches[1];
            //排除部分方法
            $inherents_functions = array('_initialize', '__construct', 'getActionName', 'isAjax', 'display', 'show', 'fetch', 'buildHtml', 'assign', '__set', 'get', '__get', '__isset', '__call', 'error', 'success', 'ajaxReturn', 'redirect', '__destruct', '_empty');
            foreach ($functions as $func) {
                $func = trim($func);
                if (!in_array($func, $inherents_functions)) {
                    $customer_functions[] = $func;
                }
            }
            return $customer_functions;
        } else {
//            \ticky\Log::record('is not file ' . $file, Log::INFO);
            return false;
        }
        return null;
    }


}