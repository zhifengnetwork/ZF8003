<?php
namespace app\admin\controller;

use think\Db;

class Gene extends Base{

    # 基因数据库
    public function index(){

        $search = isset($_GET['search']) ? $_GET['search'] : array();
        $where = ['id'=>['>',0]];

        if(isset($search['name']) && $search['name']){
            $where['name'] = ['like', "%$search[name]%"];
        }
        if(isset($search['user']) && $search['user']){
            $search_userid = Db::name('users')->where('nickname|email',$search['user'])->value('id');
            if($search_userid){
                $where['user_id'] = ['=', $search_userid];
            }else{
                $where['id'] = ['=', '0'];
            }
        }
        if(isset($search['datemin']) && $search['datemin']){
            $where['addtime'] = ['>=', strtotime($search['datemin'])];
        }
		if(isset($search['datemax']) && $search['datemax']){
            $where['addtime'] = ['<=', strtotime($search['datemax'])];
        }
        
        $list = Db::name('gene')->field('id,user_id,name,desc,addtime,utime')->where($where)->order('utime desc')->paginate(15);
        
        $count = Db::name('gene')->where($where)->count();
        
        $user_name = [0 => '--'];
        if($list){
            foreach($list as $v){
                if($v['user_id'] > 0)
                    $user_ids[] = $v['user_id'];
            }
            if(isset($user_ids)){
                $user_ids = array_unique($user_ids);
                $user_ids = implode("','",$user_ids);
                $users = Db::query("select `id`,`nickname` from `zf_users` where `id` in ('$user_ids')");
                if($users){
                    foreach($users as $u){
                        $user_name[$u['id']] = $u['nickname'];
                    }
                }
            }
        }
		
        $this->assign('user_name', $user_name);
        $this->assign('list', $list);
        $this->assign('search', $search);
        $this->assign('count', $count);
        return $this->fetch();
    }

    # 同位点查询
    public function homologous(){

        $key = isset($_GET['key']) ? $_GET['key'] : array();
        $value = isset($_GET['value']) ? $_GET['value'] : array();
        $search = array();
        $where = ['id'=>['=',0]];

        if($key){
            foreach($key as $k=>$v){
                if($value[$k]){
                    $w[strtolower($v)] = ['=', $value[$k]*100];
                    $search[$v] = $value[$k];
                }
            }
            $where = $w;
        }
        $list = Db::name('gene')->field('id,user_id,name,desc,addtime,utime')->where($where)->order('utime desc')->select();
        $count = Db::name('gene')->where($where)->count();
        
        $user_name = [0 => '--'];
        if($list){
            foreach($list as $v){
                if($v['user_id'] > 0)
                    $user_ids[] = $v['user_id'];
            }
            if(isset($user_ids)){
                $user_ids = array_unique($user_ids);
                $user_ids = implode("','",$user_ids);
                $users = Db::query("select `id`,`nickname` from `zf_users` where `id` in ('$user_ids')");
                if($users){
                    foreach($users as $u){
                        $user_name[$u['id']] = $u['nickname'];
                    }
                }
            }
        }
        
        
        $this->assign('gene_list', Standard_Gene_Up());
        $this->assign('user_name', $user_name);
        $this->assign('search', $search);
        $this->assign('list', $list);
        $this->assign('count', $count);
        return $this->fetch();
    }

    # 我的基因同位点查询
    public function my_homologous(){
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $check = isset($_GET['check']) ? $_GET['check'] : array();
        $where = ['id'=>['=',0]];
        
        $info = Db::name('gene')->where('id',$id)->find();
        if(!$info){
            echo "<h1 style='text-align:center; margin-top:80px;'>出错啦！</h1><p style='text-align:center;'>请刷新上一级页面重新打开试试！</p>";exit;
        }

        if($check){
            foreach($check as $v){
                $v = strtolower(str_replace('-','_',$v));
                $w[$v] = ['=', $info[$v]];
            }
            $where = $w;
            $where['id'] = ['neq',$id];
        }
        
        $list = Db::name('gene')->field('id,user_id,name,desc,addtime,utime')->where($where)->order('utime desc')->select();
        $count = Db::name('gene')->where($where)->count();
        
        $user_name = [0 => '--'];
        if($list){
            foreach($list as $v){
                if($v['user_id'] > 0)
                    $user_ids[] = $v['user_id'];
            }
            if(isset($user_ids)){
                $user_ids = array_unique($user_ids);
                $user_ids = implode("','",$user_ids);
                $users = Db::query("select `id`,`nickname` from `zf_users` where `id` in ('$user_ids')");
                if($users){
                    foreach($users as $u){
                        $user_name[$u['id']] = $u['nickname'];
                    }
                }
            }
        }
        
        
        $this->assign('gene_list', Standard_Gene_Up());
        $this->assign('user_name', $user_name);
        $this->assign('list', $list);
        $this->assign('count', $count);
        $this->assign('id', $id);
        $this->assign('check', $check);
        return $this->fetch();
    }


    # 详情
    public function info(){
        if($_POST){
            
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            $desc = isset($_POST['desc']) ? addslashes($_POST['desc']) : '';
            $standard = $_POST['standard'];
            $comp = $_POST['comp'];
            $time = time();
            $data2 = array();
            if($comp){
                foreach($comp['key'] as $k=>$v){
                    if($v){
                        $v2 = $comp['val'][$k] * 100;
                        $data2[$v] = $v2;
                    }                        
                }
            }
            $completion = $data2 ? json_encode($data2) : '';

            foreach($standard as $k => $v){
                $standard[$k] = $v *100;
            }

            $time = time();
            $data = $standard;
            $data['user_id'] = $user_id;
            $data['name'] = $name;
            $data['desc'] = $desc;
            $data['completion'] = $completion;
            $data['utime'] = $time;

            if($id > 0){
                $res = Db::name('gene')->where('id',$id)->update($data);
            }else{
                $data['addtime'] = $time;
                $res = Db::name('gene')->insertGetId($data);
            }
            
            if($res){
                echo "<script>parent.layermsg('操作成功！正在跳转...', 1)</script>";
                exit;
            }else{
                echo "<script>parent.layermsg('操作失败，请重试！', 0,5)</script>";
                exit;
            }
            exit;
        }

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $info = Db::name('gene')->find($id);
        $comp = array();
        if($info){
            $user_name = '';
            if($info['user_id'] > 0){
                $user_name = Db::name('users')->where('id',$info['user_id'])->value('nickname');
            }
            $info['user_name'] = $user_name;
            $comp = $info['completion'] ? json_decode($info['completion'],true) : '';
        }


        $this->assign('info', $info);
        $this->assign('comp', $comp);
        return $this->fetch();
    }

    # 用户上传的数据压缩包
    public function import(){

        $search = isset($_GET['search']) ? $_GET['search'] : array();
        $where = ['id'=>['>',0]];

        if(isset($search['user']) && $search['user']){
            $search_userid = Db::name('users')->where('nickname|email',$search['user'])->value('id');
            if($search_userid){
                $where['user_id'] = ['=', $search_userid];
            }else{
                $where['id'] = ['=', '0'];
            }
        }
        if(isset($search['datemin']) && $search['datemin']){
            $where['addtime'] = ['>=', strtotime($search['datemin'])];
        }
		if(isset($search['datemax']) && $search['datemax']){
            $where['addtime'] = ['<=', strtotime($search['datemax'])];
        }

        $list = Db::name('import_gene')->where($where)->order('id desc')->paginate(15);
        $count = Db::name('import_gene')->where($where)->count();
        $user_name = [0 => '--'];
        if($list){
            foreach($list as $v){
                if($v['user_id'] > 0)
                    $user_ids[] = $v['user_id'];
            }
            if(isset($user_ids)){
                $user_ids = array_unique($user_ids);
                $user_ids = implode("','",$user_ids);
                $users = Db::query("select `id`,`nickname` from `zf_users` where `id` in ('$user_ids')");
                if($users){
                    foreach($users as $u){
                        $user_name[$u['id']] = $u['nickname'];
                    }
                }
            }
        }
        
        $this->assign('search', $search);
        $this->assign('sex_name', [0=>'保密',1=>'男性',2=>'女性']);
        $this->assign('list', $list);
        $this->assign('count', $count);
        $this->assign('user_name', $user_name);
        return $this->fetch();
    }

}


