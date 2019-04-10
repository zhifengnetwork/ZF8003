<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Loader;
use think\Session;
use think\Paginator;
class Admin extends Base
{
    public function _initialize()
    {

        parent::_initialize();
        // Session::clear();
        $admin_name = session('admin_name');
        if (empty($admin_name)) {
            $this->error('请先登陆', 'Login/index');
            // $url = "http://" . $_SERVER['HTTP_HOST'] . "/index.php/admin/";
            // header("refresh:1;url=$url");
            // exit;
        }
    }
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
            $list = Db::name('admin')->where($where)->order('id desc')->paginate(15);
            $cname[0] = '';
            if ($list) {
                foreach ($list as $v) {
                    $cids[] = $v['group_id'];
                }

                if (isset($cids)) {
                    $cids = implode("','", $cids);
                    $cids = Db::query("select `id`,`name` from `zf_admin_group` where `id` in ('$cids')");
                    foreach ($cids as $c) {
                        $cname[$c['id']] = $c['name'];
                    }
                }
            } 
            $num = count($list);
        }else{
            $list = Db::name('admin')->order('id desc')->paginate(15);
            $num = count($list);
            $cname[0] = '';
            if ($list) {
                foreach ($list as $v) {
                    $cids[] = $v['group_id'];   
                } 
                if (isset($cids)) {
                    $cids = implode("','", $cids); 
                    $cids = Db::query("select `id`,`name` from `zf_admin_group` where `id` in ('$cids')");
                    foreach ($cids as $c) {
                        $cname[$c['id']] = $c['name'];
                    }
                    
                }
            }
        }
         
       
        // $this->assign('comb',$comb);
        $this->assign('num', $num); 
        $this->assign('cname', $cname); 
    	$this->assign('list',$list);        
        return $this->fetch();
    }
    
    // 搜索条件
    public function s_condition($conditions, $datemins, $datemaxs)
    {
        $time = " 23:59:59";
        if ($conditions) {
            $m_conditions = str_replace(' ', '', $conditions);
            $where['name'] = ['like', "%$m_conditions%"];
        }
        if ($datemins && $datemaxs) {
            $datemin = strtotime($datemins);
            $datemax = strtotime($datemaxs . $time);
            $where['addtime'] = [['>= time', $datemin], ['<= time', $datemax], 'and'];
        } elseif ($datemins) {
            $where['addtime'] = ['>= time', strtotime($datemins)];
        } elseif ($datemaxs) {
            $where['addtime'] = ['<= time', strtotime($datemaxs . $time)];
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
            $action = 'add';
            $desc   = '添加管理员';
            $log = $this->adminLog($action, $desc);   
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
            $action = 'edit';
            $desc   = '编辑管理员';
            $log = $this->adminLog($action, $desc);               
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


        $action = 'del';
        $desc   = '删除管理员';
        $log = $this->adminLog($action, $desc);

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

    function adminLog($action, $desc)
    {
        $add['addtime'] = time();
        $add['admin_id'] = session('admin_id');
        $add['action'] = $action;
        $add['desc']   = $desc;
        // $add['log_ip'] = request()->ip();
        // $add['log_url'] = request()->baseUrl();
        Db::name('admin_log')->insert($add);
        return true;
    }
    public function logout()
    {
        Session::clear();
        $this->success("退出成功", 'Admin/Login/index');        
    }

}
