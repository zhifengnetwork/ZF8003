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