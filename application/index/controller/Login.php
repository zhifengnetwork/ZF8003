<?php
namespace app\index\controller;

use think\Controller;
use think\Db;
use think\Session;
class Login extends Base
{

    public function _initialize(){
        parent::_initialize();
        if(Session::has('user')){
         return $this->redirect('index/index/index');   
        }
    }

    public function index()
    {
        // return $this->redirect('index/login/login');
    }

    /**
     * 登录
     */
    public function login()
    {
        if($_POST){
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';
            // $captcha = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';

            if(!check_email($email)){
                return json(['status'=>0,'msg'=>'邮箱格式错误！']);
            }
            if(!$password){
                return json(['status'=>0,'msg'=>'请填写登录密码！']);
            }
            // if(!captcha_check($captcha)){
            //     return json(['status'=>0,'msg'=>'验证码错误！']);
            // };
            $user = Db::name('users')->where('email',$email)->find();
            if(!$user){
                return json(['status'=>0,'msg'=>'账户不存在！']);
            }
            if(pwd_encryption($password) != $user['password']){
                return json(['status'=>0,'msg'=>'登录密码错误！']);
            }

            Session::set('user',$user);
            $time = time();
            $log_data = [
                'user_id'   => $user['id'],
                'type'  =>  'email',
                'ip'    =>  $this->ip,
                'client'    => $this->client,
                'addtime'   => $time,
            ];
            Db::name('user_login_log')->insert($log_data);
            Db::name('users')->where('id',$user['id'])->update(['login_time'=>$time]);

            $re_url = Session::has('re_url') ? Session::get('re_url') : '/index/index/index';
           
            return json(['status'=>1,'msg'=>'登录成功，正在跳转...','url'=>$re_url]);

            exit;
        }
        
        // captcha_src();
        return $this->fetch();
    }

    /**
     * 注册
     */
    public function register(){
        if($_POST){
            $register_id = isset($_POST['register_id']) ? trim($_POST['register_id']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $code = isset($_POST['code']) ? trim($_POST['code']) : '';
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';
            $re_password = isset($_POST['re_password']) ? trim($_POST['re_password']) : '';
            $captcha = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';
           
            if(!$register_id){
                return json(['status'=>0,'msg'=>'系统错误，请刷新页面后重试！']);
            }
            
            if(!check_email($email)){
                return json(['status'=>0,'msg'=>'邮箱格式错误！']);
            }

            $code_info = Db::name('mail_code')->where(['type'=>'register', 'sn'=>$register_id, 'code'=>$code])->find();
            if(!$code_info){
                return json(['status'=>0,'msg'=>'注册码错误！']);
            }
            if(!$password){
                return json(['status'=>0,'msg'=>'请填写登录密码！']);
            }

            if(!captcha_check($captcha)){
                return json(['status'=>0,'msg'=>'验证码错误！']);
            };
               

            $user = Db::name('users')->where('email',$email)->find();
            if($user){
                return json(['status'=>0,'msg'=>'该账号已被注册！']);
            }
            
            $time = time();
            $ip = $this->ip;
            $client = $this->client;

            $inser_date = [
                'email'     => $email,
                'email_verification'    => 1,
                'nickname'  => $email,
                'password'  => pwd_encryption($password),
                'register_method'   => 'email',
                'register_time' => $time,
                'login_time'    => $time,
            ];
            
            $user_id = Db::name('users')->insertGetId($inser_date);

            if($user_id){
                Db::name('mail_code')->where(['type'=>'register', 'sn'=>$register_id, 'code'=>$code])->delete();

                $user = Db::name('users')->find($user_id);
                Session::set('user', $user);

                $log_data = [
                    'user_id'   => $user_id,
                    'type'  =>  'email',
                    'ip'    =>  $ip,
                    'client'    => $client,
                    'addtime'   => $time,
                ];

                Db::name('user_login_log')->insert($log_data);

                $re_url = Session::has('re_url') ? Session::get('re_url') : '/index/index/index';

                return json(['status'=>1,'msg'=>'注册成功，正在跳转...','url'=>$re_url]);
            }

            return json(['status'=>0,'msg'=>'注册失败，请重试！']);
            exit;
        }

        $this->assign('register_id', md5(time().rand(1000,9999).rand(1000,9999)));
        return $this->fetch();
    }
}