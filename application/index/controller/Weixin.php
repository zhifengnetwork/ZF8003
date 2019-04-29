<?php
namespace app\index\controller;

use think\Db;
use think\Session;
use think\Request;

class Weixin{

    public $weixin_config;

    public function index(){
        $this->wx_config(true);

        $data = file_get_contents("php://input");

        # 接入微信服务器 | 验证接入 | 接入成功，自动变更服务器接入状态 wait_access = 1
        if ($this->weixin_config['wait_access'] == 0) {
            ob_clean();
            $signature = isset($_GET["signature"]) ? $_GET["signature"] : '';
            $timestamp = isset($_GET["timestamp"]) ? $_GET["timestamp"] : '';
            $nonce = isset($_GET["nonce"]) ? $_GET["nonce"] : '';
            $echostr = isset($_GET["echostr"]) ? $_GET["echostr"] : '';

            $token = $this->weixin_config['weixin_token'];
            $tmpArr = array($token, $timestamp, $nonce);
            sort($tmpArr, SORT_STRING);
            $tmpStr = implode( $tmpArr );
            $tmpStr = sha1( $tmpStr );

            if($tmpStr == $signature){
                echo $echostr;
                Db::name('config')->where(['name'=>'wait_access', 'type'=>'weixin_config'])->update(['value' => 1]);
            }else{
                echo false;
            }
            exit;
        }


        if($data){
            $re = $this->xmlToArray($data);
            Db::name('wx_temp')->insert(['content'=>json_encode($re)]);

            $this->SaveWxPushMessage($re);





            
        }


        # 拒绝再次访问 | 默认处理
        exit('success');
    }

    public function test(){

      
        echo 123;exit;
    }

    # 储存微信推送过来的消息
    public function SaveWxPushMessage($data){
        $type = isset($data['MsgType']) ? $data['MsgType'] : '';
        $tousername = isset($data['ToUserName']) ? $data['ToUserName'] : '';
        $fromusername = isset($data['FromUserName']) ? $data['FromUserName'] : '';

        if(!$type || !$tousername || !$fromusername){
            return '';
        }

        foreach($data as $k => $v){
            $d[strtolower($k)] = $v;
        }
        Db::name('wx_messages')->insert($d);

    }


    # 微信配置
    public function wx_config($clear = false){
        if($clear){
            Session::delete('wx_config');
            $config = false;
        }else{
            $config = Session::has('wx_config') ? Session::get('wx_config') : '';
        }
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

    # XML 转 数组
    public function xmlToArray($xml)
    {
    	$obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		$json = json_encode($obj);
		$arr = json_decode($json, true);  
		return $arr;
    }


}