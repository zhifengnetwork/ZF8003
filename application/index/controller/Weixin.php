<?php
namespace app\index\controller;
use think\Db;

class Weixin{


    public function native_notify(){
        
        $data = file_get_contents("php://input");
    	if ($data) {
            $re = $this->xmlToArray($data);
            
            Db::name('wx_temp')->insert(['text' => json_encode($re)]);
        }

    }
 
    public function xmlToArray($xml)
    {
    	$obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		$json = json_encode($obj);
		$arr = json_decode($json, true);  
		return $arr;
    }

}