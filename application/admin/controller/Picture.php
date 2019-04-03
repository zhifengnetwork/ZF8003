<?php

namespace app\admin\controller;

class Picture extends Base
{
    public function picture_list()
    {
        return $this->fetch();
    }
}