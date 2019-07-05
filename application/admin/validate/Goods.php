<?php
namespace app\admin\validate;

use \think\Validate;
//品牌验证器

class Goods extends validate
{

    protected $rule = [
        'name'          => 'require|chsAlphaNum|length:4,16',
        'goods_id'       => 'require',
        'term'          => 'require|integer',
        'quota'         => 'require|number',
        'money'         => 'require|number',
        'deadline'      => 'require|date'
    ];
    protected $message = [
        'name.require'          => '用户名必填',
        'name.length'           => '名称长度4-16',
        'goods_id'              => '请选择商品',
        'term.require'          => '请填写使用期限',
        'term.integer'           => '请填写整数',
        'name.alphaDash'        => '用户名只能汉字英文和数字',
        'quota.require'         => '请输入额度',
        'quota.number'          => '请输入数字',
        'quota.require'         => '请输入额度',
        'money.number'          => '请输入数字',
        'deadline.require'      => '请输入日期',
        'deadline.date'         => '请输入正确日期格式',
    ];
    // protected $scene = [
    //     'add' => ['user_name', 'email', 'password'],
    //     'edit' => ['user_name', 'email', 'admin_id'],
    //     'del' => ['admin_id'],
    // ];
}
