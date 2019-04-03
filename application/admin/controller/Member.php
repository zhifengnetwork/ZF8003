<?php

/**
 * 会员控制器
 * ---------------------------------------------------------
 * Author: ppc
 * Date: 2019-04-03
 */

namespace app\admin\controller;

use think\Db;
use think\Loader;
use app\common\model\Users;

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
        $member = Loader::validate('Member');
        $user = new Users;
        $return = ['status' => 0, 'msg' => '参数错误'];//初始化返回信息
        if ($data['act'] == "add") {
            if (!$member->scene('add')->check($data)) {
                $return = ['status' => 0, 'msg' => $member->getError()];
            } else {
                $is_distribut = ($data['is_distribut'] == 1) ? $data['is_distribut'] : 0;
                $result = array(
                    'nickname'       => $data['nickname'],
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
        }

        if ($data['act'] == "del") {
            $id = json_decode($data['id'],true);
            $bool = $user->destroy($id);

            if ($bool) {
                $return = array('code' => 1, 'msg' => "删除成功！");
            } else {
                $return = array('code' => 0, 'msg' => "删除失败！");
            }
        }

        if ($data['act'] == "status") {
            $status = intval($data['status']);
            $status = ($status == 1) ? $status : 0;
            $bool = $user->where('id',$data['id'])->update(['status'=>$status]);
            
            if ($bool) {
                $return = array('code' => 1);
            } else {
                $return = array('code' => 0);
            }
        }

        return json($return);
    }
}

