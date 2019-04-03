<?php

namespace app\admin\controller;

class Product extends Base
{
    public function brand()
    {
        return $this->fetch();
    }
}