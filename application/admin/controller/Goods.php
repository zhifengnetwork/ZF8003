<?php

namespace app\admin\Controller;

use think\Db;

class Goods extends Base{

    # 商品列表
    public function index(){

        $where['is_del'] = ['=', 0];

        $field = '`id`,`name`,`cate_id`,`price`,`self_price`,`promotion_price`,`promotion_to`,`is_stock`,`stock`,`image`,`discount`,`status`,`type`,`limit_stime`,`limit_etime`,`freight`,`freight_temp`,`sold`,`addtime`,`utime`';

        $list = Db::name('goods')->field($field)->where($where)->order('utime desc')->paginate(15);
        $count = Db::name('goods')->where($where)->count();
        $cname[0] = '';
        if($list){
            foreach($list as $v){
                $cids[] = $v['cate_id'];
                if($v['freight_temp'] > 0) $ftids[] = $v['freight_temp'];
            }
            if(isset($cids)){
                $cids = implode("','", $cids);
                $cids = Db::query("select `id`,`name` from `zf_category` where `id` in ('$cids')");
                foreach($cids as $c){
                    $cname[$c['id']] = $c['name'];
                }
            }
            if(isset($ftids) && !empty($ftids)){
                $ftids = implode("','", $ftids);
                $ftids = Db::query("select `id`,`name` from `zf_freight_temp` where `id` in ('$ftids')");
                foreach($ftids as $f){
                    $fname[$f['id']] = $f['name']; 
                }
                $this->assign('fname', $fname);
            }

        }

        $sname = [
            0   =>  '仓库中',
            1   =>  '已上架',
            2   =>  '已下架',
        ];
        
        $tname = [
            0   =>  '普通',
            1   =>  '新品',
            2   =>  '热卖',
            3   =>  '推荐',
            4   =>  '促销',
            5   =>  '限时'
        ];



        $this->assign('tname', $tname);
        $this->assign('sname', $sname);
        $this->assign('cname', $cname);
        $this->assign('list', $list);
        $this->assign('count', $count);
        return $this->fetch();
    }


    # 添加/编辑商品
    public function add_goods(){
        $save_dir = ROOT_PATH . 'public/images/goods/';
        $temp_dir = ROOT_PATH . 'public/images/goods/temp/';
        $src_dir = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].'/public/images/goods/';

        if($_POST){
            $goods_id = isset($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;
            $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $desc = isset($_POST['desc']) ? trim($_POST['desc']) : '';
            $images = isset($_POST['images']) && is_array($_POST['images']) ? $_POST['images'] : array();
            $price = isset($_POST['price']) && Digital_Verification($_POST['price']) ? Digital_Verification($_POST['price']) : 0.00;
            $self_price = isset($_POST['self_price']) && Digital_Verification($_POST['self_price']) ? Digital_Verification($_POST['self_price']) : 0.00;
            $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
            $is_stock = isset($_POST['is_stock']) ? intval($_POST['is_stock']) : 1;
            $discount = isset($_POST['discount']) ? intval($_POST['discount']) : 1;
            $type = isset($_POST['type']) ? intval($_POST['type']) : 0;
            $status = isset($_POST['status']) ? intval($_POST['status']) : 0;
            $promotion_price = isset($_POST['promotion_price']) && Digital_Verification($_POST['promotion_price']) ? Digital_Verification($_POST['promotion_price']) : 0.00;
            $promotion_to = isset($_POST['promotion_to']) ? intval($_POST['promotion_to']) : 0;
            $limit_stime = isset($_POST['limit_stime']) ? strtotime($_POST['limit_stime']) : 0;
            $limit_etime = isset($_POST['limit_etime']) ? strtotime($_POST['limit_etime']) : 0;
            $freight = isset($_POST['freight']) && Digital_Verification($_POST['freight']) ? Digital_Verification($_POST['freight']) : 0.00;
            $freight_temp = isset($_POST['freight_temp']) ? intval($_POST['freight_temp']) : 0;
            $details = isset($_POST['editorValue']) ? addslashes($_POST['editorValue']) : ''; 

            
            if(!$name){
                echo "<script>parent.error('请填写商品名称')</script>";
                exit;
            }
            if(!$category_id){
                echo "<script>parent.error('请选择商品分类')</script>";
                exit;
            }
            
            if(!isset($images[0])){
                echo "<script>parent.error('请上传商品图片')</script>";
                exit;
            }

            $image = implode(',', $images);
            $time = time();
            
            if($goods_id){

                $sql = "update `zf_goods` set `cate_id` = '$category_id', `name` = '$name', `price` = '$price', `self_price` = '$self_price', `image` = '$image', `is_stock` = '$is_stock', `stock` = '$stock', `details` = '$details', `discount` = '$discount', `status` = '$status', `type` = '$type', `promotion_price` = '$promotion_price', `promotion_to` = '$promotion_to', `limit_stime` = '$limit_stime', `limit_etime` = '$limit_etime', `freight` = '$freight', `freight_temp` = '$freight_temp', `utime` = '$time' where `id` = '$goods_id'";

            }else{
                $sql = "insert into `zf_goods` (`cate_id`,`name`,`price`,`self_price`,`image`,`is_stock`,`stock`,`details`,`discount`,`status`,`type`,`promotion_price`,`promotion_to`,`limit_stime`,`limit_etime`,`freight`,`freight_temp`,`addtime`,`utime`) values('$category_id','$name','$price','$self_price','$image','$is_stock','$stock','$details','$discount','$status','$type','$promotion_price','$promotion_to','$limit_stime','$limit_etime','$freight','$freight_temp','$time','$time')";
            }

            $res = Db::execute($sql);
            if($res){
                if (!$goods_id) {
                    $goods_id = Db::name('goods')->getLastInsID();
                }
                $i = 0;
                if(!file_exists($save_dir.$goods_id)){
                    mkdir($save_dir.$goods_id, 0777, true);
                }
                foreach($images as $k=>$v){
                    if(!strstr($v,'images-')){
                        if($k == 0){
                            $thumb = \think\Image::open($temp_dir.$v);
                            $thumb_path = $goods_id.'/thumb.png';
                            $thumb->thumb(150,150,\think\Image::THUMB_CENTER)->save($save_dir.$thumb_path);
                        }
                        $im = \think\Image::open($temp_dir.$v);
                        $savename = $save_dir.$goods_id.'/images-'.$time.$i.'.jpg';
                        $im->save($savename);
                        $images[$k] = 'images-'.$time.$i.'.jpg';
                        $i++;
                    }
                }
                $images = implode(',', $images);
                Db::execute("update `zf_goods` set `image` = '$images',`thumb` = '$thumb_path' where `id` = '$goods_id'");
                
                echo "<script>parent.ajax_from_callback(1,'操作成功，正在跳转...')</script>";
            }else{
                echo "<script>parent.ajax_from_callback(0,'操作失败，请重试！')</script>";
            }

            exit;
        }

        $goods_id = isset($_GET['goods_id']) ? intval($_GET['goods_id']) : 0;

        if($goods_id){
            $info = Db::name('goods')->where('id',$goods_id)->find();
            if($info){
                $cate = Db::name('category')->where('id',$info['cate_id'])->field('name')->find();
                $info['catename'] = $cate['name'];
                $info['image'] = explode(',',$info['image']);
                $image_path = $src_dir.$goods_id.'/';
                
                $this->assign('image_path', $image_path);
                $this->assign('info', $info);
            } 
        }
        
        # 运费模板
        $freight_temp = Db::name('freight_temp')->field('id,name')->select();

        # 商品分类
        $where['parent_id'] = ['=', 0];
        $where['is_lock'] = ['=', 0];
        $cate = Db::name('category')->field('id,name')->where($where)->select();

        $this->assign('freight_temp', $freight_temp);
        $this->assign('cate', $cate);
        return $this->fetch();
    }

    # 修改商品状态
    public function edit_status_goods(){
        $status = isset($_POST['status']) && in_array(intval($_POST['status']), [0,1,2]) ? intval($_POST['status']) : 99;
        $goods_id = isset($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;
        if($goods_id && $status < 3){
            $time = time();
            $res = Db::name('goods')->where('id',$goods_id)->update(['status' => $status, 'utime' => $time]);
            if($res){
                return json(['status' => 1,'utime' => date('Y-m-d H:i:s', $time)]);
            }
        }
        return json(['status' => 0]);
    }

    # 删除商品（伪）
    public function del_goods(){
        $goods_id = isset($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;
        if($goods_id){
            $time = time();
            $res = Db::name('goods')->where('id',$goods_id)->update(['is_del' => 1]);
            if($res){
                return json(['status' => 1]);
            }
        }
        return json(['status' => 0]);
    }


    # 商品分类
    public function category(){

        $where['type'] = ['=', 'goods'];
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
                $sql = "insert into `zf_category` (`name`,`level`,`sort`,`parent_id`,`parent_ids`,`is_lock`,`time`,`type`) values ('$name','$level','$sort','$parent_id','$parent_ids','$is_lock','$time','goods')";
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
        $where['type'] = ['=', 'goods'];
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
     * 删除商品分类
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
            $is_see = isset($_POST['is_see']) ? intval($_POST['is_see']) : 0;
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $other = isset($_POST['other']) && Digital_Verification($_POST['other']) ? $_POST['other'] : 0;
            $areaid = isset($_POST['areaid']) ? $_POST['areaid'] : array();
            $area_money = isset($_POST['area_money']) ? $_POST['area_money'] : array();
            $desc = isset($_POST['desc']) ? trim($_POST['desc']) : '';

            if($is_see > 0){
                exit('???');
            }

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
        $is_see = isset($_GET['is_see']) ? intval($_GET['is_see']) : 0;

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
        
        $this->assign('is_see', $is_see);
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