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

    public function _initialize()
    {

        parent::_initialize();
        // Session::clear();
        $admin_name = session('admin_name');
        if(!empty($admin_name)){
            $url = "http://".$_SERVER['HTTP_HOST']. "/index.php/admin";
            header("refresh:1;url=$url");
            exit;
        }        
    }
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
        $res = Db::name('admin')->where('name', $data['username'])->find();
        if($res){
            //判断是否禁用
            if($res['is_lock'] == 1){
                return json(['status' => -1, 'msg' => '用户已被禁用']);
            }
            //  判断密码是否相等
            $password = pwd_encryption($data['password']); 
            if($res['password'] === $password){
                
                Session::set('admin_name', $data['username']);
                $admin_user = Db::name('admin')->where('name', $data['username'])->find();
                Session::set('admin_id', $admin_user['id']);
                // 此处插入日志
                $action = 'sign';
                $desc   = '登录';
                $log = $this->adminLog($action,$desc);

                return json(['status' => 1, 'msg' => '登录成功']);
            }else{
                return json(['status' => -1, 'msg' => '用户名或者密码不正确']);
            }
        }else{
                return json(['status' => -1, 'msg' => '用户名或者密码不正确']);
        }
       
    }



    function adminLog($action,$desc)
    {
        $add['addtime'] = time();
        $add['admin_id'] = session('admin_id');
        $add['action'] = $action;
        $add['desc']   = $desc; 
        // $add['log_ip'] = request()->ip();
        // $add['log_url'] = request()->baseUrl();
        Db::name('admin_log')->insert($add);
        return true;
    }

}
