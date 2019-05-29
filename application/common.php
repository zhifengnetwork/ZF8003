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

// 应用公共文件

/**
 * 保留文件夹，删除路径下的文件
 */
function delFileUnderDir( $dirName = '') 
{ 
    if ($handle = opendir("$dirName")){ 
        while (false !== ( $item = readdir($handle))){
        if ($item != "." && $item != ".."){
            if (is_dir("$dirName/$item")){ 
                delFileUnderDir("$dirName/$item"); 
            } else {
                unlink("$dirName/$item");
            } 
        } 
    } 
    closedir($handle);
    } 
}


# H1错误提示样板
function error_h1($msg='出错了！',$msg2=''){

    echo '<h1 style="text-align:center;margin-top:10%;color:red;">'.$msg.'</h1>';
    if($msg2){
        echo "<p style='text-align:center;margin-top:20px;color:#bb7777;'>".$msg2."</p>";
    }
    exit;
}

# 求两数字的差值
function math_diff($str1 = 0.00, $str2 = 0.00){

    $str1 = (double)$str1;
    $str2 = (double)$str2;

    if($str1 == $str2){
        return 0;
    }
    if($str1 > $str2){
        return $str1 - $str2;
    }
    if($str2 > $str1){
        return $str2 - $str1;
    }
}



# 用于计算的基因座 17 STR
function Gene_17STR(){

    $array = [
        "dys19",
        "dys389i",
        "dys389b",
        "dys390",
        "dys391",
        "dys392",
        "dys393",
        "dys437",
        "dys438",
        "dys439",
        "dys448",
        "dys456",
        "dys458",
        "dys635",
        "gata-h4",
        "dys385a",
        "dys385b",
    ];

    return $array;
}


# 标准基因座 | 判断
function Standard_Gene($key = ''){

    $array = [
        "dys19",
        "dys389i",
        "dys389b",
        "dys390",
        "dys391",
        "dys392",
        "dys393",
        "dys437",
        "dys438",
        "dys439",
        "dys448",
        "dys456",
        "dys458",
        "dys635",
        "gata-h4",
        "dys385a",
        "dys385b",
        "dys449",
        "dys460",
        "dys481",
        "dys518",
        "dys533",
        "dys570",
        "dys576",
        "dys627",
        "dys387s1a",
        "dys387s1b",
        "dys388",
        "dys444",
        "dys549",
        "dys643",
        "dys722",
        "dys404s1a",
        "dys404s1b",
        "dys527a",
        "dys527b"
    ]; 
    if($key){
        return in_array(strtolower($key),$array);
    }else{
        return $array;
    }
    
}

# 返回大写的标准基因座
function Standard_Gene_Up(){
    $data = Standard_Gene();
    foreach($data as $v){
        $r[] = strtoupper($v);
    }
    return $r;
}


# 判断数组元素
function array_key_check($data, $key='', $re=false){
    if(!$data) return false;
    if(!$key){
        foreach($data as $k => $v){
            if(!$v){
                return false;
            }
        }
        return true;
    }else{
        if(array_key_exists($key, $data)){
            if($data[$key]){
                
                return $re ? $data[$key] : true;
            }
        }
    }
    return false;
}

# 唯一订单号
function order_sn(){
    $osn = date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    
    return $osn;
}



function is_weixin() {
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
        return true;
    } return false;
}
 

function is_qq() {
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'QQ') !== false) {
        return true;
    } return false;
}
function is_alipay() {
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
        return true;
    } return false;
}
function is_ios()
{
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    if (strpos($agent, 'iphone') || strpos($agent, 'ipad')) {
        return true;
    }
    return false;
}

/**
 * 检查邮箱地址格式
 * @param $email 邮箱地址
 */
function check_email($email){
    if(filter_var($email,FILTER_VALIDATE_EMAIL))
        return true;
    return false;
}

/**
 * 检查手机号码格式
 * @param $mobile 手机号码
 */
function check_mobile($mobile){
    if(preg_match('/1[34578]\d{9}$/',$mobile))
        return true;
    return false;
}

/**
 * 获取随机字符串
 * @param int $randLength  长度
 * @param int $addtime  是否加入当前时间戳
 * @param int $includenumber   是否包含数字
 * @return string
 */
function get_rand_str($randLength=6,$addtime=1,$includenumber=0){
    if ($includenumber){
        $chars='abcdefghijklmnopqrstuvwxyzABCDEFGHJKLMNPQEST123456789';
    }else {
        $chars='abcdefghijklmnopqrstuvwxyz';
    }
    $len=strlen($chars);
    $randStr='';
    for ($i=0;$i<$randLength;$i++){
        $randStr.=$chars[rand(0,$len-1)];
    }
    $tokenvalue=$randStr;
    if ($addtime){
        $tokenvalue=$randStr.time();
    }
    return $tokenvalue;
}

/**
 * CURL请求
 * @param $url string 请求url地址
 * @param $method string 请求方法 get post
 * @param mixed $postfields post数据数组
 * @param array $headers 请求header信息
 * @param bool|false $debug  调试开启 默认false
 * @return mixed
 */
function httpRequest($url, $method="GET", $postfields = null, $headers = array(), $debug = false, $timeout=60)
{
    $method = strtoupper($method);
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT,$timeout); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (!empty($postfields)) {
                $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
            }
            break;
        default:
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }
    $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
    curl_setopt($ci, CURLOPT_URL, $url);
    if ($ssl) {
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
    }
    //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
    if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
    	curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
    }
    curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ci, CURLINFO_HEADER_OUT, true);
    /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
    $response = curl_exec($ci);
    $requestinfo = curl_getinfo($ci);
    $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    if ($debug) {
        echo "=====post data======\r\n";
        var_dump($postfields);
        echo "=====info===== \r\n";
        print_r($requestinfo);
        echo "=====response=====\r\n";
        print_r($response);
    }
    curl_close($ci);
    return $response;
    //return array($http_code, $response,$requestinfo);
}


/**
 * 调用layer弹出错误提示
 */
function layer_error($msg, $re = true, $url = ''){
    header("Content-type: text/html; charset=utf-8"); 
    // echo '<script type="text/javascript" src="/public/static/public/jquery.min.js"></script>';
    // echo '<script type="text/javascript" src="/public/static/public/layer/layer.js"></script>';
    // echo "<script>layer.msg('$msg',{icon:5,time:3000});</script>";
    echo "<h1 style='margin-top:30%; text-align:center;color:red;'>$msg</h1>";
    if($re){
        if($url){
            echo "<script>setTimeout(function(){window.location.href='$url';},3000);</script>";
        }else{
            echo "<script>setTimeout(function(){window.history.go(-1);},3000);</script>";
        }
        
    }
    exit;
}


/**
 * 加密
 */
function pwd_encryption($password = ''){
    if (!$password) {
        return false;
    }

    $pwd = md5(md5($password));

    return $pwd;
}



/**
 * 数字验证 整数或2位小数
 * @param string $number 需要验证的数字
 * return number or false
 */
function Digital_Verification($number){
    
    preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $number, $r);
    return isset($r[0]) ? $r[0] : false;
}


/**
 * 通过 iframe 窗口提交数据的返回
 * @param array or string $msgs 返回消息体
 * @param func $func 调用函数
 */
function iframe_echo($msgs,$func = ''){
    if(is_array($msgs)){
        $msg = implode("','",$msgs);
    }else{
        $msg = $msgs;
    }
    
    if($func){
        echo "<script>parent.$func('$msg');</script>";
    }else{
        echo "<script>parent.alert('$msg');</script>";
    }
    exit;
}
