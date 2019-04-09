<?php
/**
 * 后台管理系统首页
 */
namespace app\admin\controller;
use Captcha\Captcha;
use think\Db;
use think\Loader;
use think\Session;
class Login extends Base
{

    public function _initialize()
    {
        parent::_initialize();
        // $admin_name = session('admin_name');
        // if($admin_name){
        //     $url = "http://".$_SERVER['HTTP_HOST']. "/index.php/admin";
        //     header("refresh:1;url=$url");
        //     exit;
        // }        
    }
    public function index()
    {
        return $this->fetch();
    }

    public function entry(){
        $captcha = new Captcha();
        return $captcha->entry();
    }


    public function login(){
        $data = input('post.');
        // 验证
        $adminValidate = Loader::Validate('Login');
        if (!$adminValidate->check($data)) {
            $baocuo = $adminValidate->getError();
            return json(['status' => -1, 'msg' => $baocuo]);
        }
        if (!captcha_check($data['yzm'])) {
            return json(['status' => -1, 'msg' => '验证码不正确']);
        };       
        // 判断是否有该用户
        $res = Db::name('admin')->where('name', $data['username'])->find();
        if($res){
            //判断是否禁用
            if($res['is_lock'] == 1){
                return json(['status' => -1, 'msg' => '用户已被禁用']);
            }
            //  判断密码是否相等
            $password = pwd_encryption($data['password']); 
            if($res['password'] === $password){
            // Session::set('admin_id', $admin_user['id']);
                Session::set('admin_name', $data['username']);
                // 此处插入日志
                return json(['status' => 1, 'msg' => '登录成功']);
            }else{
                return json(['status' => -1, 'msg' => '用户名或者密码不正确']);
            }
        }else{
                return json(['status' => -1, 'msg' => '用户名或者密码不正确']);
        }
       
    }
}