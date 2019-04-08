<?php

namespace app\admin\Controller;

use think\Controller;
use think\Db;
use think\Loader;
class Role extends Base
{
    public function role()
    {

        // $list = Db::name('admin_group')
        //     ->alias('a')
        //     ->join('admin ad', 'a.id = ad.group_id')
        //     ->field('a.*,ad.name ad_name')
        //     // ->where($where)
        //     // ->where('a.id', 'not in', '1')
        //     ->order('id asc')
        //     ->paginate(10);
        //     $this->assign('list',$list);
        //     dump($list);
        // $group = Db::name('admin_group')->select();
        // foreach ($group as $key => $value) {
        //     # code...
        //     $l = Db::name('admin')->where('group_id', $value['id'])->select();
        // }
        // 
        $str = '';
            $list=Db::name('admin_group')->select();
            $list1 = Db::name('admin')->select();
            if($list){
                foreach ($list as $key => $value) {
                    # code...
                    if($list1){
                        foreach ($list1 as $k => $val) {
                            # code...
                            if($val['group_id']==$value['id']){
                                $str = $str ? $str.','.$val['name'] : $val['name'];
                            }
                        }
                        $list[$key]['g_name'] = $str;
                    }
                    // $l = Db::name('admin')->where('group_id', $value['id'])->select();
                }  
            }
            dump($list);
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

    public function handle(){
        $data = input('post.');

        if($data['act'] == 'add'){
            unset($data['act']);
            $adminValidate = Loader::Validate('Role');
            if (!$adminValidate->check($data)) {
                $baocuo = $adminValidate->getError();
                return json(['status' => -1, 'msg' => $baocuo]);
            }
            if(empty($data['jurisdiction'])){
                return json(['status' => -1, 'msg' => '请选择权限']);
            }
            $data['jurisdiction'] = implode(',', $data['jurisdiction']);
            $data['addtime'] = time();
            $res = Db::name('admin_group')->insert($data);
            //此处插入日志
        }

        if($res){
            return json(['status' => 1,  'msg'  => '添加成功']);  
        }else{
            return json(['status' => -1, 'msg'  => '添加失败']);  
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
