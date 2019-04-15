<?php
namespace app\mobile\controller;

use think\Db;
use think\Session;

class Index extends Base
{

    public function _initialize()
    {
        parent::_initialize();

        # 删除过时的邮件记录
        Db::name('mail_code')->where(['addtime'=>['<', time() - 1800]])->delete();


    }


    public function index()
    {
        
        # 推荐商品
        $recom_goods = Db::query("select `id`,`name`,`desc`,`thumb`,`price` from `zf_goods` where `type` = 3 and `is_del` = 0 order by `utime` desc");
        $this->assign('recom_goods', $recom_goods);


        # 微解读
        $article_cate = Db::query("select `id`,`name` from `zf_category` where `type` = 'article' and `is_view` = 1");
        if($article_cate){
            $ignore = [0];
            $article['recom'] = Db::query("select `id`,`title`,`desc`,`thumb` from `zf_article` where `type` = 1 order by `utime` desc limit 3");
            if($article['recom']){
                foreach($article['recom'] as $v){
                    array_push($ignore,$v['id']);
                }
            }
            $ignore = implode("','", $ignore);
            foreach($article_cate as $k => $v){
                $article['list'][$k] = $v;
                $article['list'][$k]['list'] = Db::query("select `id`,`title`,`desc`,`thumb` from `zf_article` where `cate_id` = '$v[id]' and id not in ('$ignore') order by `utime` desc");
            }
            
            $this->assign('article', $article);

            $article_count = Db::name('article')->where('is_lock',0)->count();
            $this->assign('article_count', $article_count);
        }
        
        
        
        
        return $this->fetch();
    }


    # 研究所
    public function research(){

        return $this->fetch();
    }



    # 微信登录
    public function wx_sign(){

        $data = $this->GetOpenid();
        dump($data);
    }

    # 正常登录
    public function login(){
        if($_POST){
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';
            $captcha = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';

            if(!check_email($email)){
                return json(['status'=>0,'msg'=>'邮箱格式错误！']);
            }
            if(!$password){
                return json(['status'=>0,'msg'=>'请填写登录密码！']);
            }
            if(!captcha_check($captcha)){
                return json(['status'=>0,'msg'=>'验证码错误！']);
            };
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

            $re_url = Session::has('re_url') ? Session::get('re_url') : '/mobile/user/index';
           
            return json(['status'=>1,'msg'=>'登录成功，正在跳转...','url'=>$re_url]);

            exit;
        }
        
        captcha_src();
        return $this->fetch();
    }

    # 注册
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

                $re_url = Session::has('re_url') ? Session::get('re_url') : '/mobile/user/index';

                return json(['status'=>1,'msg'=>'注册成功，正在跳转...','url'=>$re_url]);
            }

            return json(['status'=>0,'msg'=>'注册失败，请重试！']);
            exit;
        }

        $this->assign('register_id', md5(time().rand(1000,9999).rand(1000,9999)));
        return $this->fetch();
    }


    # 退出登录
    public function logout(){

        Session::delete('user');

        return $this->redirect('Index/login');

    }

    # 忘记 | 重置 | 修改密码
    public function edit_password(){
        if($_POST){
            $pass_id = isset($_POST['pass_id']) ? trim($_POST['pass_id']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $code = isset($_POST['code']) ? trim($_POST['code']) : '';
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';
            $re_password = isset($_POST['re_password']) ? trim($_POST['re_password']) : '';

            if(!$pass_id){
                return json(['status'=>0,'msg'=>'系统错误，请刷新页面后重试！']);
            }
            
            if(!check_email($email)){
                return json(['status'=>0,'msg'=>'邮箱格式错误！']);
            }

            $code_info = Db::name('mail_code')->where(['type'=>'edit_pass', 'sn'=>$pass_id, 'code'=>$code])->find();
            if(!$code_info){
                return json(['status'=>0,'msg'=>'验证码错误！']);
            }
            if(!$password){
                return json(['status'=>0,'msg'=>'请填写登录密码！']);
            }

            $user = Db::name('users')->where('email',$email)->find();
            if(!$user){
                return json(['status'=>0,'msg'=>'邮箱错误或未注册！']);
            }

            $password = pwd_encryption($password);
            $sql = "update `zf_users` set `password` = '$password' where `id` = '$user[id]'";
            $res = Db::execute($sql);

            if($res){
                Session::delete('user');
                $re_url = Session::has('re_url') ? Session::get('re_url') : '/mobile/user/index';
                return json(['status'=>1,'msg'=>'密码重置成功，请重新登录', 'url'=> $re_url]);
            }

            return json(['status'=>0,'msg'=>'密码重置失败，请重试！']);
            exit;
        }



        $this->assign('pass_id', md5(time().rand(1000,9999).rand(1000,9999)));
        return $this->fetch();
    }

    # 发送重置密码邮件
    public function send_editpass_mail(){
        $pass_id = isset($_POST['pass_id']) ? trim($_POST['pass_id']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';

        if($pass_id && $email){
            
            $user = Db::name('users')->where('email',$email)->find();
            if(!$user){
                return json(['status'=>0,'msg'=>'邮箱错误或未注册！']);
            }

            $code = rand(100000,999999);

            $config = Db::query("select `name`,`value` from `zf_config` where `type` = 'email_setting'");
            if(empty($config)){
                return json(['status'=>0,'msg'=>'系统未设置相关参数，请联系管理员']);
            }
            foreach($config as $v){
                $conf[$v['name']] = $v['value'];
            }

            $param = [
                'host'      => $conf['host'],
                'username'  => $conf['username'],
                'password'  => $conf['password'],
                'secure'    => $conf['secure'],
                'port'      => $conf['port'],
                'nickname'  => $conf['nickname'],
                'to'        => $email,
                'title'     => $conf['edit_title'],
                'body'      => $conf['edit_body'] ? str_replace('{{$code}}', $code, $conf['edit_body']) : "<h1>您正在修改登录密码，验证码：$code</h1>",
                'altbody'   => $conf['edit_altbody'] ? str_replace('{{$code}}', $code, $conf['edit_altbody']) : "您正在修改登录密码，验证码：$code",
            ];
            
            if($this->base_send_mail($param)){
                $time = time();
                Db::execute("delete from `zf_mail_code` where `type` = 'edit_pass' and `sn` = '$pass_id'");
                Db::execute("insert into `zf_mail_code` (`sn`,`email`,`code`,`addtime`,`type`) values ('$pass_id', '$email', '$code', '$time', 'edit_pass')");
                return json(['status'=>1]);
            }else{
                return json(['status'=>0,'msg'=>'发送失败！']);
            }
        }
        exit('null');
    }

    # 发送注册码邮件
    public function send_register_mail(){

        $register_id = isset($_POST['register_id']) ? trim($_POST['register_id']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';

        if($register_id && $email){
            
            $user = Db::name('users')->where('email',$email)->find();
            if($user){
                return json(['status'=>0,'msg'=>'该账号已被注册！']);
            }

            $code = rand(100000,999999);

            $config = Db::query("select `name`,`value` from `zf_config` where `type` = 'email_setting'");
            if(empty($config)){
                return json(['status'=>0,'msg'=>'系统未设置相关参数，请联系管理员']);
            }
            foreach($config as $v){
                $conf[$v['name']] = $v['value'];
            }

            $param = [
                'host'      => $conf['host'],
                'username'  => $conf['username'],
                'password'  => $conf['password'],
                'secure'    => $conf['secure'],
                'port'      => $conf['port'],
                'nickname'  => $conf['nickname'],
                'to'        => $email,
                'title'     => $conf['register_title'],
                'body'      => $conf['register_body'] ? str_replace('{{$code}}', $code, $conf['register_body']) : "<h1>注册码：$code</h1>",
                'altbody'   => $conf['register_altbody'] ? str_replace('{{$code}}', $code, $conf['register_altbody']) : "注册码：$code",
            ];
           
            if($this->base_send_mail($param)){
                $time = time();
                Db::execute("delete from `zf_mail_code` where `type` = 'register' and `sn` = '$register_id'");
                Db::execute("insert into `zf_mail_code` (`sn`,`email`,`code`,`addtime`,`type`) values ('$register_id', '$email', '$code', '$time', 'register')");
                return json(['status'=>1]);
            }else{
                return json(['status'=>0,'msg'=>'注册码发送失败！']);
            }
        }
        exit('null');
    }

}
