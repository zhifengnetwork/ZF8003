<?php

/**
 * 公共文件
 */

 use think\Db;


# JS调用 layer.msg 成功信息提示样板
function layer_success_msg($msg,$re=false){
    echo "<script>parent.layer_success_msg('$msg',$re)</script>";
    exit;
}

# JS调用 layer.msg 错误信息提示样板
function layer_error_msg($msg){
    echo "<script>parent.layer_error_msg('$msg')</script>";
    exit;
}


 //分组名称
function group_name($group_id){
    $group_name = Db::name('user_group')->where('id',$group_id)->value('name');

    $group_name = $group_name ? $group_name : "暂无分组";
    return $group_name;
}

//上级名称
function first_leader($user_id){
    $name = Db::name('users')->where('first_leader',$user_id)->value('nickname');
    $name = $name ? $name : "无";
    return $name;
}

function adminLog($log_info,$desc)
{
    $add['addtime'] = time();
    $add['admin_id'] = session('admin_id');
    $add['action'] = $log_info;
    $add['desc']   = $desc;
    $add['ip'] = request()->ip();
    // $add['log_url'] = request()->baseUrl();
    Db::name('admin_log')->insert($add);
}