<?php
namespace app\admin\validate;

use \think\Validate;
//品牌验证器

class Login extends validate
{

    protected $rule = [
        'username'          => 'require|alphaNum|length:4,16',
        'password'      => 'require|length:4,16',

    ];
    protected $message = [
        'username.require'      => '用户名必填',
        'username.length'       => '用户名长度4-16',
        'password.length'       => '密码长度4-16',
        'username.alphaNum'     => '用户名只能英文和数字',
        'password.require'      => '密码必须填写',
    ];
    // protected $scene = [
    //     'add' => ['user_name', 'email', 'password'],
    //     'edit' => ['user_name', 'email', 'admin_id'],
    // ];
}
