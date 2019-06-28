<?php
namespace app\index\controller;

use think\Controller;

class Referral extends Base
{
    public function index()
    {
        return $this->redirect('index/referral/referral');
    }

    public function referral()
    {
        return $this->fetch();
    }
}