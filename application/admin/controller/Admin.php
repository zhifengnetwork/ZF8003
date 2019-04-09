<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Loader;
class Admin extends Base
{
    public function index(){

    }
    /**
     * 管理员列表
     */
    public function list1()
    {
        $page = 10;
        $seach = isset($_GET['seach']) ? $_GET['seach'] : '';
        if($seach){
            $page = 0;
            // 搜索条件
            $where=$this->s_condition($seach['m_conditions'],$seach['datemin'], $seach['datemax']); 
            // 列出数据
            $list=$this->l_data($where,$page);
            $num = count($list);
        }else{
            $list=$this->l_data($where='',$page);
            $num = count($list);
            // $admin_list = Db::name('admin')->select(); 
            // // dump($admin_list);
            // $g_list     = Db::name('admin_group')->select();
            // // dump($g_list);
            // foreach($admin_list as $key => $value){
            //     # code...
                
            //     foreach ($g_list as $k => $val) {
            //         # code...
            //         if($value['group_id'] == $val['id']){
            //             $value['g_name'] = $val['name'];
            //         }
            //     }
            //     $zuhe[] =$value; 
            // } 
            // foreach ($zuhe as $key => $value) {
            //     # code...
            // $res = Db::name('admin_group')->where('id',$value['group_id'])->find();
            // if(!$res){
            //         $value['g_name'] = '-'; 
            // }
            // $comb[] = $value;
            // }
            // $num = count($comb);

        }


        // $this->assign('comb',$comb);
        $this->assign('num', $num); 
    	$this->assign('list',$list);        
        return $this->fetch();
    }
    
    // 列出数据
    public function l_data($where='',$page)
    {   
        // if($status == 1){
            $list = Db::name('admin')
                ->alias('a')
                ->join('admin_group ad', 'a.group_id = ad.id')
                ->field('a.*,ad.name g_name')
                ->where($where)
                ->where('a.id', 'not in', '1')
                ->order('id asc')
                ->paginate($page);
        // }else{
          
        // }

            return $list;
    }

    // 搜索条件
    public function s_condition($conditions, $datemins, $datemaxs)
    {
        $time = " 23:59:59";
        if ($conditions) {
            $m_conditions = str_replace(' ', '', $conditions);
            $where['a.name|ad.name'] = ['like', "%$m_conditions%"];
        }
        if ($datemins && $datemaxs) {
            $datemin = strtotime($datemins);
            $datemax = strtotime($datemaxs . $time);
            $where['a.addtime'] = [['>= time', $datemin], ['<= time', $datemax], 'and'];
        } elseif ($datemins) {
            $where['a.addtime'] = ['>= time', strtotime($datemin)];
        } elseif ($datemaxs) {
            $where['a.addtime'] = ['<= time', strtotime($datemaxs . $time)];
        }
        return $where;
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
        $info['password'] =  "";
        $act = empty($id) ? 'add' : 'edit';
        $role = Db::name('admin_group')->select();
        $role_name = Db::name('admin_group')->where('id',$info['group_id'])->value('name');
        $this->assign('info', $info);
        $this->assign('act', $act);
        $this->assign('role', $role);
        $this->assign('role_name', $role_name);
    
        return $this->fetch();
      } 
    

    /**
     * 添加编辑操作
     */
    public function adminHandle(){
        $data = input('post.');
        
        // $this->ajaxReturn(['status'=>1,'msg'=>'操作成功','url'=>U('Admin/Admin/index')]);
		$adminValidate = Loader::Validate('Admin');
		if(!$adminValidate->check($data)){
            $baocuo=$adminValidate->getError();
        	return json(['status'=>-1,'msg'=> $baocuo]);
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
                'password'=> pwd_encryption($data['password']),
                'group_id'=> $data['group_id'],
                'addtime' => time() 
            ];
            $res =  Db::name('admin')->insert($data1);

    	}
    	// 编辑
    	if($data['act'] == 'edit'){
            $data1 = [
                'name'    => $data['name'],
                'password' => pwd_encryption($data['password']),
                'group_id' => $data['group_id'],
                'updatetime' => time()
            ];            
    		$res = Db::name('admin')->where('id', $data['id'])->update($data1);
    	}
    	
    	if($res){
			return json(['status'=>1,'msg'=>'操作成功']);

		}else{
            return json(['status'=>-1,'msg'=>'操作失败']);
    	}
    }

    // 删除和批量删除
    public function del(){
        $data = input('post.');
        if($data['act'] == 'batchdel'){
            $id = json_decode($data['id'], true);
            $where['id'] = array('in', $id);
            $res = Db::name('admin')->where($where)->delete();
        }else{
            if ($data['id'] > 1) {
                $res = Db::name('admin')->where('id', $data['id'])->delete();
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

    //启用和停用
    public function is_handle(){
        $data = input('post.');
        // dump($data);exit;
        if($data['status'] == 'stop'){
            $res = Db::name('admin')->where('id',$data['id'])->update(['is_lock' => 1]);
        }else{
            $res = Db::name('admin')->where('id', $data['id'])->update(['is_lock' => 0]);
        }
        
        if($res){
             return json(['status'=>1,'msg'=>'操作成功']);
        }else{
            return json(['status'=>-1,'msg'=>'操作失败']);
        }
    }
 
      public function permission(){
          return $this->fetch();
      }    
    //   public function role(){
    //       return $this->fetch();
    //   }

    // public function role_add(){
         
    //      return $this->fetch();
    // }
}
