<?php

namespace app\mobile\controller;
use think\Controller;
use think\Db;
use think\Session;

class Article extends Base{

    public $user_id = 0;//用户id

    public function _initialize(){
        parent::_initialize();

        $this->user_id = session('user.id');
        $this->assign('user_id',$this->user_id);
    }

    public function index(){


        $this->redirect('/mobile');
    }

    # 微解读
    public function micro(){
        # 系统设置
        $conf = Db::query("select `value` from `zf_config` where `type` = 'hom_module_bind' and `name` = 'micro'");
        $lists = array();
        if(!isset($conf[0]['value'])){
            layer_error('访问信息不存在或已禁止访问！');
        }
        
        $cate_id = $conf[0]['value'];
        $cate_ids = Db::query("select `id` from `zf_category` where find_in_set('$cate_id', parent_ids) and `is_lock` = 0");
        $ids[] = $cate_id;
        if($cate_ids){
            foreach($cate_ids as $v){
                $ids[] = $v['id'];
            }
        }
        $ids = implode("','", $ids);

        $lists = Db::query("select `id`,`title`,`thumb`,`star`,`comment` from `zf_article` where `cate_id` in ('$ids') order by `sort` desc,`utime` desc limit 15");
        
        if($lists){
            $lids = array_column($lists,'id');
            $start_ids = Db::name('article_star')->where('user_id',$this->user_id)->where('article_id','in',$lids)->select();

            foreach ($lists as $k1 => $v1) {
                $lists[$k1]['is_like'] = 0;
                foreach ($start_ids as $k2 => $v2) {
                    if ($v2['article_id'] == $v1['id']) {
                        $lists[$k1]['is_like'] = 1;
                        unset($start_ids[$k2]);
                    }
                }
            }
        }
        
        $this->assign('page', 1);
        $this->assign('lists', $lists);
        return $this->fetch();
    } 

    # 示例报告
    public function example(){
        # 系统设置
        $conf = Db::query("select `value` from `zf_config` where `type` = 'hom_module_bind' and `name` = 'example'");
        
        $lists = array();
        if (!isset($conf[0]['value'])) {
            layer_error('访问信息不存在或已禁止访问！');
        }

        $cate_id = $conf[0]['value'];
        $cate_ids = Db::query("select `id` from `zf_category` where find_in_set('$cate_id', parent_ids) and `is_lock` = 0");
        $ids[] = $cate_id;
        if ($cate_ids) {
            foreach ($cate_ids as $v) {
                $ids[] = $v['id'];
            }
        }
        $ids = implode("','", $ids);

        # 栏目推荐 2 条
        $recom = Db::query("select `id`,`title`,`thumb` from `zf_article` where `type` = 1 and `is_lock` = 0 and `cate_id` in ('$ids') order by `sort` desc,`utime` desc limit 2");
        $this->assign('recom',$recom);
        
        # 子栏目
        $cate = Db::query("select `id`, `name`,`thumb`,`desc` from `zf_category` where `parent_id` = '$cate_id' and `is_lock` = 0 order by `sort` desc,`time` desc");
        if ($cate) {
            foreach ($cate as $ck => $cv) {
                $cate[$ck]['count'] = Db::name('article')->where(['cate_id'=>$cv['id'], 'is_lock' => 0])->count();
            }
            $this->assign('cate', $cate);
        }
        


        return $this->fetch();
    }


    # 示例报告，列表
    public function example_list(){
        $cate_id = isset($_GET['cate_id']) ? intval($_GET['cate_id']) : 0;
        
        $cate_info = Db::name('category')->field('id,name,thumb')->where(['id'=>$cate_id,'is_lock'=>0,'type'=>'article'])->find();
        
        if(!$cate_info){
            layer_error('访问信息不存在或已禁止访问！');
        }
        $this->assign('cate_info', $cate_info);

        $count = Db::name('article')->where(['cate_id'=>$cate_id,'is_lock'=>0])->count();
        $this->assign('count',$count);

        $lists = Db::query("select `id`,`title` from `zf_article` where `cate_id` = '$cate_id' and `is_lock` = 0");
        $this->assign('lists', $lists);

        return $this->fetch();
    }


    # 皮肤管理
    public function skin(){
        # 系统设置
        $conf = Db::query("select `value` from `zf_config` where `type` = 'hom_module_bind' and `name` = 'skin'");
        if (!$conf) {
            layer_error('访问信息不存在或已禁止访问！');
        }

        $cate_id = $conf[0]['value'];

        $cate_ids = Db::query("select `id` from `zf_category` where find_in_set('$cate_id', parent_ids) and `is_lock` = 0");
        $ids[] = $cate_id;
        if ($cate_ids) {
            foreach ($cate_ids as $v) {
                $ids[] = $v['id'];
            }
        }
        $ids = implode("','", $ids);

        $lists = Db::query("select `id`,`title`,`thumb`,`desc` from `zf_article` where `cate_id` in ('$ids') and `is_lock` = 0 order by `sort` desc, `utime` desc");


        $this->assign('lists', $lists);

        return $this->fetch();
    }



    # 文章详情
    public function details(){
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        
        if(!$id){
            layer_error('参数错误！');
        }
        $info = Db::name('article')->field('id,title,details,star,comment')->where(['id'=>$id,'is_lock'=>0])->find();
        if(!$info){
            layer_error('内容不存在或已禁止访问！');
        }
        $info['is_like'] = 0;
        if ($this->user_id > 0) {
            $like = new \app\mobile\controller\Like;
            $info['is_like'] = $like->is_like($id);
        }
        
        $this->assign('info', $info);
        return $this->fetch();
    }
}