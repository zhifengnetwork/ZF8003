<?php
/**
 * 后台管理系统首页
 */
namespace app\admin\controller;
use think\captcha\Captcha;
use think\Db;
use think\Loader;
use think\Session;
use think\Config;
class Login extends Base
{


    public function index()
    {
        
        return $this->fetch();
    }

    public function entry(){
        $captcha = new Captcha(Config::get('captcha'));
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
        $sql = "select a.*,b.name as group_name,b.jurisdiction from `zf_admin` as a left join `zf_admin_group` as b on a.group_id = b.id where a.name = '$data[username]' limit 1";
        $res = Db::query($sql);
        if($res){
            $res = $res[0];
            //判断是否禁用
            if($res['is_lock'] == 1){
                return json(['status' => -1, 'msg' => '用户已被禁用']);
            }
            //  判断密码是否相等
            $password = pwd_encryption($data['password']); 
            if($res['password'] === $password){

                Session::set('admin',$res);
                Session::set('admin_name', $data['username']);
                $admin_user = Db::name('admin')->where('name', $data['username'])->find();
                Session::set('admin_id', $admin_user['id']);
                // 此处插入日志
                $action = 'sign';
                $desc   = '登录';
                $log = adminLog($action,$desc);

                return json(['status' => 1, 'msg' => '登录成功']);
            }else{
                return json(['status' => -1, 'msg' => '用户名或者密码不正确']);
            }
        }else{
                return json(['status' => -1, 'msg' => '用户名或者密码不正确']);
        }
       
    }





}
