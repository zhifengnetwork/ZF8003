<?php

return [
    
    'view_replace_str' => [
        '__PUBLIC__' => 'public/static',
        '__STATIC__' => 'public/static/admin',
        '__HOST__' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/public/static/admin',
    ],
    
    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------

    'session'                => [
        'id'             => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => '',
        // SESSION 前缀
        'prefix'         => 'admin',
        // 驱动方式 支持redis memcache memcached
        'type'           => '',
        // 是否自动开启 SESSION
        'auto_start'     => true,
    ],

    'cache'  => [
        'type'   => 'File',
        'path'   => CACHE_PATH,
        'prefix' => 'admin_',
        'expire' => 0,
    ],

];