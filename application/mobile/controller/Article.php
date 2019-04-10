<?php

namespace app\mobile\controller;
use think\Controller;
use think\Db;

class Article extends Base{

    public function index(){


        $this->redirect('/mobile');
    }

    # 微解读
    public function micro(){

        $conf = Db::query("select `value` from `zf_config` where `type` = 'hom_module_bind' and `name` = 'micro'");
        $lists = array();
        if(isset($conf) && $conf[0]['value'] > 0){
            $cate_id = $conf[0]['value'];
            $cate_ids = Db::query("select `id` from `zf_category` where find_in_set('$cate_id', parent_ids) and `is_lock` = 0");
            $ids[] = $cate_id;
            if($cate_ids){
                foreach($cate_ids as $v){
                    $ids[] = $v['id'];
                }
            }
            $ids = implode("','", $ids);

            $lists = Db::query("select `id`,`title`,`thumb`,`star`,`comment` from `zf_article` where `cate_id` in ('$ids') order by `utime` desc limit 15");
        }

        $this->assign('page', 1);
        $this->assign('lists', $lists);
        return $this->fetch();
    } 

    # 示例报告
    public function example(){






        return $this->fetch();
    }






    #文章详情
    public function details(){
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $type = isset($_GET['type']) ? intval($_GET['type']) : 0;

        
        if(!$id){
            layer_error('参数错误！');
        }
        $info = Db::name('article')->field('id,title,details,star,comment')->where(['id'=>$id,'is_lock'=>0])->find();
        if(!$info){
            layer_error('内容不存在或已禁止访问！');
        }

        
        $this->assign('info', $info);
        $this->assign('type', $type);
        return $this->fetch();
    }





}



