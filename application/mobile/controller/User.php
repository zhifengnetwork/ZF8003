<?php
namespace app\mobile\controller;

/**
 * Author: ppc
 * Date: 2019-4-10
 */
class User extends Base
{
    /** 
     * 我的
    */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 我的地址
     */
    public function my_site()
    {
        return $this->fetch();
    }

    /**
     * 我的二维码
     */
    public function qr_code()
    {
        return $this->fetch();
    }

    /**
     * 设置
     */
    public function set_up()
    {
        return $this->fetch();
    }
}