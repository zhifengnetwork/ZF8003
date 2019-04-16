<?php

namespace app\mobile\controller;
use think\Controller;
use think\Db;
use think\Session;

class Article extends Base{

    public $user_id = 0;//用户id

    public function __construct(){
        parent::__construct();

        $this->user_id = session('user_id.id');
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
        
        $info['is_like'] = $this->is_like($info['id']);
        
        $this->assign('info', $info);
        return $this->fetch();
    }

    # 点赞/取消点赞
    public function give_a_like()
    {
        $id = input('id/d');
        $user_id = $this->user_id;
        $return = ['code' => 0];
        if ($id && $user_id) {
            $where = array('user_id'=>$user_id,'article_id'=>$id);
            $is_like = Db::name('article_star')->where($where)->find();
            $count = 0;
            Db::startTrans();
            if ($is_like) {
                $bool = Db::name('article_star')->where($where)->delete();
                $count = -1;
            } else {
                $where['addtime'] = time();
                $bool = Db::name('article_star')->insertGetId($where);
                $count = 1;
            }

            if ($bool) {
                $is_update = Db::name('article')->where('id',$id)->setInc('star',$count);
                if ($is_update) {
                    Db::commit();
                    $return['code'] = 1;
                } else {
                    Db::rollback();
                }
            }
        }

        return $return;
    }

    # 是否点赞
    public function is_like($id)
    {
        $like = Db::name('article_star')->where('user_id',$this->user_id)->where('article_id',$id)->find();
        $is_like = $like ? 1 : 0;
        return $is_like;
    }

    # 评论
    public function comment()
    {
        $id = input('id/d');
        
        if(!$id){
            layer_error('参数错误！');
        }
        
        $this->assign('id',$id);
        return $this->fetch();
    }

    # 获取评论
    public function get_comment()
    {
        $data = input('get.');

        $result['lists'] = Db::name('comment')->where(['to'=>$data['id'],'type'=>$data['type'],'status'=>1])->page($data['page'],$data['count'])->select();
        
        if ($result['lists']) {
            $id = array_column($result['lists'],'user_id');
            $user = Db::name('users')->field('id,avatar,nickname')->select($id);
            
            foreach ($result['lists'] as $k1 => $v1) {
                $result['lists'][$k1]['avatar'] = '';
                $result['lists'][$k1]['nickname'] = '';
                $result['lists'][$k1]['del'] = '';
                foreach ($user as $k2 => $v2) {
                    if ($v1['user_id'] == $v2['id']) {
                        $result['lists'][$k1]['avatar'] = $v2['avatar'];
                        $result['lists'][$k1]['nickname'] = $v2['nickname'];
                    }
                }
            }
        }
        
        return json($result);
    }

    # 处理评论
    public function handle_comment()
    {
        $comment = input('post.');
        if ($comment) {
            $comment = trim($comment);
        }
        return ['code'=>1];
    }
}