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
        $where['id'] = ['>', 0];
        $datemin = '';
        $datemax = '';
        $seach = isset($_GET['seach']) ? $_GET['seach'] : '';
        $page = 10;
        
        //搜索条件
        if($seach){
            $page = 0;
            $time = " 23:59:59";
            if($seach['m_conditions']){
                $m_conditions = str_replace(' ', '', $seach['m_conditions']);
                $where['nickname|mobile|email'] = ['like',"%$m_conditions%"];
            }
            if ($seach['datemin'] && $seach['datemax']) {
                $datemin = strtotime($seach['datemin']);
                $datemax = strtotime($seach['datemax'].$time);
                $where['register_time'] = [['>= time',$datemin],['<= time',$datemax],'and'];
            } elseif ($seach['datemin']) {
                $where['register_time'] = ['>= time',strtotime($seach['datemin'])];
            } elseif ($seach['datemax']) {
                $where['register_time'] = ['<= time',strtotime($seach['datemax'].$time)];
            }
        }
        
        $user = new Users;

        $list = $user->where($where)->paginate($page);
        $this->assign('seach',$seach);
        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * 分组
     */
    public function group()
    {
         $list = Db::name('user_group')->order('addtime desc')->paginate(15);
         $num  = count($list);
         $this->assign('num', $num);
         $this->assign('list',$list);
         return $this->fetch();
    }
    /**
     * 添加分组
     */
    public function groupAdd(){
        $data = input();
        if($_POST){
            $member = Loader::validate('Member');
            if(!$member->scene('groupAdd')->check($data)){
                $baocuo=$member->getError();
                return json(['status'=>-1,'msg'=> $baocuo]);
            }

            if($data['id']){   
                $data1 = [
                    'name' => $data['name'],
                    'desc' => $data['desc'],
                    'updatetime' => time()
                ];                
                $res = Db::name('user_group')->where('id',$data['id'])->update($data1);
                if ($res) {
                    $action = 'edit';
                    $log = adminLog($action);
                    return json(['status' => 1, 'msg' => '更新成功！']);
                } else {
                    return json(['status' => 0, 'msg' => '更新失败！']);
                } 
            }
            $data1 = [
              'name' => $data['name'],
              'desc' => $data['desc'],
              'addtime' => time()
            ];
            $data['addtime']=time();  
            $res = Db::name('user_group')->insert($data1);
            
            if($res){
                $action = 'add';
                $log = adminLog($action);   
                return json(['status'=>1,'msg'=>'添加成功！']);
            }else{
                return json(['status'=>0,'msg'=>'添加失败！']);
            }            
        }
        if(isset($data['id'])){
            $info = Db::name('user_group')->where('id',$data['id'])->find();
            $this->assign('info',$info); 
        } 
        return $this->fetch(); 
    }
    /**
     * 分组删除和批量删除
     */
    public function del(){
        $data = input('post.');
        // dump($data);exit;
        if($_POST){
            if($data['act'] == 'batchdel'){
                    $id = json_decode($data['id'], true);
                    $where['id'] = array('in', $id);
                    $res = Db::name('user_group')->where($where)->delete();
            }else{
                    // $is_super = Db::name('admin')->where('id',$data['id'])->value('is_super');
                    // if ($is_super == 1) {
                    //     return json(['status' => -1, 'msg' => '超级管理员不能删除！']);
                    // }else{
                        $res = Db::name('user_group')->where('id', $data['id'])->delete();
                    
                    // } 
            }
            // 日志


            if($res){
                $action = 'del';
                $log = adminLog($action);
                return json(['status'=>1,'msg'=>'操作成功']);
            }else{
                return json(['status'=>-1,'msg'=>'操作失败']);
            }
        }
 
        // Db::name('admin')->where('name', $data['name'])->select();
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

    /**
     * 后台会员修改密码
     */
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

        //添加
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

        //编辑
        if ($data['act'] == "edit") {
            if (!$member->scene('edit')->check($data)) {
                $return = array('status' => 0, 'msg' => $member->getError());
            } else {
                $is_distribut = (intval($data['is_distribut']) == 1) ? intval($data['is_distribut']) : 0;
                $status = (intval($data['status']) == 1) ? intval($data['status']) : 0;
                $money = floatval($data['money']);

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
                } elseif ($bool === 0) {
                    $return = array('code' => 0, 'msg' => "你没有任何修改");
                }else { 
                    $return = array('code' => 0, 'msg' => "修改失败！");
                }
            }
        }

        //删除
        if ($data['act'] == "del") {
            $id = json_decode($data['id'],true);
            $bool = $user->destroy($id);

            if ($bool) {
                $return = array('code' => 1, 'msg' => "删除成功！");
            } else {
                $return = array('code' => 0, 'msg' => "删除失败！");
            }
        }

        //修改密码
        if ($data['act'] == 'pwd') {
            $pwd1 = pwd_encryption($data['newpassword']);
            $pwd2 = pwd_encryption($data['newpassword2']);

            if ($pwd1 == $pwd2) {
                $bool = $user->where('id',$data['id'])->update(['password' => $pwd1]);

                if ($bool) {
                    $return = array('code' => 1, 'msg' => "成功修改密码！");
                } else {
                    $return = array('code' => 0, 'msg' => "修改密码失败！");
                }
            } else {
                $return = array('code' => 0,'msg' => "密码不一致");
            }
        }

        //更改状态
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

    /**
     * 用户详情
     */
    public function show()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (!$id) {
            echo "<script>alert('该用户不存在')</script>";
            exit;
        }

        $user = Db::name('users')->where('id',$id)->field('password,openid,unionid,payment_password',true)->find();

        $avatar = $user['avatar'];
        $is_avatar = 0;
        $path = ROOT_PATH;
        
        if (is_file($path.$avatar)) {
            $is_avatar = 1;
        }

        $this->assign('is_avatar',$is_avatar);
        $this->assign('info',$user);

        return $this->fetch();
    }


}

