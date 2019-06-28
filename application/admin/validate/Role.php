<?php
namespace app\admin\validate;

use \think\Validate;
//品牌验证器

class Role extends validate
{

    protected $rule = [
        'name'          => 'require|chsAlpha|length:2,16|unique:admin_group',
        'jurisdiction'  => 'require'
        // 'password'      => 'require|length:4,16',
        // 'password2'     => 'require|length:4,16',
        // 'group_id'      => 'require',
    ];
    protected $message = [
        'name.require'          => '角色名必填',
        'name.chsAlpha'        => '角色名只能汉字字母',
        'name.length'           => '角色名长度2-16位',
        'name.unique'           =>  '已存在此角色',
        'jurisdiction'          => '请选择权限'
        // 'password.length' => '密码长度4-16',
        // 'password2.length' => '确认长度4-16',
        // 'name.alphaDash'        => '用户名只能英文和数字',
        // 'password.require'      => '密码必须填写',
        // 'password2.require'     => '确认密码必填',
        // 'group_id.require'      => '请选择角色',
    ];
    // protected $scene = [
    //     'add' => ['name', 'jurisdiction'],
    //     'edit' => ['jurisdiction'],
    //     // 'del' => ['admin_id'],
    // ];
}
