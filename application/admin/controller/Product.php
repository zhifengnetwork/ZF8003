<?php

namespace app\admin\controller;

class Product extends Base
{
    /**
     * 品牌
     */
    public function brand()
    {
        return $this->fetch();
    }

    /**
     * 分类
     */
    public function category()
    {
        return $this->fetch();
    }

    /**
     * 添加分类
     */
    public function category_add()
    {
        return $this->fetch();
    }

    /**
     * 产品列表
     */
    public function product_list()
    {
        return $this->fetch();
    }
}