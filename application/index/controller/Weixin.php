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

    public function login(){
        $host = urlencode('http://zf8003.zhifengwangluo.c3w.cc/index/weixin/hd');
        $appid = 'wx35fd392ad0d3b7be';
        $url = "https://open.weixin.qq.com/connect/qrconnect?appid={$appid}&redirect_uri={$host}&response_type=code&scope=snsapi_login&state=STATE#wechat_redirect";
        header("location:$url");
    }

  
    public function hd(){
        $data = input('get.');

        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx35fd392ad0d3b7be&secret=aeb753813c5e6d538905daeda4bc4932&code={$data['code']}&grant_type=authorization_code";
        $res = $this->curl($url);
        echo '<pre>';
        print_r($res);
    }

    public function curl($url){
        // 1. 初始化
        $ch = curl_init();
        // 2. 设置选项，包括URL
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_HEADER,0);
        // 3. 执行并获取HTML文档内容
        $output = curl_exec($ch);
        if($output === FALSE ){
        echo "CURL Error:".curl_error($ch);
        }
        // 4. 释放curl句柄
        curl_close($ch);
        return $output;
    }

}