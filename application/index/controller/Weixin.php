<?php
namespace app\index\controller;

use think\Db;
use think\Session;
use think\Request;

class Weixin{

    public $weixin_config;

    public function native_notify(){
        
        $data = file_get_contents("php://input");

    	

    }
 

    # 微信配置
    public function wx_config(){
        $config = Session::has('wx_config') ? Session::get('wx_config') : '';
        if(!$config){
            $config = Db::name('config')->where('type','weixin_config')->select();
            foreach($config as $v){
                $conf[$v['name']] = $v['value'];
            }
            Session::set('wx_config',$conf);
            $config = $conf;
        }
        
        if(!$config){

            return layer_error('管理员未配置微信登录相关信息，功能未启用！');
        }
        

        $this->weixin_config = $config;
        return $config;
    }

    public function xmlToArray($xml)
    {
    	$obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		$json = json_encode($obj);
		$arr = json_decode($json, true);  
		return $arr;
    }

}