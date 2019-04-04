<?php

namespace app\admin\Controller;

use think\Db;

class Goods extends Base{





    # 商品列表
    public function index(){

        $where['is_del'] = ['=', 0];

        $field = '`id`,`name`,`price`,`is_stock`,`stock`,`image`,`discount`,`status`,`type`,`limit_stime`,`limit_etime`,`freight`,`freight_temp`,`sold`,`addtime`,`utime`';

        $list = Db::name('goods')->field($field)->where($where)->paginate(15);
        $count = Db::name('goods')->where($where)->count();
        
        
        $this->assign('list', $list);
        $this->assign('count', $count);
        return $this->fetch();
    }


    # 添加/编辑商品
    public function add_goods(){

        $goods_id = isset($_GET['goods_id']) ? intval($_GET['goods_id']) : 0;

        if($goods_id){
            $info = Db::name('goods')->where('id',$goods_id)->find();




        }

        



    }

    # 运费模板
    public function freight(){


        $where['id'] = ['>', 0];


        $list = Db::name('freight_temp')->where($where)->paginate(15);
        $count = Db::name('freight_temp')->where($where)->count();
        
        
        $this->assign('list', $list);
        $this->assign('count', $count);

        return $this->fetch();
    }

    # 添加/编辑运费模板
    public function add_freight(){

        $freight_id = isset($_GET['freight_id']) ? intval($_GET['freight_id']) : 0;

        
        return $this->fetch();
    }


}