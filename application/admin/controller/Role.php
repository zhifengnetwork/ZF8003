<?php

namespace app\admin\Controller;

use think\Controller;
use think\Db;
use think\Loader;
class Role extends Base
{
    public function index(){

    } 

    public function role()
    {

        // $str = '';
        //     $list=Db::name('admin_group')->select();
        //     $list1 = Db::name('admin')->select();
        //     if($list){
        //         foreach ($list as $key => $value) {
        //             # code...
        //             if($list1){
        //                 foreach ($list1 as $k => $val) {
        //                     # code...
        //                     if($val['group_id']==$value['id']){
        //                         $str = $str ? $str.','.$val['name'] : $val['name'];
        //                     }
        //                 }
        //                 $list[$key]['g_name'] = $str;
        //             }
        //             // $l = Db::name('admin')->where('group_id', $value['id'])->select();
        //         }  
        //     }

            $list = Db::name('admin_group')->select();
            $num = count($list);
            $this->assign('num', $num);
            $this->assign('list', $list);
            // dump($list);
            return $this->fetch();
    }

    public function role_add()
    {
        $list = Db::name('menu')->where('parent_id',0)->select();
        // 拼接子菜单
        foreach ($list as $key => $value) {
            # code...
            $chidren=Db::name('menu')->where('parent_id', $value['id'])->select();
            $list[$key]['chidren']=$chidren;
           
        }
        // dump($list);
        $this->assign('list', $list);
        // Db::name('menu')->where()->select();
        
        return $this->fetch();
    }
    public function role_edit()
    {
        $id = input('id');
        if($id){
            $role_info=Db::name('admin_group')->where('id', $id)->find();
            // dump($role_name);
            $list = Db::name('menu')->where('parent_id', 0)->select();
            // 拼接子菜单
            foreach ($list as $key => $value) {
                # code...
                $chidren = Db::name('menu')->where('parent_id', $value['id'])->select();
                $list[$key]['chidren'] = $chidren;
            }
            // 权限分割
            $array = explode(',',$role_info['jurisdiction']);
            $this->assign('array', $array);
            $this->assign('list', $list);
            $this->assign('info', $role_info);
        } 

        // Db::name('menu')->where()->select();

        return $this->fetch();
    }
    public function handle(){
        $data = input('post.');

        if($data['act'] == 'add'){
            
            $adminValidate = Loader::Validate('Role');
            if (!$adminValidate->check($data)) {
                $baocuo = $adminValidate->getError();
                return json(['status' => -1, 'msg' => $baocuo]);
            }
            if(empty($data['jurisdiction'])){
                return json(['status' => -1, 'msg' => '请选择权限']);
            } 
            // unset($data['act']);
            $a = implode(',', $data['jurisdiction']);

            $data1=[
                'name'         => $data['name'], 
                'jurisdiction' => $a,
                'addtime' => time(),
            ];
            $res = Db::name('admin_group')->insert($data1);
            //此处插入日志
        }
        if ($data['act'] == 'edit') {
        
            $adminValidate = Loader::Validate('Role1');
            if (!$adminValidate->check($data)) {
                $baocuo = $adminValidate->getError();
                return json(['status' => -1, 'msg' => $baocuo]);
            }
            if (empty($data['jurisdiction'])) {
                return json(['status' => -1, 'msg' => '请选择权限']);
            }
            unset($data['act']);
            $data['jurisdiction'] = implode(',', $data['jurisdiction']);
            $data['updatetime'] = time();

            $res = Db::name('admin_group')->where('id',$data['id'])->update($data);
            //此处插入日志
        }
        if($res){
            return json(['status' => 1,  'msg'  => '操作成功']);  
        }else{
            return json(['status' => -1, 'msg'  => '操作失败']);  
        }
    }

    // 删除和批量删除
    public function del(){
        $data = input('post.');
        if($data['act'] == 'batchdel'){
            $id = json_decode($data['id'], true);
            $where['id'] = array('in', $id);
            $res = Db::name('admin_group')->where($where)->delete();
        }else{
            if ($data['id'] > 1) {
                $res = Db::name('admin_group')->where('id', $data['id'])->delete();
            }else{
                return json(['status' => -1, 'msg' => '超级管理员不能删除！']);
            } 
        }
       
        if($res){
            return json(['status'=>1,'msg'=>'操作成功']);
        }else{
            return json(['status'=>-1,'msg'=>'操作失败']);
        }
        // Db::name('admin')->where('name', $data['name'])->select();
    }

    function adminLog($log_info)
    {
        $add['log_time'] = time();
        $add['admin_id'] = session('admin_id');
        $add['log_info'] = $log_info;
        // $add['log_ip'] = request()->ip();
        // $add['log_url'] = request()->baseUrl();
        M('admin_log')->add($add);
    }
}