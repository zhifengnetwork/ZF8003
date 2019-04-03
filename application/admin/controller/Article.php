<?php

namespace app\admin\controller;

class Article extends Base
{
    public function art_list()
    {
        return $this->fetch();
    }
}