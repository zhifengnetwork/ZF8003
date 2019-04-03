<?php
namespace app\admin\validate;

use \think\Validate;
//品牌验证器

class Admin extends validate
{

    protected $rule = [
        'name'          => 'require|alphaDash',
        'password'      => 'require',
        'password2'     => 'require',
        'group_id'      => 'require',
    ];
    protected $message = [
        'name.require'          => '用户名必填',
        'name.alphaDash'        => '用户名不能为汉字',
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

