<?php
namespace app\index\controller;

use think\Controller;
use think\Db;
use think\Session;
class Login extends Base
{

    public function _initialize(){
        parent::_initialize();
    }

    public function index()
    {
        $this->redirect('index/login/login');
    }


    /**
     * 登录
     */
    public function login()
    {
        if(Session::has('user')){
            return $this->redirect('index/index/index');   
        }
        if($_POST){
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';
            $email = base64_decode($email);
            $password = base64_decode($password);

            if(!check_email($email)){
                return json(['status'=>0,'msg'=>'邮箱格式错误！']);
            }
            if(!$password){
                return json(['status'=>0,'msg'=>'请填写登录密码！']);
            }
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
        
        
        $wxconf = $this->wx_config();
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $url = $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').'/index/login/weixin_login';
        $state = rand(100000,999999);
        Session::set('wx_login_state', $state);

        
        $this->assign('appid',$wxconf['open_appid']);
        $this->assign('redirect_uri',UrlEncode($url));
        $this->assign('href',$sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').'/public/static/index/css/weixin_login.css');
        $this->assign('state',$state);
        return $this->fetch();
    }

    /**
     * 注册
     */
    public function register(){
        if(Session::has('user')){
            return $this->redirect('index/index/index');   
        }
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
            
            $user_id = Db::name('users')->strict(false)->insertGetId($inser_date);

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

    # 退出登录
    public function logout(){
        Session::delete('user');
        $this->redirect('/index/login/login');

    }

 
    # 微信登录
    public function weixin_login(){
        $time = time();
        if(isset($_GET['code'])){
            $code = $_GET['code'];
            if($_GET['state'] != Session::get('wx_login_state')){
                echo "<script>parent.wx_ifr_error('登录失败！未知的来源');</script>";
            }
            $data = $this->getOpenidFromMp($code);//获取网页授权access_token和用户openid
            // dump($data);
            // die;
            $data2 = $this->GetUserInfo($data['access_token'],$data['openid']);//获取微信用户信息
            $data['nickname'] = empty($data2['nickname']) ? '微信用户' : trim($data2['nickname']);
            $data['sex'] = $data2['sex'];
            $data['head_pic'] = $data2['headimgurl'];   
            $data['oauth_child'] = 'mp';
            $data['oauth'] = 'weixin';
            $data['unionid'] = '';
            if(isset($data2['unionid'])){
            	$data['unionid'] = $data2['unionid'];
            }
            
            $user = Db::name('users')->where('unionid',$data['unionid'])->find();
            if($user){
                Session::set('user', $user);
                $user_id = $user['id'];
                $log_data = [
                    'user_id'   => $user_id,
                    'type'  =>  'weixin',
                    'ip'    =>  $this->ip,
                    'client'    => $this->client,
                    'addtime'   => $time,
                ];
    
                Db::name('user_login_log')->insert($log_data);
                $re_url = Session::has('re_url') ? Session::get('re_url') : '/index/user/index';
                echo "<script>parent.wx_ifr_success('$re_url');</script>";
                exit;
            }else{
                echo "<script>parent.wx_ifr_error('账号不存在，请注册账号！');</script>";
                exit;
            }
        }
        exit;
    }
    
    /**
     *
     * 构造获取拉取用户信息(需scope为 snsapi_userinfo)的url地址     
     * @return 请求的url
     */
    private function __CreateOauthUrlForUserinfo($access_token,$openid)
    {
        $urlObj["access_token"] = $access_token;
        $urlObj["openid"] = $openid;
        $urlObj["lang"] = 'zh_CN';        
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/userinfo?".$bizString;                    
    } 

    /**
     *
     * 通过access_token openid 从工作平台获取UserInfo      
     * @return openid
     */
    public function GetUserInfo($access_token,$openid)
    {         
        // 获取用户 信息
        $url = $this->__CreateOauthUrlForUserinfo($access_token,$openid);
        $ch = curl_init();//初始化curl        
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);//设置超时
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);         
        $res = curl_exec($ch);//运行curl，结果以jason形式返回            
        $data = json_decode($res,true);            
        curl_close($ch);
        
        return $data;
    }

    /**
     *
     * 通过code从工作平台获取openid机器access_token
     * @param string $code 微信跳转回来带上的code
     *
     * @return openid
     */
    public function GetOpenidFromMp($code)
    {
        //通过code获取网页授权access_token 和 openid 。网页授权access_token是一次性的，而基础支持的access_token的是有时间限制的：7200s。
    	//1、微信网页授权是通过OAuth2.0机制实现的，在用户授权给公众号后，公众号可以获取到一个网页授权特有的接口调用凭证（网页授权access_token），通过网页授权access_token可以进行授权后接口调用，如获取用户基本信息；
    	//2、其他微信接口，需要通过基础支持中的“获取access_token”接口来获取到的普通access_token调用。
        $url = $this->__CreateOauthUrlForOpenid($code);       
        $ch = curl_init();//初始化curl        
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);//设置超时
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);         
        $res = curl_exec($ch);//运行curl，结果以jason形式返回            
        $data = json_decode($res,true);         
        curl_close($ch);
        return $data;
    }

    /**
     *
     * 拼接签名字符串
     * @param array $urlObj
     *
     * @return 返回已经拼接好的字符串
     */
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v)
        {
            if($k != "sign"){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     *
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     *
     * @return 返回构造好的url
     */
    private function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["appid"] = $this->weixin_config['open_appid'];
        // echo $this->weixin_config['open_appid'];exit;
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = "snsapi_login";
        $urlObj["state"] = "STATE"."#wechat_redirect";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/qrconnect?".$bizString;
    }


    /**
     *
     * 构造获取open和access_toke的url地址
     * @param string $code，微信跳转带回的code
     *
     * @return 请求的url
     */
    private function __CreateOauthUrlForOpenid($code)
    {
        $this->wx_config();
        $urlObj["appid"] = $this->weixin_config['open_appid'];
        $urlObj["secret"] = $this->weixin_config['open_appsecret'];
        // dump($urlObj);die;
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
    }

    /**
     * 获取当前的url 地址
     * @return type
     */
    private function get_url() {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
        return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
    } 
}