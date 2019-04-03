<?php
namespace app\admin\validate;

use think\validate;
//品牌验证器

class Admin extends validate
{

    protected $rule = [
        'name'          => 'require',
        'password'      => 'require',
        'password2'     => 'require',
        'group_id'      => 'require',
    ];
    protected $message = [
        'name.require'          => '用户名必填',
        'password.require'      => '密码必须',
        'password2.require'     => '确认密码必填',
        'group_id.require'      => '请选择角色',
    ];
    // protected $scene = [
    //     'add' => ['user_name', 'email', 'password'],
    //     'edit' => ['user_name', 'email', 'admin_id'],
    //     'del' => ['admin_id'],
    // ];
}

