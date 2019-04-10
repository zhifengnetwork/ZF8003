<?php
namespace app\mobile\controller;

use think\Db;

class Index extends Base
{
    public function index()
    {
        


        # 推荐商品
        $recom_goods = Db::query("select `id`,`name`,`desc`,`thumb`,`price` from `zf_goods` where `type` = 3 and `is_del` = 0 order by `utime` desc limit 2");
        $this->assign('recom_goods', $recom_goods);


        # 微解读
        $article = Db::query("select `id`,`title`,`desc`,`thumb` from `zf_article` where `cate_id` = 13 limit 5");
        $article_count = Db::name('article')->where('cate_id',13)->count();
        $this->assign('article', $article);
        $this->assign('article_count', $article_count);

        
        
        return $this->fetch();
    }
}
