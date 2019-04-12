<?php
namespace app\mobile\controller;

use think\Db;

class Index extends Base
{
    public function index()
    {
        
        # 推荐商品
        $recom_goods = Db::query("select `id`,`name`,`desc`,`thumb`,`price` from `zf_goods` where `type` = 3 and `is_del` = 0 order by `utime` desc");
        $this->assign('recom_goods', $recom_goods);


        # 微解读
        $article_cate = Db::query("select `id`,`name` from `zf_category` where `type` = 'article' and `is_view` = 1");
        if($article_cate){
            $ignore = [0];
            $article['recom'] = Db::query("select `id`,`title`,`desc`,`thumb` from `zf_article` where `type` = 1 order by `utime` desc limit 3");
            if($article['recom']){
                foreach($article['recom'] as $v){
                    array_push($ignore,$v['id']);
                }
            }
            $ignore = implode("','", $ignore);
            foreach($article_cate as $k => $v){
                $article['list'][$k] = $v;
                $article['list'][$k]['list'] = Db::query("select `id`,`title`,`desc`,`thumb` from `zf_article` where `cate_id` = '$v[id]' and id not in ('$ignore') order by `utime` desc");
            }
            
            $this->assign('article', $article);

            $article_count = Db::name('article')->where('is_lock',0)->count();
            $this->assign('article_count', $article_count);
        }
        
        
        
        
        return $this->fetch();
    }


    # 研究所
    public function research(){

        return $this->fetch();
    }



    # 微信登录
    public function wx_sign(){

        parent::GetOpenid();


        dump($_SESSION);

    }


}
