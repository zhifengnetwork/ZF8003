<?php

namespace app\admin\controller;

class Member extends Base
{
    public function index()
    {
        return $this->fetch();
    }
}

