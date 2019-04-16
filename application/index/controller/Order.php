<?php
namespace app\index\controller;

use think\Controller;

class Order extends Base
{
    public function index()
    {
        return $this->fetch();
    }
}