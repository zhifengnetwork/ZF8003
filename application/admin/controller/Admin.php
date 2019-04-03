<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;

class Admin extends Controller
{
    /**
     * 管理员列表
     */
    public function list1()
    {
    	$list = array();
    	$keywords = I('keywords/s');
    	if(empty($keywords)){
    		$res = D('admin')->where('admin_id','not in','2,3')->select();
    	}else{
			$res = DB::name('admin')->where('user_name','like','%'.$keywords.'%')->where('admin_id','not in','2,3')->order('admin_id')->select();
    	}
    	$role = D('admin_role')->getField('role_id,role_name');
    	if($res && $role){
    		foreach ($res as $val){
    			$val['role'] =  $role[$val['role_id']];
    			$val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
    			$list[] = $val;
    		}
    	}
    	$this->assign('list',$list);
        return $this->fetch();
    }
    /**
     * 添加或者编辑管理员
     */
      public function add(){
        $admin_id = I('get.admin_id/d', 0);
        if ($admin_id) {
            $info = Db::name('admin')->where("admin_id", $admin_id)->find();
            $info['password'] =  "";
            $this->assign('info', $info);
        }
        $act = empty($admin_id) ? 'add' : 'edit';
        $this->assign('act', $act);
        $role = D('admin_role')->select();
        $this->assign('role', $role);
        return $this->fetch();
      } 
    //   public function edit(){
    //       return $this->fetch();
    //   }       
      public function permission(){
          return $this->fetch();
      }    
      public function role(){
          return $this->fetch();
      }
      
      

}
