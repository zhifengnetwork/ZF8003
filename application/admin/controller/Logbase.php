<?php

/**
 * 
 * 后台模块公共类
 * 
 * 
 */

namespace app\admin\Controller;

use think\Controller;
use think\Db;
use think\Session;
class Logbase extends Controller
{

    public function __construct()
    {
        parent::__construct();

        $this->base_web_config();
        $admin_name = session('admin_name');
        if (!empty($admin_name)) {
            $url = "http://" . $_SERVER['HTTP_HOST'] . "/index.php/admin";
            header("refresh:1;url=$url");
            exit;
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
        // dump(Session::get('web_setting'));
        $this->assign('web_setting',Session::get('web_setting'));
    }

}




