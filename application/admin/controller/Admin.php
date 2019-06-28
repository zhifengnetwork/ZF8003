<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Loader;
use think\Session;
use think\Paginator;
class Admin extends Base
{
    
    public function index(){
        $this->redirect('list1');
    }
    /**
     * 管理员列表
     */
    public function list1()
    {
        $seach = isset($_GET['seach']) ? $_GET['seach'] : '';
        $m_conditions   = isset($seach['m_conditions']) ? $seach['m_conditions'] : '';
        $datemin        = isset($seach['datemin']) ? $seach['datemin'] : '';
        $datemax        = isset($seach['datemax']) ? $seach['datemax'] : '';
        $role           = isset($seach['role']) ? $seach['role'] : '';
        $where = '';
        if($seach){
            // 搜索条件
            $where=$this->s_condition($seach['m_conditions'],$seach['datemin'], $seach['datemax'],$seach['role']);
            $seach = [
                'm_conditions'  => $m_conditions,
                'datemin'       => strtotime($datemin),
                'datemax'       => strtotime($datemax),
                'role'          => $seach['role']
            ];
            $this->assign('seach', $seach); 
            // 列出数据
        }
            $list = Db::name('admin')->where($where)->order('addtime desc')->paginate(15, false, ['query' => request()->param()]);
            $num = count($list);
            // 防止空值报错
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
        
        $role = Db::name('admin_group')->select();
        $this->assign('role',$role);
        $this->assign('num', $num); 
        $this->assign('cname', $cname); 
    	$this->assign('list',$list);        
        return $this->fetch();
    }
    
    // 搜索条件
    public function s_condition($conditions, $datemins, $datemaxs,$role)
    {
        $time = " 23:59:59";
        if ($conditions) {
            $m_conditions = str_replace(' ', '', $conditions);
            $where['name'] = ['like', "%$m_conditions%"];
        }
        if ($datemins && $datemaxs) {
            $datemin = strtotime($datemins);
            $datemax = strtotime($datemaxs);
            $where['addtime'] = [['>= time', $datemin], ['<= time', $datemax], 'and'];
        } elseif ($datemins) {
            $where['addtime'] = ['>= time', strtotime($datemins)];
        } elseif ($datemaxs) {
            $where['addtime'] = ['<= time', strtotime($datemaxs)];
        } elseif ($role) {
            $where['group_id'] = $role;
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
		$adminValidate = Loader::Validate('Admin');
		if(!$adminValidate->check($data)){
            $baocuo=$adminValidate->getError();
        	return json(['status'=>-1,'msg'=> $baocuo]);
        }
        if ($data['password'] != $data['password2']) {
            return json(['status' => -1, 'msg' => '密码不一致']);
        }
        if (!isset($data['group_id'])) {
            return json(['status' => -1, 'msg' => '请选择角色']);
        }          
        // 添加数据 
    	if($data['act'] == 'add'){
            // 检测用户名是否存在
            $check_name = Db::name('admin')->where('name', $data['name'])->select();
            if (!empty($check_name)) {
                return json(['status' => -1, 'msg' => '用户名已经存在']);
            }
            unset($data['id']);
            unset($data['password2']); 
            $data1 = [
                'name'    => $data['name'],
                'password'=> pwd_encryption($data['password']),
                'group_id'=> $data['group_id'],
                'addtime' => time() 
            ];
            $res =  Db::name('admin')->insert($data1);
            $action = 'add';
            $desc = '添加管理员';
            $log = adminLog($action,$desc);   
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
            $desc = '编辑管理员';
            $log = adminLog($action, $desc);                
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
            $is_super = Db::name('admin')->where('id',$data['id'])->value('is_super');
            if ($is_super == 1) {
                return json(['status' => -1, 'msg' => '超级管理员不能删除！']);
            }else{
                $res = Db::name('admin')->where('id', $data['id'])->delete();
               
            } 
        }


        if($res){
            // 日志
            $action = 'del';
            $desc = '删除管理员';
            $log = adminLog($action,$desc);            
            return json(['status'=>1,'msg'=>'操作成功']);
        }else{
            return json(['status'=>-1,'msg'=>'操作失败']);
        }
    }

    //启用和停用
    public function is_handle(){
        $data = input('post.');
        if($data['status'] == 'stop'){
            $res = Db::name('admin')->where('id',$data['id'])->update(['is_lock' => 1]);
            $action = 'start_admin';
            $desc = '启用管理员';
            $log = adminLog($action, $desc); 
        }else{
            $res = Db::name('admin')->where('id', $data['id'])->update(['is_lock' => 0]);
            $action = 'stop_admin';
            $desc = '停用管理员';
            $log = adminLog($action, $desc); 
        }
        
        if($res){
            return json(['status'=>1,'msg'=>'操作成功']);
        }else{
            return json(['status'=>-1,'msg'=>'操作失败']);
        }
    }
 

    # 管理员组 | 角色管理
    public function admin_group(){

        $where['id'] = ['>', 0];

        $list = Db::name('admin_group')->field('id,name,addtime,utime')->where($where)->order('utime desc,id desc')->paginate(20);
        $count = Db::name('admin_group')->where($where)->count();
        $gcount = [];
        if($list){
            foreach($list as $v){
                $gcount[$v['id']] = Db::name('admin')->where('group_id', $v['id'])->count();
            }
        }

        $this->assign('gcount', $gcount);
        $this->assign('list', $list);
        $this->assign('count', $count);
        return $this->fetch();
    }

    # 添加或编辑角色
    public function edit_admin_group(){
        if($_POST){
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $jur = isset($_POST['jur']) ? $_POST['jur'] : '';
            $time = time();

            if(!$name){
                layer_error_msg('角色名称不能为空');
            }
            if(!$id){
                $ex = Db::name('admin_group')->where('name',$name)->count();
                if($ex){
                    layer_error_msg('角色名已存在，不建议重复！');
                }

            }
            
            if($jur){
                foreach($jur as $k=>$v){
                    foreach($v as $key=>$val){
                        if($val){
                            $wrd = implode('',$val);
                            $d_jur[$k][$key] = $wrd;
                        }
                    }
                }
                
                $jur = json_encode($d_jur);
            }
            
            if($id){
                $res = Db::name('admin_group')->where('id', $id)->update(['name'=>$name,'jurisdiction'=>$jur,'utime'=>$time]);
            }else{
                $res = Db::name('admin_group')->insert(['name'=>$name,'jurisdiction'=>$jur,'addtime'=>$time,'utime'=>$time]);
            }
            if($res){
                layer_success_msg('操作成功，正在跳转...',true);
            }
            layer_error_msg('操作失败，请重试！');
            exit;
        }


        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        $menu = Db::name('menu')->field('id,name,parent_id')->order('level asc,sort desc,id asc')->select();
        if(!$menu){
            error_h1('系统暂无可配置的菜单，请先添加系统菜单！','系统未正确配置！');
        }
        foreach($menu as $k=>$v){
            if($v['parent_id'] == 0){
                $menu_list[$v['id']] = [
                    'id' => $v['id'],
                    'name' => $v['name'],
                ];
                unset($menu[$k]);
                foreach($menu as $k2=>$v2){
                    if($v2['parent_id'] == $v['id']){
                        $menu_list[$v['id']]['last'][$v2['id']] = ['id'=>$v2['id'],'name'=>$v2['name']];
                        unset($menu[$k2]);
                    }
                }
            }
        }

        $info = Db::name('admin_group')->find($id);
        if($info){
            $info['jurisdiction'] =  $info['jurisdiction'] ? json_decode($info['jurisdiction'], true) : '';
            // dump($info);exit;
        }

        $this->assign('info', $info);
        $this->assign('menu_list', $menu_list);
        return $this->fetch();
    }

    # 删除角色 | 一键删除
    public function del_admin_group(){

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $one = isset($_POST['one']) ? intval($_POST['one']) : 0;

        if($id){
            if(!$one){
                $count = Db::name('admin')->where('group_id', $id)->count();
                if($count){
                    return json(['status'=>2, 'msg'=>'存在下级人数：'.$count]);
                }
            }
            
            if($one){
                Db::name('admin')->where('group_id', $id)->delete();
            }

            $res = Db::name('admin_group')->delete($id);
            if($res){
                return json(['status'=>1,'msg'=>'操作成功，正在刷新...']);
            }
        }
        
        return json(['status'=>0,'msg'=>'操作失败，请重试！']);
    }

    # 转移角色下的管理员并删除
    public function transfer_group(){

        if($_POST){
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
            if(!$id || !$group_id){
                layer_error_msg('请选择转入的角色');
            }
            
            $ur = Db::name('admin')->where('group_id',$id)->update(['group_id'=>$group_id]);
            if(!$ur){
                layer_error_msg('管理员角色变更失败，请重试！');
            }
            $dr = Db::name('admin_group')->delete($id);
            if(!$dr){
                layer_success_msg('管理员角色转移成功，转出角色删除失败');
                
            }
            layer_success_msg('操作成功，请刷新页面查看');
        }

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $iname = Db::name('admin_group')->where('id', $id)->value('name');
        if(!$iname){
            error_h1('参数错误！','没有找到该角色');
        }
        $other_group = Db::name('admin_group')->where('id','<>',$id)->field('id,name')->select();
        if(!$other_group){
            error_h1('没有找到其他角色','请选择其他操作项');
        }


        $this->assign('id', $id);
        $this->assign('iname', $iname);
        $this->assign('other_group', $other_group);
        return $this->fetch();
    }









    # 退出登录
    public function logout()
    {
        Session::clear();
        $this->redirect('Login/index');     
    }

}
