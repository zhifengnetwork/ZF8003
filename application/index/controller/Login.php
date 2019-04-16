<?php
namespace app\index\controller;

use think\Controller;

class Login extends Controller
{
    public function index()
    {
        return $this->redirect('index/login/login');
    }

    /**
     * 登录
     */
    public function login()
    {
        return $this->fetch();
    }

    /**
     * 注册
     */
    public function register()
    {
        return $this->fetch();
    }
}