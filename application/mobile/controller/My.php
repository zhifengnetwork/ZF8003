<?php
namespace app\mobile\controller;

class My extends Base
{
    public function my()
    {
        return $this->fetch();
    }
}