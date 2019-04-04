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
        $list = $user->paginate(10);
        
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
    public function edit()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if (!$id) {
            echo "<script>alert('该用户不存在')</script>";
            exit;
        }
        $act = "edit";
        $user = Db::name('users')->where('id',$id)->field('password,openid,unionid,payment_password',true)->find();
        $group = Db::name('user_group')->select();
        
        $this->assign('act',$act);
        $this->assign('info',$user);
        $this->assign('group',$group);
        return $this->fetch();
    }

    public function change_password()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (!$id) {
            echo "<script>alert('该用户不存在')</script>";
            exit;
        }
        $act = 'pwd';
        $info = Db::name('users')->where('id',$id)->field('id,nickname')->find();

        $this->assign('act',$act);
        $this->assign('info',$info);
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
                $return = array('status' => 0, 'msg' => $member->getError());
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

        if ($data['act'] == "edit") {
            if (!$member->scene('edit')->check($data)) {
                $return = array('status' => 0, 'msg' => $member->getError());
            } else {
                $is_distribut = (intval($data['is_distribut']) == 1) ? intval($data['is_distribut']) : 0;
                $status = (intval($data['status']) == 1) ? intval($data['status']) : 0;
                $money = intval($data['money']);

                $result = array(
                    'nickname'       => $data['nickname'],
                    'mobile'         => $data['mobile'],
                    'email'          => $data['email'],
                    'group_id'       => $data['group_id'],
                    'is_distributor' => $is_distribut,
                    'status'         => $status,
                );


                if ($money) {
                    $result['money'] = $money;
                }

                $bool = $user->where('id',$data['id'])->update($result);

                if ($bool) {
                    $return = array('code' => 1, 'msg' => "修改成功！");
                } else {
                    $return = array('code' => 0, 'msg' => "修改失败！");
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

        if ($data['act'] == 'pwd') {
            pwd_encryption($data['password1']);
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

