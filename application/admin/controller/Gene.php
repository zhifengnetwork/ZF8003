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
        
        $list = Db::name('gene')->field('id,user_id,name,desc,addtime,utime,nation,region')->where($where)->order('utime desc')->paginate(15);
        
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

    # 删除数据
    public function del_gene(){
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if($id > 0){
            $res = Db::name('gene')->delete($id);
            if($res){
                return json(['status'=>1]);
                exit;
            }
        }
        return json(['status'=>0]);
    }

    # 基因库配置
    public function config(){
        $gene_list = Standard_Gene_Up();

        if($_POST){
            $standard = isset($_POST['standard']) ? $_POST['standard'] : array();
            $check = isset($_POST['check']) ? $_POST['check'] : array();
            $timeline = isset($_POST['timeline']) ? $_POST['timeline'] : array();

            # 时间线设置
            if($timeline){
                $ctimeline = $in_ctimeline = '';
                foreach($timeline['min'] as $k=>$v){
                    $tmin = intval($v);
                    $tmax = intval($timeline['max'][$k]);
                    $tname = trim($timeline['name'][$k]);
                    $timg = isset($timeline['img'][$k]) && $timeline['img'][$k] ? trim($timeline['img'][$k]) : '';
                    if( ($tmin || ($k == 0 && $tmin == 0)) && $tmax && $tname ){
                        $in_ctimeline[] = $tmin.'_'.$tmax;
                        $tname = json_encode(['name'=>$tname,'img'=>$timg]);
                        if( Db::name('config')->where(['name'=>$tmin.'_'.$tmax, 'type'=>'gene_config_timeline'])->value('id') ){

                            Db::name('config')->where(['name'=>$tmin.'_'.$tmax, 'type'=>'gene_config_timeline'])->update(['value'=>$tname]);
                        }else{
                            Db::name('config')->insert(['name'=>$tmin.'_'.$tmax, 'value'=>$tname, 'type'=>'gene_config_timeline']);
                        }
                    }
                }

                if($in_ctimeline){
                    $in_ctimeline = implode("','", $in_ctimeline);
                    Db::execute("delete from `zf_config` where `type` = 'gene_config_timeline' and `name` not in ('$in_ctimeline')");
                }
                
            }

            # 基因突变率
            if($standard){
                foreach($standard as $k=>$v){
                    if(Db::name('config')->where(['name'=>$k,'type'=>'gene_config_mutation'])->find()){
                        Db::name('config')->where(['name'=>$k,'type'=>'gene_config_mutation'])->update(['value'=>$v]);
                    }else{
                        Db::name('config')->insert(['name'=>$k,'value'=>$v,'type'=>'gene_config_mutation']);
                    }
                }
            }
            

            # 用于计算的基因座
            if($check){
                
                foreach($gene_list as $v){
                    $value = 0;
                    if(in_array($v,$check)){
                        $value = 1;
                        $dd[] = $v;
                    }
                    
                    $name = strtolower(str_replace('-','_',$v));
                    if(Db::name('config')->where(['type'=>'gene_config_calculation','name'=>$name])->find()){
                        Db::name('config')->where(['type'=>'gene_config_calculation','name'=>$name])->update(['value'=>$value]);
                    }else{
                        Db::name('config')->insert(['name'=>$name,'value'=>$value,'type'=>'gene_config_calculation']);
                    }
                }
            }
            

            echo "<script>parent.layermsg('操作成功！正在刷新...')</script>";
            exit;
        }


        $timeline_config = Db::name('config')->field('name,value')->where(['type' => 'gene_config_timeline'])->select();
        // dump($timeline_config);exit;
        $mutation_config = Db::name('config')->field('name,value')->where('type', 'gene_config_mutation')->select();
        $calculation_config = Db::name('config')->field('name,value')->where('type', 'gene_config_calculation')->select();
        

        $timeline = $check = $mutation = array();
        if($timeline_config){
            foreach($timeline_config as $k => $v){
                $key = explode('_', $v['name']);
                $value = json_decode($v['value'],true);
                $timeline[] = [
                    'min' => $key[0],
                    'max' => $key[1],
                    'name' => $value['name'],
                    'img' => $value['img'],
                ];
            }
        }

        if($mutation_config){
            foreach($mutation_config as $v){
                $mutation[$v['name']] = $v['value'];
            }
        }
        if($calculation_config){
            foreach($calculation_config as $v){
                if($v['value']){
                    $check[] = strtoupper(str_replace('_','-',$v['name']));
                }
            }
        }

        $this->assign('timeline', $timeline);
        $this->assign('mutation', $mutation);
        $this->assign('gene_list', $gene_list);
        $this->assign('check', $check);
        return $this->fetch();
    }

    # 基因库配置 时间线颜色文件
    public function config_timeline_image(){

        $files = array();
        if($head = opendir(ROOT_PATH.'/public/gene/image/')){
            while(($file = readdir($head)) !== false){
                if($file != ".." && $file!="."){
                    if(is_dir($file)){
                        $files[$file]=bianli1($dir.'/'.$file);
                    }else{
                        $files[]=$file;
                    }
                }
            }
            closedir($head);
        }

        $this->assign('files', $files);
        return $this->fetch();;
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
                    $min = $value[$k]*100-200 > 0 ? $value[$k]*100-200 : 0;
                    $max = $value[$k]*100+200;
                    $w[strtolower($v)] = ['between',"$min,$max"];
                    $search[$v] = $value[$k];
                }
            }
            $where = $w;
        }
        $list = Db::name('gene')->field('id,user_id,name,desc,addtime,utime,nation,region')->where($where)->order('utime desc')->select();
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
                $min = $info[$v]-200 > 0 ? $info[$v]-200 : 0;
                $max = $info[$v]+200;
                $w[strtolower($v)] = ['between',"$min,$max"];
            }
            $where = $w;
            $where['id'] = ['neq',$id];
        }
        
        $list = Db::name('gene')->field('id,user_id,name,desc,addtime,utime,nation,region')->where($where)->order('utime desc')->select();
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
            $haplogroup = isset($_POST['haplogroup']) ? trim($_POST['haplogroup']) : '';
            $nation = isset($_POST['nation']) ? trim($_POST['nation']) : '';
            $region = isset($_POST['region']) ? trim($_POST['region']) : '';
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
            $data['haplogroup'] = $haplogroup;
            $data['nation'] = $nation;
            $data['region'] = $region;
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


    # 删除用户上传的的数据
    public function del_import(){

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        $filename = Db::name('import_gene')->where('id',$id)->value('filepath');
        if($filename){
            $res = Db::name('import_gene')->delete($id);
            if($res){
                if(file_exists(ROOT_PATH."public/gene/import/$filename")){
                    @unlink(ROOT_PATH."public/gene/import/$filename");
                }
                return json(['status'=>1]);
            }
        }
        return json(['status'=>0]);
    }

}


