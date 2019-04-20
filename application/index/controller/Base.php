<?php

namespace app\index\controller;

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
        $this->base_web_config();
        $this->Verification_Client();
        if($this->client == 'mobile'){
            $this->redirect('/mobile/index/index');
        }
        
    }

    # 用户验证
    public function Verification_User(){
        if(Session::has('user')){
            $this->user = Session::get('user');
            $this->user_id = Session::get('user.id');
            
            // $this->avatar  = Session::get('user.avatar');
        }else{
            layer_error('请先登录！', false);
            $this->redirect('Login/login');
        }
    }

    # 网站基本信息设置
    public function base_web_config(){

        if(!Session::has('web_setting')){
            $config = Db::name('config')->where('type','web_setting')->select();
            if($config){
                foreach($config as $v){
                    $conf[$v['name']] = $v['value'];
                }
                $config = $conf;
                Session::set('web_setting',$config);
            }
        }
        $user = Session::has('user') ? Session::get('user') : '';
        
        $this->assign('web_setting',Session::get('web_setting'));
        
        $this->assign('user',$user);
        
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
        if(Session::has('user')){
            $this->user = Session::get('user');
            $this->user_id = Session::get('user.id');
        }

    }


    /**
     * 发送邮件 基础方法
     * 可在模块下其他控制器调用
     */
    public function base_send_mail($param){
        

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