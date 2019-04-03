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
     * 编辑会员
     */
    public function eidt()
    {
        $act = "eidt";
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
        $data = input('post.');
        $user = new Users;

        if ($data['act'] == "add") {
            $is_distribut = ($data['is_distribut'] == 1) ? $data['is_distribut'] : 0;
            $result = array(
                'nickname'       => $data['username'],
                'mobile'         => $data['mobile'],
                'email'          => $data['email'],
                'group_id'       => $data['group_id'],
                'is_distributor' => $is_distribut,
                'register_time'  => time()
            );

            $bool = $user->save($result);

            if ($bool) {
                $return = array('code' => 1, 'msg' => "添加成功！");
            } else {
                $return = array('code' => 0, 'msg' => "添加失败！");
            }
        }

        return json($return);
    }
}

