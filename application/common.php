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


function layer_error($msg){
    echo "<script>layer.msg('$msg',{icon:3});window.history.go(-1);</script>";
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
