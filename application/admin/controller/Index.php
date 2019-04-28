<?php
/**
 * 后台管理系统首页
 */
namespace app\admin\controller;
use think\Db;
use think\Session;
class Index extends Base
{

    public function index()
    {

        $admin_id = session('admin_id');
        
        $info  =  Db::name('admin')->where('id',$admin_id)->field('name,group_id,is_super')->find();
        $group =  Db::name('admin_group')->where('id', $info['group_id'])->value('name');
        if($info['is_super'] == 1){
            $this->assign('group', '超级管理员');
        }else{
            $this->assign('group', $group);
        }
        
        $this->assign('admin_info',$info);
        return $this->fetch();
    }

    public function welcome(){

        # 问候语
        $greetings = '';
        $admin_name = Session::get('admin_name'); 
        $h = date('H',time());
        if($h > 0 && $h < 6){
            $greetings = '夜深了！' . $admin_name . ' 注意休息！';
        }
        if($h > 5 && $h < 12){
            $greetings = '早上好！' . $admin_name;
        }
        if($h > 11 && $h < 14){
            $greetings = '中午好！' . $admin_name;
        }
        if($h > 13 && $h < 19){
            $greetings = '下午好！' . $admin_name;
        }
        if($h > 16 && $h <= 24 ){
            $greetings = '晚上好！' . $admin_name;
        }

        $html = '<h1 style="padding-top:80px;text-align:center;">欢迎使用 '.Session::get('web_setting.web_name').' 后台管理系统</h1>';
        $html .= '<h1 style="padding-top:80px;text-align:center;">'.$greetings.'</h1>';
        return $html;
    }
    

    public function test(){



        echo 'test';exit;
    }
}