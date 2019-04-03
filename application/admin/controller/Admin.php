<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Loader;
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
		$adminValidate = Loader::Validate('Admin');
		if(!$adminValidate->batch()->check($data)){
        	return json(['status'=>-1,'msg'=> $adminValidate->getError(),'result'=>$adminValidate->getError()]);
        }
        if($data['password'] != $data['password2']){
            return json(['status'=>-1,'msg'=>'密码不一致']);
        }
        // 检测用户名是否存在

        $check_name=Db::name('admin')->where('name',$data['name'])->select();
         
		if(empty($data['password'])){
			unset($data['password']);
		}else{
			$data['password'] = md5($data['password']);
        }
        /* 添加数据 */
    	if($data['act'] == 'add'){
            unset($data['id']);
            // unset($data['act']);
            unset($data['password2']); 
            $data1 = [
                'name'    => $data['name'],
                'password'=> $data['password'],
                'group_id'=> $data['group_id'],
                'addtime' => time() 
            ];

            $res =  Db::name('admin')->insert($data1);
    	}
    	
    	if($data['act'] == 'edit'){
    		$res = D('admin')->where('id', $data['id'])->save($data);
    	}
        if($data['act'] == 'del' && $data['id']>1){
    		$res = D('admin')->where('id', $data['id'])->delete();
    	}
    	
    	if($res){
			return json(['status'=>1,'msg'=>'操作成功']);

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
