<?php
/**
 * 后台管理系统首页
 */
namespace app\admin\controller;
use think\Db;
class Index extends Base
{

    public function _initialize()
    {

        parent::_initialize();
        // Session::clear();
        $admin_name = session('admin_name');
        if (empty($admin_name)) {
            $this->redirect('Login/index');
            // $url = "http://" . $_SERVER['HTTP_HOST'] . "/index.php/admin/";
            // header("refresh:1;url=$url");
            // exit;
        }
    }

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

        // dump($_SERVER);exit;
        return $this->fetch();
    }
    

    public function test(){



        echo 'test';exit;
    }
}