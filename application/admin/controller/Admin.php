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
    	// $list = array();
    	// $keywords = input('keywords/s');
    	// if(empty($keywords)){
        //     // $res = D('admin')->where('admin_id','not in','2,3')->select();
        //     $res = D('admin')->select();
    	// }else{
		// 	$res = DB::name('admin')->where('user_name','like','%'.$keywords.'%')->where('admin_id','not in','2,3')->order('admin_id')->select();
    	// }
    	// $role = D('admin_role')->getField('role_id,role_name');
    	// if($res && $role){
    	// 	foreach ($res as $val){
    	// 		$val['role'] =  $role[$val['role_id']];
    	// 		$val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
    	// 		$list[] = $val;
    	// 	}
    	// }
    	// $this->assign('list',$list);
        return $this->fetch();
    }
    /**
     * 添加或者编辑管理员页面
     */
      public function add(){
        $admin_id = input('get.id/d', 0);
        if ($admin_id) {
            $info = Db::name('admin')->where("id", $admin_id)->find();
            $info['password'] =  "";
            $this->assign('info', $info);
        }
        $act = empty($admin_id) ? 'add' : 'edit';
        $this->assign('act', $act);
        // $role = D('admin_role')->select();
        // $this->assign('role', $role);
        return $this->fetch();
      } 
    /**
     * 添加编辑操作
     */
    public function adminHandle(){
        $data = input('post.');

        // $this->ajaxReturn(['status'=>1,'msg'=>'操作成功','url'=>U('Admin/Admin/index')]);
		$adminValidate = Loader::validate('Admin');
		if(!$adminValidate->batch()->check($data)){
        	return json(['status'=>-1,'msg'=> $adminValidate->getError(),'result'=>$adminValidate->getError()]);
        }
        if($data['password'] != $data['password2']){
            return json(['status'=>-1,'msg'=>'密码不一致']);
        }
        // if(isset($data['name']) && !empty($data['name'])){
        //     $inarr['name'] = trim($data['name']);
        // }else{
        //     return json(['status' => -1, 'msg' => '操作失败']);
        // }

        // if(isset($data ['password']) && !empty($data['name'])){
        //      $inarr['password'] = trim($data['password']);
        // }else{
              
        // }
         
		if(empty($data['password'])){
			unset($data['password']);
		}else{
			$data['password'] = md5($data['password']);
		}
    	if($data['act'] == 'add'){
            unset($data['id']);  

            $data['addtime'] = time();
            $r = db('admin')->insert($data);
    	}
    	
    	if($data['act'] == 'edit'){
    		$r = D('admin')->where('id', $data['id'])->save($data);
    	}
        if($data['act'] == 'del' && $data['id']>1){
    		$r = D('admin')->where('id', $data['id'])->delete();
    	}
    	
    	if($r){
			return json(['status'=>1,'msg'=>'操作成功','url'=>U('Admin/Admin/index')]);

		}else{
            return json(['status'=>-1,'msg'=>'操作失败']);
    	}
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
