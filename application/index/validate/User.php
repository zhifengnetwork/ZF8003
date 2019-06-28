<?php
namespace app\index\validate;

use \think\Validate;
//品牌验证器

class User extends validate
{

    protected $rule = [
        'password'      => 'require|length:4,16',
    ];
    protected $message = [
        'password.length' => '密码长度4-16',
        'password.require'      => '密码必须填写',
    ];
    // protected $scene = [
    //     'add' => ['user_name', 'email', 'password'],
    //     'edit' => ['user_name', 'email', 'admin_id'],
    //     'del' => ['admin_id'],
    // ];
}
