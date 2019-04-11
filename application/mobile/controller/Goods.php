<?php
namespace app\mobile\controller;

use think\Db;

class Goods extends Base
{
    public function index()
    {
        return $this->fetch();
    }
    /**
     * 分类列表显示
     */
    public function categoryList()
    {
        return $this->fetch();
    }
    /**
     * 商品列表显示
     */
    public function goodsList()
    {
        return $this->fetch();
    }   
}
