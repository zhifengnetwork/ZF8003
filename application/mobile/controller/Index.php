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

        if($this->user_id && in_array($this->action,['login','wx_sign','register'])){
            $this->redirect('user/index');
        }

    }


    public function index()
    {
        
        # 推荐商品
        $recom_goods = Db::query("select `id`,`name`,`desc`,`thumb`,`price` from `zf_goods` where `type` = 3 and `is_del` = 0 order by `utime` desc");
        $this->assign('recom_goods', $recom_goods);

        # 微解读
        $article_cate = Db::query("select `id`,`name` from `zf_category` where `type` = 'article' and `is_view` = 1 and `is_lock` = 0");
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

            // dump($article);exit;
            
            $this->assign('article', $article);
            
            $shareUp1 = input('shareUp');
            if($shareUp1){
                Session::set('shareUp1',$shareUp1);
            }
            

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
        $time = time();
        $data = $this->GetOpenid();
        if(isset($data['openid'])){
            $user = Db::name('users')->where('openid',$data['openid'])->find();
            if($user){
                # 已注册用户，直接登录
                if(!$user['unionid'] && isset($data['unionid'])){
                    Db::name('users')->where('id',$user['id'])->update(['unionid' => $data['unionid']]);
                }
                Session::set('user', $user);
                $user_id = $user['id'];
            }else{

                # 未注册的用户，注册新账号
                $first_leader = 0;
                if(Session::has('shareUp1')){
                    $first_leader = Session::get('shareUp1');
                }

                $jifen = Db::name('jifen_set')->find();

                $inser_data = [
                    'nickname' => $data['nickname'],
                    'sex'   => $data['sex'],
                    'openid'    => $data['openid'],
                    'unionid'   => isset($data['unionid']) ? $data['unionid'] : '',
                    'register_method'   => 'weixin',
                    'first_leader' => $first_leader,
                    'register_time' => $time,
                    'login_time'    => $time,
                    'integral'      => $jifen['reg_jifen'],
                    'avatar'    => $data['head_pic']
                ];
                
                $user_id = Db::name('users')->insertGetId($inser_data);
                if(!$user_id){
                    layer_error('系统错误！请重试！');
                }

                $reg_jf_log_data['user_id'] = $user_id;
                $reg_jf_log_data['son_user_id'] = 0;
                $reg_jf_log_data['type'] = 3;
                $reg_jf_log_data['jifen'] = $jifen['reg_jifen'];
                $reg_jf_log_data['add_time'] = time();
                $this->jifen_log($reg_jf_log_data);

                if($first_leader){
                    //注册邀请送积分
                    $jifen = Db::name('jifen_set')->find();
                    $jifen = isset($jifen['jifen']) ? $jifen['jifen'] : 0;
                    $yjf = Db::name('users')->where('id',$first_leader)->setInc('integral',$jifen);
                    
                    //积分记录
                    $jf_log_data['user_id'] = $first_leader;
                    $jf_log_data['son_user_id'] = $user_id;
                    $jf_log_data['type'] = 1;
                    $jf_log_data['jifen'] = $jifen;
                    $jf_log_data['add_time'] = time();
                    $this->jifen_log($jf_log_data);
                }

                $user = Db::name('users')->find($user_id);
                Session::set('user', $user);
            }

            $log_data = [
                'user_id'   => $user_id,
                'type'  =>  'weixin',
                'ip'    =>  $this->ip,
                'client'    => $this->client,
                'addtime'   => $time,
            ];

            Db::name('user_login_log')->insert($log_data);

            $re_url = Session::has('re_url') ? Session::get('re_url') : '/mobile/user/index';
            $this->redirect($re_url);


        }else{
            echo '<h1 style="text-align:center;height:300px;line-height:300px;">微信登陆失败，请重试！</h1>';
        }
        exit;
    }

    # 正常登录
    public function login(){
        if($_POST){
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';
            $captcha = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';
            $email = base64_decode($email);
            $password = base64_decode($password);

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

            $first_leader = 0;
            if(Session::has('shareUp1')){
                $first_leader = Session::get('shareUp1');
            }

            $jifen = Db::name('jifen_set')->find();

            $inser_date = [
                'email'     => $email,
                // 'email_verification'    => 1,
                'nickname'  => $email,
                'password'  => pwd_encryption($password),
                'register_method'   => 'email',
                'first_leader'   => $first_leader,
                'register_time' => $time,
                'login_time'    => $time,
                'integral'      => $jifen['reg_jifen'],
            ];
            
            $user_id = Db::name('users')->insertGetId($inser_date);

            if($user_id){
                //积分记录
                $reg_jf_log_data['user_id'] = $user_id;
                $reg_jf_log_data['son_user_id'] = 0;
                $reg_jf_log_data['type'] = 3;
                $reg_jf_log_data['jifen'] = $jifen['reg_jifen'];
                $reg_jf_log_data['add_time'] = time();
                $this->jifen_log($reg_jf_log_data);

                Db::name('mail_code')->where(['type'=>'register', 'sn'=>$register_id, 'code'=>$code])->delete();

                if($first_leader){
                    //注册邀请送积分
                    
                    $jifen = isset($jifen['jifen']) ? $jifen['jifen'] : 0;
                    $yjf = Db::name('users')->where('id',$first_leader)->setInc('integral',$jifen);
                    
                    //积分记录
                    $jf_log_data['user_id'] = $first_leader;
                    $jf_log_data['son_user_id'] = $user_id;
                    $jf_log_data['type'] = 1;
                    $jf_log_data['jifen'] = $jifen;
                    $jf_log_data['add_time'] = time();
                    $this->jifen_log($jf_log_data);
                }

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
        if($this->user_id && !$this->user['email']){
            layer_error('请先设置登录邮箱！',true,'/mobile/user/edit_email');
        }

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

            $code_info = Db::name('mail_code')->where(['type'=>'edit_pass', 'sn'=>$pass_id])->find();
            if(!$code_info){
                return json(['status'=>0,'msg'=>'验证码错误！']);
            }
            if($code_info['email'] != $email){
                return json(['status'=>0,'msg'=>'验证码错误！']);
            }
            if($code_info['code'] != $code){
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


        $this->assign('email', $this->user['email'] ? $this->user['email'] : '');
        $this->assign('pass_id', md5(time().rand(1000,9999).rand(1000,9999)));
        return $this->fetch();
    }


    # 设置| 修改支付密码
    public function edit_paypass(){
        if($this->user_id && !$this->user['email']){
            layer_error('请先设置登录邮箱！',true,'/mobile/user/edit_email');
        }
        
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

            $code_info = Db::name('mail_code')->where(['type'=>'edit_paypass', 'sn'=>$pass_id])->find();
            if(!$code_info){
                return json(['status'=>0,'msg'=>'验证码错误！']);
            }
            if($code_info['email'] != $email){
                return json(['status'=>0,'msg'=>'验证码错误！']);
            }
            if($code_info['code'] != $code){
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
            $sql = "update `zf_users` set `payment_password` = '$password' where `id` = '$user[id]'";
            $res = Db::execute($sql);

            if($res){
                $user = Db::name('users')->find($user['id']);
                Session::set('user',$user);
                $this->user = $user;
                $re_url = Session::has('re_url') ? Session::get('re_url') : '/mobile/user/index';
                return json(['status'=>1,'msg'=>'支付密码重置成功', 'url'=> $re_url]);
            }

            
            return json(['status'=>0,'msg'=>'支付密码重置失败，请重试！']);
            exit;
        }


        $this->assign('email', $this->user['email'] ? $this->user['email'] : '');
        $this->assign('pass_id', md5(time().rand(1000,9999).rand(1000,9999)));
        return $this->fetch();
    }

     # 发送重置支付密码邮件
     public function send_editpaypass_mail(){
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
                'title'     => $conf['edit_paypass_title'] ? $conf['edit_paypass_title'] : '重置支付密码',
                'body'      => $conf['edit_paypass_body'] ? str_replace('{{$code}}', $code, $conf['edit_paypass_body']) : "<h1>您正在重置支付密码，验证码：$code</h1>",
                'altbody'   => $conf['edit_paypass_altbody'] ? str_replace('{{$code}}', $code, $conf['edit_paypass_altbody']) : "您正在重置支付密码，验证码：$code",
            ];
            
            if($this->base_send_mail($param)){
                $time = time();
                Db::execute("delete from `zf_mail_code` where `type` = 'edit_paypass' and `sn` = '$pass_id'");
                Db::execute("insert into `zf_mail_code` (`sn`,`email`,`code`,`addtime`,`type`) values ('$pass_id', '$email', '$code', '$time', 'edit_paypass')");
                return json(['status'=>1]);
            }else{
                return json(['status'=>0,'msg'=>'发送失败！']);
            }
        }
        exit('null');
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
                'title'     => $conf['edit_pass_title'] ? $conf['edit_pass_title'] : '重置登录密码',
                'body'      => $conf['edit_pass_body'] ? str_replace('{{$code}}', $code, $conf['edit_pass_body']) : "<h1>您正在修改登录密码，验证码：$code</h1>",
                'altbody'   => $conf['edit_pass_altbody'] ? str_replace('{{$code}}', $code, $conf['edit_pass_altbody']) : "您正在修改登录密码，验证码：$code",
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
                'title'     => $conf['register_title'] ? $conf['register_title'] : '账号注册',
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

    # 发送绑定邮箱邮件
    public function send_editemail_mail(){
        $pass_id = isset($_POST['pass_id']) ? trim($_POST['pass_id']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';

        if($pass_id && $email){
            
            $user = Db::name('users')->where('email',$email)->find();
            if($user){
                return json(['status'=>0,'msg'=>'邮箱已被使用！']);
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
                'title'     => $conf['bind_email_title'] ? $conf['bind_email_title'] : '绑定登录邮箱账号',
                'body'      => $conf['bind_email_body'] ? str_replace('{{$code}}', $code, $conf['bind_email_body']) : "<h1>您正在绑定登录邮箱账号，验证码：$code</h1>",
                'altbody'   => $conf['bind_email_altbody'] ? str_replace('{{$code}}', $code, $conf['bind_email_altbody']) : "您正在绑定登录邮箱账号，验证码：$code",
            ];
           
            if($this->base_send_mail($param)){
                $time = time();
                Db::execute("delete from `zf_mail_code` where `type` = 'edit_mail' and `sn` = '$pass_id'");
                Db::execute("insert into `zf_mail_code` (`sn`,`email`,`code`,`addtime`,`type`) values ('$pass_id', '$email', '$code', '$time', 'edit_mail')");
                return json(['status'=>1]);
            }else{
                return json(['status'=>0,'msg'=>'发送失败！']);
            }
        }
        exit('null');
    }

}
