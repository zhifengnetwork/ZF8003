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

        


        return $this->fetch();
    }

    # 商品分类
    public function category(){

        $where['id'] = ['>', 0];
        $keywords = isset($_GET['keywords']) ? trim($_GET['keywords']) : '';
        if($keywords){
            $where['name'] = ['like', "%$keywords%"];
        }

        $list = Db::name('goods_category')->where($where)->paginate(15);
        $count = Db::name('goods_category')->where($where)->count();
        
        
        $this->assign('list', $list);
        $this->assign('count', $count);
        $this->assign('keywords', $keywords);
        return $this->fetch();
    }

    # 增加/编辑商品分类
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
                $pinfo = Db::query("select `level`,`parent_ids` from `zf_goods_category` where `id` = '$parent_id' and `level` < 3");
                if(!$pinfo){
                    return json(['status'=> 0,'msg' => '参数错误！']);
                }

                $level = $pinfo[0]['level'] + 1;
                $parent_ids = $pinfo[0]['parent_ids'] ?  $pinfo[0]['parent_ids'] . ',' . $parent_id : $parent_id;

            }

            $time = time();
            if($category_id > 0){
                $sql = "update `zf_goods_category` set `name` = '$name', `parent_id` = '$parent_id', `sort` = '$sort', `is_lock` = '$is_lock', `parent_ids` = '$parent_ids' where `id` = '$category_id'";
            }else{
                $sql = "insert into `zf_goods_category` (`name`,`level`,`sort`,`parent_id`,`parent_ids`,`is_lock`,`time`) values ('$name','$level','$sort','$parent_id','$parent_ids','$is_lock','$time')";
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
        $pid = 0;
        $cate = Db::name('goods_category')->where($where)->select();

        $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
        if($category_id > 0){
            $where['id'] = ['neq', $category_id];
            $info = Db::name('goods_category')->find($category_id);
            if($info){

                $pid = $info['parent_id'];

                if($info['parent_ids']){
                    $pids = explode(',',$info['parent_ids']);
                    if(isset($pids[1])){
                        $where['parent_id'] = $pids[0];
                        $lcate = Db::name('goods_category')->where($where)->select();
                        
                        $pid = $pids[0];
                        $this->assign('lcate',$lcate);
                    }
                }

                $this->assign('info', $info);
            }
        }
        
        $this->assign('pid', $pid);
        $this->assign('cate', $cate);
        return $this->fetch();
    }

    # 获取下级分类
    public function ajax_getLastCate(){

        $cate_id = isset($_POST['cate_id']) ? intval($_POST['cate_id']) : 0;
        if($cate_id > 0){
            $list = Db::name('goods_category')->field('`id`,`name`')->where(['parent_id'=>['=',$cate_id], 'is_lock' => ['=', 0]])->select();
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

    # 运费模板
    public function freight(){

        $where['id'] = ['>', 0];
        $keywords = isset($_GET['keywords']) ? trim($_GET['keywords']) : '';
        if($keywords){
            $where['name'] = ['like', "%$keywords%"];
        }


        $list = Db::name('freight_temp')->where($where)->paginate(15);
        $count = Db::name('freight_temp')->where($where)->count();
        
        $this->assign('keywords', $keywords);
        $this->assign('list', $list);
        $this->assign('count', $count);

        return $this->fetch();
    }

    # 添加/编辑运费模板
    public function add_freight(){
        
        if($_POST){
            $freight_id = isset($_POST['freight_id']) ? intval($_POST['freight_id']) : 0;
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $other = isset($_POST['other']) && Digital_Verification($_POST['other']) ? $_POST['other'] : 0;
            $areaid = isset($_POST['areaid']) ? $_POST['areaid'] : array();
            $area_money = isset($_POST['area_money']) ? $_POST['area_money'] : array();
            $desc = isset($_POST['desc']) ? trim($_POST['desc']) : '';

            if(!$name){
                iframe_echo('请输入运费模板名称','layermsg');
            }
            $temp['freight'] = $other;
            if($areaid){
                foreach($areaid as $k => $v){
                    if( Digital_Verification($v) && ( isset($area_money[$k]) && Digital_Verification($area_money[$k]) !== false ) ){
                        $temp[$v] = $area_money[$k];
                    }
                }
            }
            $temp = json_encode($temp);
            $time = time();

            if($freight_id > 0){
                $sql = "update `zf_freight_temp` set `name` = '$name',`temp` = '$temp',`desc` = '$desc',`time` = '$time' where `id` = '$freight_id'";
            }else{
                $sql = "insert into `zf_freight_temp` (`name`,`temp`,`desc`,`time`) values ('$name','$temp','$desc','$time')";
            }
            $res = Db::execute($sql);
            if($res){
                iframe_echo(['操作成功，正在跳转...',1],'layermsg');
            }else{
                iframe_echo('操作失败，请重试！','layermsg');
            }

            exit;
        }


        $freight_id = isset($_GET['freight_id']) ? intval($_GET['freight_id']) : 0;
        if($freight_id){
            $info = Db::name('freight_temp')->where('id', $freight_id)->find();
            if($info){
                $temp = json_decode($info['temp'],true);
                unset($info['temp']);
                $other = $temp['freight'];
                unset($temp['freight']);
                if($temp){
                    foreach($temp as $k => $v){
                        $areaid[] = $k;
                        if(!isset($one_k)) $one_k = $k;
                    }
                    $areaid = implode("','",$areaid);
                    $areaname = Db::query("select `id`,`name` from `zf_area` where `id` in ('$areaid')");
                    if($areaname){
                        foreach($areaname as $ar){
                            $aname[$ar['id']] = $ar['name'];
                        }

                        $this->assign('aname', $aname);
                    }
                    // dump($temp);exit;
                    $this->assign('one_k', $one_k);
                    $this->assign('temp', $temp);
                }
                
                $this->assign('freight_id',$freight_id);
                $this->assign('other',$other);
                $this->assign('info',$info);
            }
        }
        
        
        return $this->fetch();
    }

    /**
     * 删除运费模板
     */
    function del_freight(){
        if($_POST){
            $freight_id = isset($_POST['freight_id']) ? intval($_POST['freight_id']) : 0;
            if($freight_id){
                $res = Db::table('zf_freight_temp')->delete($freight_id);
                if($res){
                    return json(['status' => 1]);
                }
            }
        }
        return json(['status' => 0]);
    }




}