<?php
namespace app\index\controller;

use think\Controller;
use think\Db;
use think\Session;
class Index extends Base
{
    

    public function index()
    {

        return $this->fetch();
    }

    /**
     * 导入数据
     */
    public function import_data()
    {
        return $this->fetch();
    }


    # 发送注册码邮件
    public function send_register_mail()
    {
        $register_id = isset($_POST['register_id']) ? trim($_POST['register_id']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';

        if ($register_id && $email) {

            $user = Db::name('users')->where('email', $email)->find();
            if ($user) {
                return json(['status' => 0, 'msg' => '该账号已被注册！']);
            }

            $code = rand(100000, 999999);

            $config = Db::query("select `name`,`value` from `zf_config` where `type` = 'email_setting'");
            if (empty($config)) {
                return json(['status' => 0, 'msg' => '系统未设置相关参数，请联系管理员']);
            }
            foreach ($config as $v) {
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

            if ($this->base_send_mail($param)) {
                $time = time();
                Db::execute("delete from `zf_mail_code` where `type` = 'register' and `sn` = '$register_id'");
                Db::execute("insert into `zf_mail_code` (`sn`,`email`,`code`,`addtime`,`type`) values ('$register_id', '$email', '$code', '$time', 'register')");
                return json(['status' => 1]);
            } else {
                return json(['status' => 0, 'msg' => '注册码发送失败！']);
            }
        }
        exit('null');
    }


    public function logout(){
        $is_logout = input('post.is_logout');
        if($is_logout == 1){
            Session::clear();
            return json(['status'=>1,'msg'=>'退出成功！']);      
        }else{
            return json(['status'=>1,'msg'=>'退出失败！']); 
        }
    }
}
