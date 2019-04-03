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
    	$list = array();
    	$keywords = input('keywords/s');
    	if(empty($keywords)){
            // 列出除了超级管理员以外的管理员
            // $res = Db::name('admin')->where('id','not in','1')->select();
            $list = Db::name('admin')
            ->alias('a')
            ->join('admin_group ad','a.group_id = ad.id')
            ->field('a.*,ad.name g_name')
            ->where('a.id','not in','1')
            ->select();
            // dump($res);
    	}else{
            // $res = DB::name('admin')->where('user_name','like','%'.$keywords.'%')->where('id','not in','1')->order('id')->select();
            $list = Db::name('admin')
            ->alias('a')
            ->join('admin_group ad','a.group_id = ad.id')
            ->field('a.*,ad.name g_name')
            ->where('user_name','like','%'.$keywords.'%')
            ->where('a.id','not in','1')
            ->order('id')
            ->select();
        }

    	$this->assign('list',$list);        
        return $this->fetch();
    }
    /**
     * 添加或者编辑管理员页面
     */
      public function add(){

        $act = empty($id) ? 'add' : 'edit';
        $this->assign('act', $act);
        $role = Db::name('admin_group')->select();
        $this->assign('role', $role);
        return $this->fetch();
      } 

      public function edit($id){
      
        $info = Db::name('admin')->where("id", $id)->find();
        // $info['password'] =  "";
        $act = empty($id) ? 'add' : 'edit';
        $role = Db::name('admin_group')->select();
        $role_name = Db::name('admin_group')->where('id',$info['group_id'])->value('name');
        $this->assign('info', $info);
        $this->assign('act', $act);
        $this->assign('role', $role);
        $this->assign('role_name', $role_name);
        dump($role_name);
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
            $baocuo=$adminValidate->getError();
        	return json(['status'=>-1,'msg'=> $baocuo['name']]);
        }
        if ($data['password'] != $data['password2']) {
            return json(['status' => -1, 'msg' => '密码不一致']);
        }

        // 添加数据 
    	if($data['act'] == 'add'){

            // 检测用户名是否存在
            $check_name = Db::name('admin')->where('name', $data['name'])->select();
            if (!empty($check_name)) {
                return json(['status' => -1, 'msg' => '用户名已经存在']);
            }
            unset($data['id']);
            // unset($data['act']);
            unset($data['password2']); 
            $data1 = [
                'name'    => $data['name'],
                'password'=> md5($data['password']),
                'group_id'=> $data['group_id'],
                'addtime' => time() 
            ];

            $res =  Db::name('admin')->insert($data1);
    	}
    	// 编辑
    	if($data['act'] == 'edit'){
            $data1 = [
                'name'    => $data['name'],
                'password' => $data['password'],
                'group_id' => $data['group_id'],
                'updatetime' => time()
            ];            
    		$res = Db::name('admin')->where('id', $data['id'])->update($data1);
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

    public function role_add(){
   
         return $this->fetch();
      }
}
