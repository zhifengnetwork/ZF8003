<?php

namespace app\admin\Controller;
use think\Db;

class Article extends Base{

    public function index(){



        return $this->fetch();
    }

    # 文章分类
    public function category(){

        $where['type'] = ['=', 'article'];
        $keywords = isset($_GET['keywords']) ? trim($_GET['keywords']) : '';
        if($keywords){
            $where['name'] = ['like', "%$keywords%"];
        }

        $list = Db::name('category')->where($where)->paginate(15);
        $count = Db::name('category')->where($where)->count();
        if($list){
            $pname[0] = '顶级分类';
            foreach($list as $v){
                $pids[] = $v['id'];
            }
            if(isset($pids) && $pids){
                $pids = implode("','", $pids);
                $pinfo = Db::query("select `id`,`name` from `zf_category` where `id` in ('$pids')");
                foreach($pinfo as $p){
                    $pname[$p['id']] = $p['name'];
                }
                $this->assign('pname', $pname);
            }
        }
        
        $this->assign('list', $list);
        $this->assign('count', $count);
        $this->assign('keywords', $keywords);
        return $this->fetch();
    }

    # 增加/编辑文章分类
    public function add_category(){

        if($_POST){
            $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
            $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $sort = isset($_POST['sort']) ? intval($_POST['sort']) : 0;
            $is_lock = isset($_POST['is_lock']) ? intval($_POST['is_lock']) : 0;

            if(!$name){
                return json(['status' => 0, 'msg' => '请输入分类名称！']);
            }

            $level = 1;
            $parent_ids = '';

            if($parent_id > 0){
                $pinfo = Db::query("select `level`,`parent_ids` from `zf_category` where `id` = '$parent_id' and `level` < 3");
                if(!$pinfo){
                    return json(['status'=> 0,'msg' => '参数错误！']);
                }

                $level = $pinfo[0]['level'] + 1;
                $parent_ids = $pinfo[0]['parent_ids'] ?  $pinfo[0]['parent_ids'] . ',' . $parent_id : $parent_id;

            }

            $time = time();
            if($category_id > 0){
                $sql = "update `zf_category` set `name` = '$name', `parent_id` = '$parent_id', `sort` = '$sort', `is_lock` = '$is_lock', `parent_ids` = '$parent_ids' where `id` = '$category_id'";
            }else{
                $sql = "insert into `zf_category` (`name`,`level`,`sort`,`parent_id`,`parent_ids`,`is_lock`,`time`,`type`) values ('$name','$level','$sort','$parent_id','$parent_ids','$is_lock','$time','article')";
            }
            $res = Db::execute($sql);
            if($res){
                return json(['status' => 1, 'msg' => '操作成功！']);
            }else{
                return json(['status'=> 0, 'msg' => '操作失败，请重试！']);
            }

            exit;
        }

       
        $where['parent_id'] = ['=', 0];
        $where['is_lock'] = ['=', 0];
        $where['type'] = ['=', 'article'];
        $pid = 0;

        $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
        if($category_id > 0){
            $where['id'] = ['neq', $category_id];
            $info = Db::name('category')->find($category_id);
            $cate = Db::name('category')->where($where)->select();
            if($info){

                $pid = $info['parent_id'];

                if($info['parent_ids']){
                    $pids = explode(',',$info['parent_ids']);
                    if(isset($pids[1])){
                        $where['parent_id'] = $pids[0];
                        $lcate = Db::name('category')->where($where)->select();
                        
                        $pid = $pids[0];
                        $this->assign('lcate',$lcate);
                    }
                }

                $this->assign('info', $info);
            }
        }else{
            $cate = Db::name('category')->where($where)->select();
        }
        
        $this->assign('pid', $pid);
        $this->assign('cate', $cate);
        return $this->fetch();
    }

    /**
     * 删除文章分类
     */
    function del_category(){
        if($_POST){
            $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
            if($category_id){
                $res = Db::table('zf_category')->delete($category_id);
                if($res){
                    return json(['status' => 1]);
                }
            }
        }
        return json(['status' => 0]);
    }

    # 获取下级分类
    public function ajax_getLastCate(){

        $cate_id = isset($_POST['cate_id']) ? intval($_POST['cate_id']) : 0;
        if($cate_id > 0){
            $list = Db::name('category')->field('`id`,`name`')->where(['parent_id'=>['=',$cate_id], 'is_lock' => ['=', 0]])->select();
            if($list){
                $tpl = '';
                foreach($list as $v){
                    $tpl .= '<option value="'.$v['id'].'">'.$v['name'].'</option>';
                }
                
                return json($tpl);
            }
        }
        exit;
    }



        
    






}