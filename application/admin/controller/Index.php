<?php
/**
 * 后台管理系统首页
 */
namespace app\admin\controller;

class Index extends Base
{
    
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