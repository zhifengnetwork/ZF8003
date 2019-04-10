<?php
/**
 * 后台管理系统首页
 */
namespace app\admin\controller;

class Index extends Base
{

    public function _initialize()
    {

        parent::_initialize();
        // Session::clear();
        $admin_name = session('admin_name');
        if (empty($admin_name)) {
            $this->error('请先登陆', 'Login/index');
            // $url = "http://" . $_SERVER['HTTP_HOST'] . "/index.php/admin/";
            // header("refresh:1;url=$url");
            // exit;
        }
    }

    public function index()
    {
        
        
        
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