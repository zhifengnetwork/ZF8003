<?php
namespace app\index\controller;

use think\Controller;
use think\Db;
class Gene extends Base
{
    public function index()
    {


        return $this->fetch();
    }
}