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

class Base extends Controller
{

    public function __construct()
    {
        parent::__construct();


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



}




