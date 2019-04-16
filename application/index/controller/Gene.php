<?php
namespace app\index\controller;

use think\Controller;

class Gene extends Base
{
    public function index()
    {
        return $this->fetch();
    }
}