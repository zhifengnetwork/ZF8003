<?php

/**
 * 会员控制器
 * ---------------------------------------------------------
 * Author: ppc
 * Date: 2019-04-03
 */

namespace app\admin\controller;

use think\Db;
use app\common\model\Users;
use app\common\model\UserGroup;

class Member extends Base
{
    /**
     * 会员列表
     */
    public function index()
    {
        $user = new Users;
        $list = $user->select();

        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * 添加会员
     */
    public function add()
    {
        $act = "add";
        $group = Db::name('user_group')->select();
        
        $this->assign('act',$act);
        $this->assign('group',$group);
        return $this->fetch();
    }

    /**
     * 增删改
     */
    public function handle()
    {
        // $act = input('cat');
        $data = input('post.');
        // dump($data);exit;
        return json($data);
    }
}

