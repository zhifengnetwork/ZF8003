<?php
namespace app\index\controller;
use think\Db;
use think\Session;

class User extends Base{


    public function _initialize(){
        parent::_initialize();

        $this->Verification_User();
    }

    public function index(){



        $this->assign('info', $this->user);
        return $this->fetch();
    }




}