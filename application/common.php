<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
error_reporting(E_ERROR | E_WARNING | E_PARSE);

// 应用公共文件
/**
 * 可逆加密
 * @param  string $data 需要加密的数据
 * @param  string $key 密码
 * @return string       加密后的数据
 */
function mwencrypt($data, $key = 'xfz61281006')
{
    $key = md5($key);
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = '';
    $str = '';
    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) {
            $x = 0;
        }
        $char .= $key{$x};
        $x++;
    }
    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord($data{$i}) + (ord($char{$i})) % 256);
    }
    //var_dump($str );
    return base64_encode($str);
}

/**
 * 解密
 * @param  string $data 需要解密的数据
 * @param  string $key 密码
 * @return string       解密后的数据
 */
function mwdecrypt($data, $key = 'xfz61281006')
{
    $key = md5($key);
    $x = 0;
    $data = base64_decode($data);
    $len = strlen($data);
    $l = strlen($key);
    $char = '';
    $str = '';
    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) {
            $x = 0;
        }
        $char .= substr($key, $x, 1);
        $x++;
    }
    for ($i = 0; $i < $len; $i++) {
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        } else {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return $str;
}


//CURL请求
/**
 * CURL请求
 * @param string  url
 * @param array   数据
 * @param string  请求方式  如果不等于post,则使用get请求
 * @param array   设置请求头部数据
 *
 */
function CURLRequest($url, $params, $http_method = 'GET',$Header = [])
{

    $SSL = substr($url, 0, 8) == "https://" ? true : false;  //判断是否https连接
    $httpInfo = array();
    $ch = curl_init();                                       //初始化CURL会话

    //设置CURL传输选项
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	//设置响应头头文件的信息作为数据流输出
	curl_setopt($ch, CURLOPT_HEADER, 1); //返回response头部信息
	curl_setopt($ch, CURLINFO_HEADER_OUT, true); //TRUE 时追踪句柄的请求字符串，从 PHP 5.1.3 开始可用。这个很关键，就是允许你查看请求header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// 返回 response_header, 该选项非常重要,如果不为 true, 只会获得响应的正文
	curl_setopt($ch, CURLOPT_HEADER, true);
    if ($http_method == 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_URL, $url);
        
    } else {
    	if(is_array($params)&& !empty($params)){
	        $data = '?';
	        foreach ($params as $key => $value) {
	            $data .= $key . '=' . $value . '&';
	        }
	    }
        curl_setopt($ch, CURLOPT_URL, $url . $data);
    }
    
    if(!empty($Header)){
    	//设置请求头信息
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $Header);
    }
    if ($SSL) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书 
    }


    $response = curl_exec($ch);                                      //执行CURL会话
	
	if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '200') {
		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $headerSize);
		$body = substr($response, $headerSize);
	}
	
	curl_close($ch);                                                 //关闭会话
	
	list($header, $body) = explode("\r\n\r\n", $response, 2);
	
	$headers = explode("\r\n", $header);
	$headList = array();
	foreach ($headers as $head) {
		$value = explode(':', $head);
		$headList[$value[0]] = $value[1];
	}
	
    $result = json_decode($body, true);                               //返回解析后的数据
    if ($response === FALSE OR empty($response)) {                    //错误返回false
        $result = false;
    }
	
	return ['header'=>$headList,'result'=>$result['data'][0],'raw_result' => $body];//header响应头数据，格式化数组，原始数据
}

/**
 * 可以统计中文字符串长度的函数
 * @param $str 要计算长度的字符串
 * @param $type 计算长度类型，0(默认)表示一个中文算一个字符，1表示一个中文算两个字符
 *
 */
function abslength($str)
{
    if (empty($str)) {
        return 0;
    }
    if (function_exists('mb_strlen')) {
        return mb_strlen($str, 'utf-8');
    } else {
        preg_match_all("/./u", $str, $ar);
        return count($ar[0]);
    }
}

/**
 *    utf-8编码下截取中文字符串,参数可以参照substr函数
 * @param $str 要进行截取的字符串
 * @param $start 要进行截取的开始位置，负数为反向截取
 * @param $end 要进行截取的长度
 */
function utf8_substr($str, $start = 0)
{
    if (empty($str)) {
        return false;
    }
    if (function_exists('mb_substr')) {
        if (func_num_args() >= 3) {
            $end = func_get_arg(2);
            return mb_substr($str, $start, $end, 'utf-8');
        } else {
            mb_internal_encoding("UTF-8");
            return mb_substr($str, $start);
        }

    } else {
        $null = "";
        preg_match_all("/./u", $str, $ar);
        if (func_num_args() >= 3) {
            $end = func_get_arg(2);
            return join($null, array_slice($ar[0], $start, $end));
        } else {
            return join($null, array_slice($ar[0], $start));
        }
    }
}

/**
 * @desc arraySort php二维数组排序 按照指定的key 对数组进行排序
 * @param array $arr 将要排序的数组
 * @param string $keys 指定排序的key
 * @param string $type 排序类型 asc | desc
 * @return array
 */
function arraySort($arr, $keys, $type = 'asc')
{
    $keysvalue = $new_array = array();
    foreach ($arr as $k => $v) {
        $keysvalue[$k] = $v[$keys];
    }
    $type == 'asc' ? asort($keysvalue) : arsort($keysvalue);

    reset($keysvalue);
    foreach ($keysvalue as $k => $v) {
        $new_array[] = $arr[$k];
    }
    return $new_array;
}

/**
 * ******************
 * 1、写入内容到文件,追加内容到文件
 * 2、打开并读取文件内容
 * *******************
 */
function save_log($folder = 'debug', $msg)
{
    $path = LOG_PATH . $folder;
    if (!is_dir($path)) {
        mkdir($path);
    }
    $filename = $path . '/' . date('Ymd') . '.txt';
    $content = date("Y-m-d H:i:s") . "\r\n" . print_r($msg, 1) . "\r\n \r\n \r\n ";
    file_put_contents($filename, $content, FILE_APPEND);
}


/**
 * 指定位置插入字符串
 * @param $str  原字符串
 * @param $i    插入位置
 * @param $substr 插入字符串
 * @return string 处理后的字符串
 */
function insertToStr($str, $i, $substr)
{
    //指定插入位置前的字符串
    $startstr = "";
    for ($j = 0; $j < $i; $j++) {
        $startstr .= $str[$j];
    }

    //指定插入位置后的字符串
    $laststr = "";
    for ($j = $i; $j < strlen($str); $j++) {
        $laststr .= $str[$j];
    }
    //将插入位置前，要插入的，插入位置后三个字符串拼接起来
    $str = $startstr . $substr . $laststr;

    //返回结果
    return $str;
}

	// a valid password should contains:
	// at least 1 upper case letter, 1 lower case letter, 1 number, 1 special character,
	// and 8 characters in length
	function valid_pass($candidate) {
		$r1='/[A-Z]/';  //uppercase
		$r2='/[a-z]/';  //lowercase
		$r3='/[0-9]/';  //numbers
		$r4='/[~!@#$%^&*()\-_=+{};:<,.>?]/';  // special char

        if(strlen($candidate)<6) {
            return ['code' => 0,'msg' =>  '密码必须包含至少含有6个字符，请返回修改！' ];
        }
        return ['code' => 1];

		if(preg_match_all($r1,$candidate, $o)<1) {
			return ['code' => 0,'msg' =>  '密码必须包含至少一个大写字母，请返回修改！' ];
		}
		if(preg_match_all($r2,$candidate, $o)<1) {
			return ['code' => 0,'msg' =>  '密码必须包含至少一个小写字母，请返回修改！' ];
		}
		if(preg_match_all($r3,$candidate, $o)<1) {
			return ['code' => 0,'msg' =>  '密码必须包含至少一个数字，请返回修改！' ];
		}
		if(preg_match_all($r4,$candidate, $o)<1) {
			return ['code' => 0,'msg' =>  '密码必须包含至少一个特殊符号：[~!@#$%^&*()\-_=+{};:<,.>?]，请返回修改！' ];
		}
		if(strlen($candidate)<8) {
			return ['code' => 0,'msg' =>  '密码必须包含至少含有8个字符，请返回修改！' ];
		}
		return ['code' => 1];
	}

/**
 * 获取相应时间范围的时间戳
 * @param  [type] $when 日期（用英文表示，如果传过来的值为空，默认返回本月的时间戳）
 * @return [type]       [description]
 */
function getTimeStamp($when = null)
{
    switch ($when) {
        case 'today':
            //今天
            $starttime = strtotime(date("Y-m-d", time()));
            $endtime = strtotime(date("Y-m-d 23:59:59", time()));
            break;
        case 'yesterday':
            //昨天
            $starttime = mktime(0, 0, 0, date("m"), date("d") - 1, date("Y"));
            $endtime = mktime(23, 59, 59, date("m"), date("d") - 1, date("Y"));
            break;
        case 'week':
            //本周
            $starttime = mktime(0, 0, 0, date("m"), date("d") - date("w") + 1, date("Y"));
            $endtime = mktime(23, 59, 59, date("m"), date("d") - date("w") + 7, date("Y"));
            break;
        case 'month':
            //本月
            $starttime = mktime(0, 0, 0, date("m"), 1, date("Y"));
            $endtime = mktime(23, 59, 59, date("m"), date("t"), date("Y"));
            break;
        case 'year':
            //今年
            $starttime = mktime(0, 0, 0, 1, 1, date("Y"));
            $endtime = mktime(23, 59, 59, 12, 31, date("Y"));
            break;
        case 'lastWeek':
            //上周
            $starttime = mktime(0, 0, 0, date("m"), date("d") - date("w") + 1 - 7, date("Y"));
            $endtime = mktime(23, 59, 59, date("m"), date("d") - date("w"), date("Y"));
            break;
        case 'lastMonth':
            //上月
            $starttime = mktime(0, 0, 0, date("m") - 1, 1, date("Y"));
            $endtime = mktime(23, 59, 59, date("m"), 0, date("Y"));
            break;
        case 'lastYear':
            //去年
            $starttime = mktime(0, 0, 0, 1, 1, date("Y") - 1);
            $endtime = mktime(23, 59, 59, 12, 31, date("Y") - 1);
            break;
        default:
            //本月
            $starttime = mktime(0, 0, 0, date("m"), 1, date("Y"));
            $endtime = mktime(23, 59, 59, date("m"), date("t"), date("Y"));
            break;
    }
    $data['start'] = $starttime;
    $data['end'] = $endtime;
    return $data;

}

/**************************************************************
 *
 *  使用特定function对数组中所有元素做处理
 * @param  string  &$array 要处理的字符串
 * @param  string $function 要执行的函数
 * @return boolean $apply_to_keys_also     是否也应用到key上
 * @access public
 *
 *************************************************************/
function arrayRecursive(&$array, $function, $apply_to_keys_also = false)
{
    static $recursive_counter = 0;
    if (++$recursive_counter > 1000) {
        die('possible deep recursion attack');
    }
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            arrayRecursive($array[$key], $function, $apply_to_keys_also);
        } else {
            $array[$key] = $function($value);
        }

        if ($apply_to_keys_also && is_string($key)) {
            $new_key = $function($key);
            if ($new_key != $key) {
                $array[$new_key] = $array[$key];
                unset($array[$key]);
            }
        }
    }
    $recursive_counter--;
}

/**************************************************************
 *
 *  将数组转换为JSON字符串（兼容中文）
 * @param  array $array 要转换的数组
 * @return string      转换得到的json字符串
 * @access public
 *
 *************************************************************/
function jsones($array)
{
    arrayRecursive($array, 'urlencode', true);
    $json = json_encode($array);
    return urldecode($json);
}

function object_to_array($obj)
{
    $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
    foreach ($_arr as $key => $val) {
        $val = (is_array($val)) || is_object($val) ? object_to_array($val) : $val;
        $arr[$key] = $val;
    }
    return $arr;
}

/*
	自定义复杂MD5加密函数
*/
function md123($sstr)
{

    $md_asc = '';

    $mds = md5('www.zdhx.com@@' . $sstr . 'www.zdhx123.com##');
    $mds = md5('www.zdhx444.com' . $mds . 'ssaas@@ffff');
    $mds = md5('ssdq12@$%' . $mds . '12dsad3!@#$%^');
    $mds = md5('cdb111@$%' . $mds . 'cdb222!@#$%^');
    $mds = md5('canada' . $mds . 'canada2221');
    for ($i = 1; $i < strlen($mds); $i++) {

        $md_asc .= 168 / ord(substr($mds, $i, 1));

    }

    return md5($md_asc);
}

//加1
/*$condition = array('date'=> '2019-07-06');
$params = array('feild1'=>1,'feild2'=>2);
*/
function addNumSql($table, $uniqueKey, $params)
{
	$sqlSet = "";
	foreach ($uniqueKey as $key => $value) {
		if ("" != $sqlSet) {
			$sqlSet .= ",";
		}
		$sqlSet .= "`" . $key . "`='" . addslashes($value) . "'";
	}
	foreach ($params as $feild => $value) {
		if ("" != $sqlSet) {
			$sqlSet .= ",";
		}
		$value = trim($value);
		$sqlSet .= "`" . $feild . "`= " . addslashes($feild) . " + {$value}";
	}
	$sql = "INSERT  DELAYED INTO " . $table . " SET " . $sqlSet . " ON DUPLICATE KEY UPDATE " . $sqlSet;
	return $sql;
}

/**
 * +----------------------------------------------------------
 * 生成随机字符串
 * +----------------------------------------------------------
 * @param int $length 要生成的随机字符串长度
 * @param string $type 随机码类型：0，数字+大小写字母；1，数字；2，小写字母；3，大写字母；4，特殊字符；-1，数字+大小写字母+特殊字符
 * +----------------------------------------------------------
 * @return string
 * +----------------------------------------------------------
 */
function randCode($length = 5, $type = 0)
{
    $arr = array(1 => "0123456789", 2 => "abcdefghijklmnopqrstuvwxyz", 3 => "ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4 => "~@#$%^&*(){}[]|");
    if ($type == 0) {
        array_pop($arr);
        $string = implode("", $arr);
    } elseif ($type == "-1") {
        $string = implode("", $arr);
    } else {
        $string = $arr[$type];
    }
    $count = strlen($string) - 1;
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $string[rand(0, $count)];
    }
    return $code;
}

/**
 * 获取客户端浏览器信息 添加win10 edge浏览器判断
 * @return string
 */
function get_broswer()
{
    $sys = $_SERVER['HTTP_USER_AGENT'];  //获取用户代理字符串
    if (stripos($sys, "Firefox/") > 0) {
        preg_match("/Firefox\/([^;)]+)+/i", $sys, $b);
        $exp[0] = "Firefox";
        $exp[1] = $b[1];  //获取火狐浏览器的版本号
    } elseif (stripos($sys, "Maxthon") > 0) {
        preg_match("/Maxthon\/([\d\.]+)/", $sys, $aoyou);
        $exp[0] = "傲游";
        $exp[1] = $aoyou[1];
    } elseif (stripos($sys, "MSIE") > 0) {
        preg_match("/MSIE\s+([^;)]+)+/i", $sys, $ie);
        $exp[0] = "IE";
        $exp[1] = $ie[1];  //获取IE的版本号
    } elseif (stripos($sys, "OPR") > 0) {
        preg_match("/OPR\/([\d\.]+)/", $sys, $opera);
        $exp[0] = "Opera";
        $exp[1] = $opera[1];
    } elseif (stripos($sys, "Edge") > 0) {
        //win10 Edge浏览器 添加了chrome内核标记 在判断Chrome之前匹配
        preg_match("/Edge\/([\d\.]+)/", $sys, $Edge);
        $exp[0] = "Edge";
        $exp[1] = $Edge[1];
    } elseif (stripos($sys, "Chrome") > 0) {
        preg_match("/Chrome\/([\d\.]+)/", $sys, $google);
        $exp[0] = "Chrome";
        $exp[1] = $google[1];  //获取google chrome的版本号
    } elseif (stripos($sys, 'rv:') > 0 && stripos($sys, 'Gecko') > 0) {
        preg_match("/rv:([\d\.]+)/", $sys, $IE);
        $exp[0] = "IE";
        $exp[1] = $IE[1];
    } else {
        $exp[0] = "未知浏览器";
        $exp[1] = "";
    }
    return $exp[0] . '(' . $exp[1] . ')';
}

/**
 * 获取客户端操作系统信息包括win10
 * @return string
 */
function get_os()
{
    $agent = $_SERVER['HTTP_USER_AGENT'];
    $os = false;

    if (preg_match('/win/i', $agent) && strpos($agent, '95')) {
        $os = 'Windows 95';
    } else if (preg_match('/win 9x/i', $agent) && strpos($agent, '4.90')) {
        $os = 'Windows ME';
    } else if (preg_match('/win/i', $agent) && preg_match('/98/i', $agent)) {
        $os = 'Windows 98';
    } else if (preg_match('/win/i', $agent) && preg_match('/nt 6.0/i', $agent)) {
        $os = 'Windows Vista';
    } else if (preg_match('/win/i', $agent) && preg_match('/nt 6.1/i', $agent)) {
        $os = 'Windows 7';
    } else if (preg_match('/win/i', $agent) && preg_match('/nt 6.2/i', $agent)) {
        $os = 'Windows 8';
    } else if (preg_match('/win/i', $agent) && preg_match('/nt 10.0/i', $agent)) {
        $os = 'Windows 10';#添加win10判断
    } else if (preg_match('/win/i', $agent) && preg_match('/nt 5.1/i', $agent)) {
        $os = 'Windows XP';
    } else if (preg_match('/win/i', $agent) && preg_match('/nt 5/i', $agent)) {
        $os = 'Windows 2000';
    } else if (preg_match('/win/i', $agent) && preg_match('/nt/i', $agent)) {
        $os = 'Windows NT';
    } else if (preg_match('/win/i', $agent) && preg_match('/32/i', $agent)) {
        $os = 'Windows 32';
    } else if (preg_match('/linux/i', $agent)) {
        $os = 'Linux';
    } else if (preg_match('/unix/i', $agent)) {
        $os = 'Unix';
    } else if (preg_match('/sun/i', $agent) && preg_match('/os/i', $agent)) {
        $os = 'SunOS';
    } else if (preg_match('/ibm/i', $agent) && preg_match('/os/i', $agent)) {
        $os = 'IBM OS/2';
    } else if (preg_match('/Macintosh/i', $agent) && preg_match('/Mac OS X/i', $agent)) {
        $os = 'Mac OS';
    } else if (preg_match('/PowerPC/i', $agent)) {
        $os = 'PowerPC';
    } else if (preg_match('/AIX/i', $agent)) {
        $os = 'AIX';
    } else if (preg_match('/HPUX/i', $agent)) {
        $os = 'HPUX';
    } else if (preg_match('/NetBSD/i', $agent)) {
        $os = 'NetBSD';
    } else if (preg_match('/BSD/i', $agent)) {
        $os = 'BSD';
    } else if (preg_match('/OSF1/i', $agent)) {
        $os = 'OSF1';
    } else if (preg_match('/IRIX/i', $agent)) {
        $os = 'IRIX';
    } else if (preg_match('/FreeBSD/i', $agent)) {
        $os = 'FreeBSD';
    } else if (preg_match('/teleport/i', $agent)) {
        $os = 'teleport';
    } else if (preg_match('/flashget/i', $agent)) {
        $os = 'flashget';
    } else if (preg_match('/webzip/i', $agent)) {
        $os = 'webzip';
    } else if (preg_match('/offline/i', $agent)) {
        $os = 'offline';
    } else {
        $os = '未知操作系统';
    }
    return $os;
}

/**
 * 获取客户端IP地址
 * @return string
 */
function get_ip()
{
    global $ip;
    if (getenv("HTTP_CLIENT_IP"))
        $ip = getenv("HTTP_CLIENT_IP");
    else if (getenv("HTTP_X_FORWARDED_FOR"))
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    else if (getenv("REMOTE_ADDR"))
        $ip = getenv("REMOTE_ADDR");
    else $ip = "未知";
    return $ip;
}


/*
    移除Emoji表情
 */
function removeEmoji($value)
{

    $value = json_encode($value);
    $value = preg_replace("/\\\u[ed][0-9a-f]{3}\\\u[ed][0-9a-f]{3}/", "*", $value);//替换成*
    $value = json_decode($value);

    return $value;
}

/**
 * jwt加密
 * @param array $data
 * @return string
 */
function jwtEncode($data = [])
{
    try {
        return \Firebase\JWT\JWT::encode(array_merge([
            "iss" => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'], // jwt签发者
            "aud" => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'], // 接收jwt的一方
            "iat" => time() // jwt的签发时间
        ], $data), config('jwt_key'));
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * jwt解密
 * @param string $encode_string
 * @return array
 */
function jwtDecode($encode_string = '')
{
    try {
        $res = \Firebase\JWT\JWT::decode($encode_string, config('jwt_key'), ['HS256']);

        return (array)$res;
    } catch (\Exception $e) {
        return false;
//        return $e->getMessage();
    }
}
/**
 * 将xml转为array
 * @param string $xml
 * return array
 */
function xml_to_array($xml){
	if(!$xml){
		return false;
	}
	//将XML转为array
	//禁止引用外部xml实体
	libxml_disable_entity_loader(true);
	$data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
	return $data;
}
/**
 * 将array转为xml
 * @param array $array
 * return xml
 */
function toXml($params){
	if(!is_array($params)|| count($params) <= 0)
	{
		return false;
	}
	$xml = "<xml>";
	foreach ($params as $key=>$val)
	{
		if (is_numeric($val)){
			$xml.="<".$key.">".$val."</".$key.">";
		}else{
//			if($val === 'true'){
//				$xml.= "<".$key.">".true."</".$key.">" ;
//			}elSe if($val === 'false'){
//				$xml.= "<".$key.">".false."</".$key.">" ;
//			}else{
			$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
//			}
		}
	}
	$xml.="</xml>";
	return $xml;
}

/**
 * 调用微信SDK
 * @staticvar array $wechat
 * @param type $type SDK类型
 * @return type
 */
function callWechat($type = '')
{
    static $wechat = array();;
    $index = md5(strtolower($type));
    if (!isset($wechat[$index])) {
        $config = [//微信公众号
            'appid' => 'wx50651f79980b88a4',
            'appsecret' => '7b5626cfd8b43443727ac586f1793c08',
            'cachepath' => RUNTIME_PATH . '/temp',
            'partnerKey' => '', //支付api密钥
            'mchid' => '' //商户号
        ];
        $wechat[$index] = \wechat\Loader::get($type, $config);
    }
    return $wechat[$index];
}

/**
 * 价格格式化
 * @param type $num
 * @return type
 */
function priceFormat($num, $format = true)
{
    $num = floatval($num);
    $num = sprintf("%.2f", $num);
    if (!$format) {
        return $num;
    }
    //$num = preg_replace("/([0-9]+\.[0-9]{2})[0-9]*/", '$1', $num);
    $tmp = explode(".", $num);
    if (!isset($tmp[1])) {
        return intval($num);
    }
    if (!isset($tmp[1][1]) || $tmp[1][1] < 1) {
        if ($tmp[1][0] < 1) {
            return intval($tmp[0]);
        }
        return floatval($tmp[0] . "." . $tmp[1][0]);
    }
    return floatval($tmp[0] . "." . $tmp[1][0] . $tmp[1][1]);
}

if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
    function mmx_getpswd($code)
    {
        $password = [];
        for ($i = 0; $i < 21; $i++) {
            $password[] = mt_rand(10000, 99999);
        }
        return implode(",", $password);
    }

}


//插入更新SQL
function sqlInsertUpdate($table, $params, $up_params = array())
{
    $sql = array();
    $update = array();
    foreach ($params as $key => $value) {
        $sql[] = "`" . $key . "`='" . addslashes($value) . "'";
    }
    foreach ($up_params as $key => $value) {
        $update[] = "`" . $key . "`='" . addslashes($value) . "'";
    }
    $sql = "INSERT  INTO " . $table . " SET " . implode(", ", $sql) . " ON DUPLICATE KEY UPDATE " . implode(", ", $update);
    return $sql;
}

//语言切换
function setLang() {
	$lang =  input( 'get.lang' ) ;
    $lang_data = config('lang_data');
    if(!$lang || !isset($lang_data[$lang])){
        $lang = config('lang_default');
    }
    cookie( 'think_var', strtolower($lang) );
}

function statSql($table, $condition, $params)
{
    return json_encode(['table' => $table, 'condition' => $condition, 'params' => $params], JSON_UNESCAPED_UNICODE);
}

function getTableByDate($name, $date)
{
    $date = str_replace("-", "", $date);
    $date = substr($date, 0, 6);
    $table = $name . "_" . $date;
    return $table;
}

function statText($code = 'jcc', $text)
{
    if (empty($text)) {
        return;
    }
    $path = LOG_PATH . 'stat';
    !is_dir($path) && mkdir($path);
    $path = $path . '/' . strtolower($code);
    !is_dir($path) && mkdir($path);
    is_array($text) && $text = implode("\n", $text);
    $filename = $path . '/' . randCode(10) . '.txt';
    file_put_contents($filename, $text, FILE_APPEND);
}

function getTableNo($name, $type, $ext = '', $str = '')
{
    if ($type == 'date') {
        return $name . "_" . $ext;
    }
    $table = substr(md5($str), 0, 2);
    if ($ext == 256) {
        return $name . "_" . $table;
    }
    if ($ext != 64 && $ext != 16 && $ext != 4) {
        die('系统繁忙');
    }
    $ext = 256 / $ext;
    $table = hexdec($table) / $ext;
    $table = dechex($table);
    $table = str_pad($table, 2, "0", STR_PAD_LEFT);
    return $name . "_" . $table;
}

//获取两个时间月份差
function getMonthNum($date1, $date2)
{
    $date1 = strtotime($date1);
    $date2 = strtotime($date2);
    if ($date1 > $date2) {
        $tmp = $date1;
        $date1 = $date2;
        $date2 = $tmp;
    }
    $year = date("Y", $date2) - date("Y", $date1);
    $month = date("m", $date2) - date("m", $date1);
    $diff = $year*12 + $month;
    return $diff;
}

