<?php

namespace app\admin\Controller;

use think\Controller;
use think\Db;
use think\Loader;
class Role extends Base
{
    public function role()
    {
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
        // $a = json_decode($data['jurisdiction'],true);
        // $jurisdiction = json_decode($data['jurisdiction'],true);
        // dump(implode(',',$jurisdiction));exit;
        // {1:{2,3,4},5:{6,7,8}`}
        if($data['act'] == 'add'){
            unset($data['act']);
            $adminValidate = Loader::Validate('Role');
            if (!$adminValidate->check($data)) {
                $baocuo = $adminValidate->getError();
                return json(['status' => -1, 'msg' => $baocuo]);
            }
           
            $res = Db::name('admin_group')->insert($data);
        }




        if($res){
            return json(['status' => 1,  'msg'  => '添加成功']);  
        }else{
            return json(['status' => -1, 'msg'  => '添加失败']);  
        }
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
