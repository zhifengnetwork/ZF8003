<?php
namespace app\index\controller;

class Buy extends Base
{
    public function index()
    {
        return $this->redirect('index/buy/buy');
    }

    public function buy()
    {
        return $this->fetch();
    }

    public function details()
    {
        return $this->fetch();
    }
}