<?php
namespace app\common\validate;

use think\Validate;

class Member extends Validate
{
    //验证规则
    protected $rule = [
        'nickname' => 'require|unique:users',
        'mobile'   => 'require|unique:users',
        'email'    => 'require|unique:users',
        'group_id' => 'require',
        'money'    => 'number',
    ];
    
    //错误消息
    protected $message = [
        'nickname.require'    => '请输入用户名！',
        'nickname.unique'     => '该用户名已存在！',
        'mobile.require'      => '请输入手机！',
        'mobile.unique'       => '该手机号已注册！',
        'email.require'       => '请输入邮箱！',
        'email.unique'        => '该邮箱已注册！',
        'group_id.require'    => '请输选择分组！',
        'money.number'        => '余额必须是数字！'
    ];

    //错误消息
    protected $scene= [
        //添加
        'add'     => [
            'nickname' => 'require|unique:users,nickname^id',
            'mobile' => 'require|unique:users,mobile^id',
            'email' => 'require|unique:users,email^id',
            'group_id' => 'require',
        ],

        //编辑
        'edit'    => [
            'nickname' => 'require|unique:users,nickname^id',
            'mobile'   => 'require|unique:users,mobile^id',
            'email'    => 'require|unique:users,email^id',
            'group_id' => 'require',
            'money'    => 'number',
        ],

        'set_pwd' => ['password'],
        'reg'     => ['nickname','password'],
    ];
}