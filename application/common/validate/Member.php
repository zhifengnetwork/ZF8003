<?php
namespace app\common\validate;

use think\Validate;

class Member extends Validate
{
    //验证规则
    protected $rule = [
        'nickname' => 'require|checkName',
        'password' => 'require|checkPassword',
    ];
    
    //错误消息
    protected $message = [
        'nickname.require'    => '请输入用户名',
        'password.require'    => '密码不能为空',
        'password.checkPassword'     => '两次密码不一致',
    ];

    //错误消息
    protected $scene= [
        'set_pwd' => ['password'],
        'reg'     => ['nickname','password'],
    ];
}