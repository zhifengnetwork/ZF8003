<?php

namespace app\admin\controller;

class Article extends Base
{
    public function article_list()
    {
        return $this->fetch();
    }
}