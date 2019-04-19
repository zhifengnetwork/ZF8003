<?php
namespace app\admin\validate;

use \think\Validate;
//品牌验证器

class User extends validate
{

    protected $rule = [
        'name'          => 'require|alphaDash|length:4,16',
        'password'      => 'require|length:4,16',
        'password2'     => 'require|length:4,16',
        'group_id'      => 'require',
    ];
    protected $message = [
        'name.require'          => '用户名必填',
        'name.length'     => '用户名长度4-16',
        'password.length' => '密码长度4-16',
        'password2.length' => '确认长度4-16',
        'name.alphaDash'        => '用户名只能英文和数字',
        'password.require'      => '密码必须填写',
        'password2.require'     => '确认密码必填',
        'group_id.require'      => '请选择角色',
    ];
    // protected $scene = [
    //     'add' => ['user_name', 'email', 'password'],
    //     'edit' => ['user_name', 'email', 'admin_id'],
    //     'del' => ['admin_id'],
    // ];
}
