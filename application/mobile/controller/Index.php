<?php
namespace app\mobile\controller;

use think\Db;
use PHPMailer\PHPMailer\PHPMailer;

class Index extends Base
{
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

        // parent::GetOpenid();

    }

    # 正常登录
    public function login(){

        

        return $this->fetch();
    }

    # 注册
    public function register(){
        $code = rand(100000,999999);
        $param = [
            'host'      => 'smtp.qq.com',
            'username'  => '1142506197@qq.com',
            'password'  => 'fbssodalnjkkibbg',
            'secure'    => 'ssl',
            'port'      => '456',
            'nickname'  => 'rock',
            'to'        => '15766485478@163.com',
            'title'     => '注册码',
            'body'      => '<h1>注册码：'.$code.'</h1>',
            'altBody'   => '注册码：'.$code,
        ];
        
        // $res = $this->send_mail();
        dump(order_sn());
        exit;
        return $this->fetch();
    }


    /**
     * 发送邮件
     */
    // public function send_mail(){
        
    //     $code = rand(100000,999999);
    //     $mail = new PHPMailer(true);
        
    //     try {
    //         //服务器配置
    //         $mail->CharSet ="UTF-8";                     //设定邮件编码
    //         $mail->SMTPDebug = 0;                        // 调试模式输出
    //         $mail->isSMTP();                             // 使用SMTP
    //         $mail->Host = 'smtp.qq.com';                // SMTP服务器
    //         $mail->SMTPAuth = true;                      // 允许 SMTP 认证
    //         $mail->Username = '1142506197@qq.com';                // SMTP 用户名  即邮箱的用户名
    //         $mail->Password = 'fbssodalnjkkibbg';             // SMTP 密码  部分邮箱是授权码(例如163邮箱)
    //         $mail->SMTPSecure = 'ssl';                    // 允许 TLS 或者ssl协议
    //         $mail->Port = '456';                            // 服务器端口 25 或者465 具体要看邮箱服务器支持
        
    //         $mail->setFrom('1142506197@qq.com', 'rock');  //发件人
    //         $mail->addAddress('15766485478@163.com');  // 收件人
    //         $mail->addReplyTo('1142506197@qq.com', 'rock'); //回复的时候回复给哪个邮箱 建议和发件人
        
    //         //Content
    //         $mail->isHTML(true);                                  // 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容
    //         $mail->Subject = '注册码';
    //         $mail->Body    = '<h1>注册码：'.$code.'</h1>';
    //         $mail->AltBody = '注册码：'.$code;
            
    //         $mail->send();
    //         return true;
    //     } catch (Exception $e) {
    //         return $mail->ErrorInfo;
    //     }
    // }

}
