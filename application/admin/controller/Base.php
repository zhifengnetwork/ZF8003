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
use think\Request;

class Base extends Controller
{
    public $admin;
    public $module;
    public $controller;
    public $action;
    public $ip;
    public $client;

    public function _initialize()
    {
        $this->Verification_Client();
        $this->base_web_config();

        $admin_name = session('admin_name');
        
        if ($this->controller != 'Login' && !Session::has('admin')){
            Session::clear();
            $this->redirect('Login/index');
        }

        if($this->controller == 'Login' && $this->action == 'login' && Session::has('admin')){
            $this->redirect('index/index');
        }
        
        $this->admin = Session::get('admin');



        // dump($this->admin);exit;
        $global_menu_list = $this->get_menu();
        $this->assign('global_menu_list', $global_menu_list);
    }

    # 请求验证
    public function Verification_Client(){
        $request= Request::instance();
        $this->module = $request->module();
        $this->controller = $request->controller();
        $this->action = $request->action();
        $this->ip = $request->ip();
    }

    # 获取菜单
    public function get_menu(){

        $global_menu_list = Db::query("select `id`,`name`,`icon` from `zf_menu` where `is_lock` =  0 and `parent_id` = 0 order by `sort` desc,`id` asc");
        if($global_menu_list){
            foreach($global_menu_list as $k => $v){
                $global_menu_list[$k]['last'] = Db::query("select `id`,`name`,`url` from `zf_menu` where `is_lock` = 0 and `parent_id` = '$v[id]'  order by `sort` desc,`id` asc");
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




