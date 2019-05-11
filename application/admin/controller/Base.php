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
    public $weixin_config;

    public function _initialize()
    {
        $this->Verification_Client();
        $this->base_web_config();

        $admin_name = session('admin_name');
        
        if ($this->controller != 'Login' && !Session::has('admin')){
            Session::clear();
            $this->redirect('/Admin/Login/index');
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

        $sql = "select `id`,`name`,`icon` from `zf_menu` where `is_lock` =  0 and `parent_id` = 0 order by `sort` desc,`id` asc";
        if($this->ip !== '119.131.61.72'){
            $sql = "select `id`,`name`,`icon` from `zf_menu` where `is_lock` =  0 and `parent_id` = 0 and id != 32 order by `sort` desc,`id` asc";
        }
        $global_menu_list = Db::query($sql);
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
        
        $this->assign('web_setting',Session::get('web_setting'));
    }

    # 微信配置
    public function wx_config(){
        $config = Session::has('wx_config','user') ? Session::get('wx_config','user') : '';
        if(!$config){
            $config = Db::name('config')->where('type','weixin_config')->select();
            foreach($config as $v){
                $conf[$v['name']] = $v['value'];
            }
            Session::set('wx_config',$conf,'user');
            $config = $conf;
        }
        
        if(!$config){

            return layer_error('管理员未配置微信登录相关信息，功能未启用！');
        }
        

        $this->weixin_config = $config;
        return $config;
    }

    # 获取微信Token
    public function get_weixin_global_token($refresh = false){
        if($refresh){
            $this->weixin_config = '';
            Session::delete('wx_config','user');
        }
        $this->wx_config();
        $weixin_config = $this->weixin_config;
        
        if(!array_key_check($weixin_config,'weixin_appid') || !array_key_check($weixin_config,'weixin_appsecret')){
            
            return layer_error('管理员未配置微信登录相关信息，功能未启用！');
        }
        
        if( array_key_check($weixin_config,'weixin_access_token') && $weixin_config['weixin_expires_in_time'] > time() ){

            return $weixin_config;
        }

        $appid = $weixin_config['weixin_appid'];
        $appsecret = $weixin_config['weixin_appsecret'];

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
        $res = httpRequest($url,'GET');
        $res = json_decode($res,true);
        if(isset($res['access_token']) && !empty($res['access_token'])){
            $access_token = $res['access_token'];
            $expires_in = time() + ($res['expires_in'] - 200);

            $is_access_token = Db::name('config')->where(['name'=>'weixin_access_token','type'=>'weixin_config'])->column('name,type');

            if (isset($is_access_token['weixin_access_token'])) {
                Db::execute("update `zf_config` set `value` = '$access_token' where `name` = 'weixin_access_token' and `type` = 'weixin_config'");
            } else {
                Db::name('config')->insert(['name'=>'weixin_access_token','type'=>'weixin_config','value'=>$access_token]);
            }

            $is_expires = Db::name('config')->where(['name'=>'weixin_expires_in_time','type'=>'weixin_config'])->column('name,type');
            if (isset($is_expires['weixin_expires_in_time'])) {
                Db::execute("update `zf_config` set `value` = '$expires_in' where `name` = 'weixin_expires_in_time' and `type` = 'weixin_config'");
            } else {
                Db::name('config')->insert(['name'=>'weixin_expires_in_time','type'=>'weixin_config','value'=>$expires_in]);
            }
            
            $this->get_weixin_global_token();

        }else{

            return layer_error('获取微信TOKEN失败：'.$res['errmsg']);
        }
    }
}




