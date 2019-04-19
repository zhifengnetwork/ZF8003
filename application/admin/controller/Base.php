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
class Base extends Controller
{

    public function __construct()
    {
        parent::__construct();

        $this->base_web_config();
        $global_menu_list = $this->get_menu();
        $this->assign('global_menu_list', $global_menu_list);
    }

    # 获取菜单
    public function get_menu(){

        $global_menu_list = Db::query("select `id`,`name`,`icon` from `zf_menu` where `is_lock` =  0 and `parent_id` = 0");
        if($global_menu_list){
            foreach($global_menu_list as $k => $v){
                $global_menu_list[$k]['last'] = Db::query("select `id`,`name`,`url` from `zf_menu` where `is_lock` = 0 and `parent_id` = '$v[id]'");
            }
        }
        return $global_menu_list;
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




