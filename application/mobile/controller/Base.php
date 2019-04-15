<?php

/**
 * 前台模块公共类
 */

namespace app\mobile\controller;

use think\Controller;
use think\Db;
use think\Session;
use think\Request;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

class Base extends Controller
{

    public $session_id;
    public $weixin_config;
    public $user;
    public $user_id;
    public $module;
    public $controller;
    public $action;
    public $ip;
    public $client;

    public function _initialize()
    {
        $this->Verification_Client();




    }

    # 请求验证
    public function Verification_Client(){
        $request= Request::instance();
        $this->module = $request->module();
        $this->controller = $request->controller();
        $this->action = $request->action();
        $this->ip = $request->ip();

        $is_mobile = $request->isMobile();
        if($is_mobile){
            $this->client = 'mobile';
        }else{
            $this->client = 'pc';
        }
    }


    # 用户验证
    public function Verification_User(){
        if(Session::has('user')){
            $this->user = Session::get('user');
            $this->user_id = Session::get('user.id');
        }else{
            layer_error('请先登录！', false);
            $this->redirect('Index/login');
        }
    }


    # 微信配置
    public function wx_config(){
        $config = Session::get('wx_config');
        if(!$config){
            $config = Db::name('config')->where('type','weixin_config')->select();
            foreach($config as $v){
                $conf[$v['name']] = $v['value'];
            }
            Session::set('wx_config',$conf);
        }
        
        if(!$config){

            return layer_error('管理员未配置微信登录相关信息，功能未启用！');
        }
        

        $this->weixin_config = $config;
        return $config;
    }


    # 获取微信Token
    public function get_weixin_global_token(){

        $weixin_config = $this->weixin_config;
        
        if(empty($weixin_config['weixin_appid']) || empty($weixin_config['weixin_appsecret'])){
            
            return layer_error('管理员未配置微信登录相关信息，功能未启用！');
        }
        
        if(!empty($weixin_config['weixin_access_token']) && (!empty($weixin_config['weixin_expires_in_time']) || $weixin_config['weixin_expires_in_time'] > time())){

            return $weixin_config;
        }

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
        $res = httpRequest($url,'GET');
        $res = json_decode($res,true);
        if(isset($res['access_token']) && !empty($res['access_token'])){
            $access_token = $res['access_token'];
            $expires_in = time() + ($res['expires_in'] - 200);

            Db::execute("update `zf_config` set `value` = '$access_token' where `name` = 'weixin_access_token' and `type` = 'weixin_config'");
            Db::execute("update `zf_config` set `value` = '$expires_in' where `name` = 'weixin_expires_in_time' and `type` = 'weixin_config'");
            $this->get_weixin_global_token();

        }else{

            return layer_error('获取微信TOKEN失败：'.$res['errmsg']);
        }
    }

    // 网页授权登录获取 OpendId
    public function GetOpenid()
    {
        $this->wx_config();

        if(Session::has('openid'))
            return Session::get('wx_user_data');
        //通过code获得openid
        if (!isset($_GET['code'])){
            //触发微信返回code码
            //$baseUrl = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
            $baseUrl = urlencode($this->get_url());
            $url = $this->__CreateOauthUrlForCode($baseUrl); // 获取 code地址
            Header("Location: $url"); // 跳转到微信授权页面 需要用户确认登录的页面
            exit();
        } else {
            //上面获取到code后这里跳转回来
            $code = $_GET['code'];
            $data = $this->getOpenidFromMp($code);//获取网页授权access_token和用户openid
            $data2 = $this->GetUserInfo($data['access_token'],$data['openid']);//获取微信用户信息
            $data['nickname'] = empty($data2['nickname']) ? '微信用户' : trim($data2['nickname']);
            $data['sex'] = $data2['sex'];
            $data['head_pic'] = $data2['headimgurl'];   
            $data['oauth_child'] = 'mp';
            Session::set('openid', $data['openid']);
            $data['oauth'] = 'weixin';
            if(isset($data2['unionid'])){
            	$data['unionid'] = $data2['unionid'];
            }
            Session::set('wx_user_data', $data);
            return $data;
        }
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
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     *
     * @return 返回构造好的url
     */
    private function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["appid"] = $this->weixin_config['weixin_appid'];
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
//        $urlObj["scope"] = "snsapi_base";
        $urlObj["scope"] = "snsapi_userinfo";
        $urlObj["state"] = "STATE"."#wechat_redirect";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
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
        $urlObj["appid"] = $this->weixin_config['weixin_appid'];
        $urlObj["secret"] = $this->weixin_config['weixin_appsecret'];
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
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
     * 发送邮件 基础方法
     * 可在模块下其他控制器调用
     */
    public function base_send_mail($param){
        // $code = rand(100000,999999);
        // $mail = new PHPMailer(true);
        // try {
        //     //服务器配置
        //     $mail->CharSet ="UTF-8";                     //设定邮件编码
        //     $mail->SMTPDebug = 0;                        // 调试模式输出
        //     $mail->isSMTP();                             // 使用SMTP
        //     $mail->Host = 'smtp.qq.com';                // SMTP服务器
        //     $mail->SMTPAuth = true;                      // 允许 SMTP 认证
        //     $mail->Username = '1142506197@qq.com';                // SMTP 用户名  即邮箱的用户名
        //     $mail->Password = 'fbssodalnjkkibbg';             // SMTP 密码  部分邮箱是授权码(例如163邮箱)
        //     $mail->SMTPSecure = 'ssl';                    // 允许 TLS 或者ssl协议
        //     $mail->Port = '465';                            // 服务器端口 25 或者465 具体要看邮箱服务器支持
        
        //     $mail->setFrom('1142506197@qq.com', 'rock');  //发件人
        //     $mail->addAddress('15766485478@163.com');  // 收件人
        //     $mail->addReplyTo('1142506197@qq.com', 'rock'); //回复的时候回复给哪个邮箱 建议和发件人
        
        //     //Content
        //     $mail->isHTML(true);                                  // 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容
        //     $mail->Subject = '注册码';
        //     $mail->Body    = '<h1>注册码：'.$code.'</h1>';
        //     $mail->AltBody = '注册码：'.$code;
            
        //     $mail->send();
        //     echo '发送成功';
        // } catch (Exception $e) {
        //     echo $mail->ErrorInfo;
        // }

        $mail = new PHPMailer(true);
        try {
            //服务器配置
            $mail->CharSet ="UTF-8";                     //设定邮件编码
            $mail->SMTPDebug = 0;                        // 调试模式输出
            $mail->isSMTP();                             // 使用SMTP
            $mail->Host = $param['host'];                // SMTP服务器
            $mail->SMTPAuth = true;                      // 允许 SMTP 认证
            $mail->Username = $param['username'];                // SMTP 用户名  即邮箱的用户名
            $mail->Password = $param['password'];             // SMTP 密码  部分邮箱是授权码(例如163邮箱)
            $mail->SMTPSecure = $param['secure'];                    // 允许 TLS 或者ssl协议
            $mail->Port = $param['port'];                            // 服务器端口 25 或者465 具体要看邮箱服务器支持
            
            $mail->setFrom($param['username'], $param['nickname']);  //发件人
            $mail->addAddress($param['to']);  // 收件人
            $mail->addReplyTo($param['username'], $param['nickname']); //回复的时候回复给哪个邮箱 建议和发件人
        
            //Content
            $mail->isHTML(true);                                  // 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容
            $mail->Subject = $param['title'];
            $mail->Body    = $param['body'];
            $mail->AltBody = $param['altbody'];
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }

    }
    
}