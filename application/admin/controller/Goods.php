<?php

namespace app\admin\controller;

use think\Db;
use think\Loader;
class Goods extends Base{

    # 商品列表
    public function index(){

        $jur = $this->check_jurisdiction_ok('r','goods/index');
        if(!$jur){
            error_h1('访问权限受控，您无权操作此项！', '至少拥有‘查看’的权限');
        }
        $where['is_del'] = ['=', 0];

        $field = '`id`,`name`,`cate_id`,`price`,`self_price`,`promotion_price`,`promotion_to`,`is_stock`,`stock`,`image`,`discount`,`status`,`type`,`limit_stime`,`limit_etime`,`freight`,`freight_temp`,`sold`,`addtime`,`utime`';
        $keywords = isset($_GET['keywords']) ? trim($_GET['keywords']) : '';
        if ($keywords) {
            $where['name'] = ['like', "%$keywords%"];
        }
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
            $jur = $this->check_jurisdiction_ok('w','goods/index');
            if(!$jur){
                echo "<script>parent.error('访问权限受控，您无权操作此项！至少拥有‘编辑’的权限')</script>";
                exit;
            }
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

                $sql = "update `zf_goods` set `cate_id` = '$category_id', `name` = '$name',`desc` = '$desc', `price` = '$price', `self_price` = '$self_price', `image` = '$image', `is_stock` = '$is_stock', `stock` = '$stock', `details` = '$details', `discount` = '$discount', `status` = '$status', `type` = '$type', `promotion_price` = '$promotion_price', `promotion_to` = '$promotion_to', `limit_stime` = '$limit_stime', `limit_etime` = '$limit_etime', `freight` = '$freight', `freight_temp` = '$freight_temp', `utime` = '$time' where `id` = '$goods_id'";
                $action = 'edit_goods';
                $desc   = '编辑商品';
                $log    = adminLog($action, $desc);   
            }else{
                $sql = "insert into `zf_goods` (`cate_id`,`name`,`desc`,`price`,`self_price`,`image`,`is_stock`,`stock`,`details`,`discount`,`status`,`type`,`promotion_price`,`promotion_to`,`limit_stime`,`limit_etime`,`freight`,`freight_temp`,`addtime`,`utime`) values('$category_id','$name','$desc','$price','$self_price','$image','$is_stock','$stock','$details','$discount','$status','$type','$promotion_price','$promotion_to','$limit_stime','$limit_etime','$freight','$freight_temp','$time','$time')";
                $action = 'add_goods';
                $desc   = '添加商品';
                $log    = adminLog($action, $desc);   
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

                $thumb_path = $goods_id.'/thumb.png';
                foreach($images as $k=>$v){
                    if(!strstr($v,'images-')){
                        if($k == 0){
                            $thumb = \think\Image::open($temp_dir.$v);
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

        $jur = $this->check_jurisdiction_ok('w','goods/index');
        if(!$jur){
            error_h1('访问权限受控，您无权操作此项！', '至少拥有‘编辑’的权限');
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
        $jur = $this->check_jurisdiction_ok('w','goods/index');
        if(!$jur){
            return json(['status' => 0,'msg' => '访问权限受控，您无权操作此项！至少拥有‘编辑’的权限']);
        }
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
        $jur = $this->check_jurisdiction_ok('w','goods/index');
        if(!$jur){
            return json(['status' => 0,'msg' => '访问权限受控，您无权操作此项！至少拥有‘编辑’的权限']);
        }
        $goods_id = isset($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;
        if($goods_id){
            $time = time();
            $res = Db::name('goods')->where('id',$goods_id)->update(['is_del' => 1]);
            if($res){
                $action = 'del_goods';
                $desc   = '删除商品';
                $log    = adminLog($action, $desc);                   
                return json(['status' => 1]);
            }
        }
        return json(['status' => 0]);
    }


    # 商品分类
    public function category(){

        $jur = $this->check_jurisdiction_ok('r','goods/category');
        if(!$jur){
            error_h1('访问权限受控，您无权操作此项！', '至少拥有‘查看’的权限');
        }
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
                $pids[] = $v['parent_id'];
            }
        //    dump($pids);
            if(isset($pids) && $pids){
                $pids = implode("','", $pids);
                $pinfo = Db::query("select `id`,`name` from `zf_category` where `id` in ('$pids')");
                foreach($pinfo as $p){
                    $pname[$p['id']] = $p['name'];
                }
                // dump($pname);
                $this->assign('pname', $pname);
            }
        }
        // dump($list);
        $this->assign('list', $list);
        $this->assign('count', $count);
        $this->assign('keywords', $keywords);
        return $this->fetch();
    }

    # 增加/编辑商品分类
    public function add_category(){

        if($_POST){
            $jur = $this->check_jurisdiction_ok('w','goods/category');
            if(!$jur){
                return json(['status'=> 0, 'msg' => '访问权限受控，您无权操作此项！至少拥有‘编辑’的权限']);
            }
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
                $action = 'edit_goods';
                $desc   = '编辑商品分类';
                $log    = adminLog($action, $desc);               
            }else{
                $sql = "insert into `zf_category` (`name`,`level`,`sort`,`parent_id`,`parent_ids`,`is_lock`,`time`,`type`) values ('$name','$level','$sort','$parent_id','$parent_ids','$is_lock','$time','goods')";
                $action = 'add_goods';
                $desc   = '添加商品分类';
                $log    = adminLog($action, $desc);                
            }
            $res = Db::execute($sql);
            if($res){
                return json(['status' => 1, 'msg' => '操作成功！']);
            }else{
                return json(['status'=> 0, 'msg' => '操作失败，请重试！']);
            }

            exit;
        }

        $jur = $this->check_jurisdiction_ok('w','goods/category');
        if(!$jur){
            error_h1('访问权限受控，您无权操作此项！', '至少拥有‘编辑’的权限');
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
        $jur = $this->check_jurisdiction_ok('d','goods/category');
        if(!$jur){
            return json(['status'=> 0, 'msg' => '访问权限受控，您无权操作此项！至少拥有‘删除’的权限']);
        }
        if($_POST){
            $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
            if($category_id){
                $res = Db::table('zf_category')->delete($category_id);
                if($res){
                    $action = 'del_goods';
                    $desc   = '删除商品分类';
                    $log    = adminLog($action, $desc); 
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
        $jur = $this->check_jurisdiction_ok('r','goods/freight');
        if(!$jur){
            error_h1('访问权限受控，您无权操作此项！','至少拥有‘查看’的权限');
        }
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
            $jur = $this->check_jurisdiction_ok('w','goods/freight');
            if(!$jur){
                iframe_echo('访问权限受控，您无权操作此项！至少拥有‘编辑’的权限','layermsg');
            }
            $freight_id = isset($_POST['freight_id']) ? intval($_POST['freight_id']) : 0;
            $is_see = isset($_POST['is_see']) ? intval($_POST['is_see']) : 0;
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $other = $_POST['other'];
            if($other == ''){
                iframe_echo('默认运费必须填写','layermsg');
            }
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

            $where['name'] = $name;
            if($freight_id){
                $where['id'] = ['<>',$freight_id];
            }
            $res = Db::name('freight_temp')->where($where)->find();
            if($res){
                iframe_echo('运费模板名称不能重复','layermsg');
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
                $action = 'edit_freight';
                $desc   = '编辑运费模板';
                $log    = adminLog($action, $desc);             
            }else{
                $sql = "insert into `zf_freight_temp` (`name`,`temp`,`desc`,`time`) values ('$name','$temp','$desc','$time')";
                $action = 'add_freight';
                $desc   = '添加运费模板';
                $log    = adminLog($action, $desc);   
            
            }
            $res = Db::execute($sql);
            if($res){
                iframe_echo(['操作成功，正在跳转...',1],'layermsg');
            }else{
                iframe_echo('操作失败，请重试！','layermsg');
            }

            exit;
        }


        $jur = $this->check_jurisdiction_ok('w','goods/freight');
        if(!$jur){
            error_h1('访问权限受控，您无权操作此项！','至少拥有‘编辑’的权限');
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
        $jur = $this->check_jurisdiction_ok('d','goods/freight');
        if(!$jur){
            return json(['status' => 0, 'msg' => '访问权限受控，您无权操作此项！至少拥有‘编辑’的权限']);
        }
        if($_POST){
            $freight_id = isset($_POST['freight_id']) ? intval($_POST['freight_id']) : 0;
            if($freight_id){
                $res = Db::table('zf_freight_temp')->delete($freight_id);
                if($res){
                    $action = 'del_freight';
                    $desc   = '删除运费模板';
                    $log    = adminLog($action, $desc);   
                    return json(['status' => 1]);
                }
            }
        }
        return json(['status' => 0]);
    }

    /**
     * 优惠券列表
     */
    public function goods_coupon(){
        $jur = $this->check_jurisdiction_ok('r','goods/goods_coupon');
        if(!$jur){
            error_h1('访问权限受控，您无权操作此项！','至少拥有‘查看’的权限');
        }
        $where['id'] = ['>', 0];
        $keywords = isset($_GET['keywords']) ? trim($_GET['keywords']) : '';
        if ($keywords) {
            $where['name'] = ['like', "%$keywords%"];
        }

        $list = Db::name('goods_coupon')->where($where)->paginate(15);
        $count = Db::name('goods_coupon')->where($where)->count();
        $cname[0] = '';
        if ($list) {
            foreach ($list as $v) {
                $cids[] = $v['goods_id'];
            }
            // dump($cids);
            if (isset($cids)) {
                $cids = implode("','", $cids);
                $cids = Db::query("select `id`,`name` from `zf_goods` where `id` in ('$cids')");
                foreach ($cids as $c) {
                    $cname[$c['id']] = $c['name'];
                }
            }
        }
        $this->assign('cname', $cname);
        $this->assign('keywords', $keywords);
        $this->assign('list', $list);
        $this->assign('count', $count);
        return $this->fetch();
    }
    /**
     * 添加优惠券
     */
    public function add_coupon(){
        $data1 = input('post.');
       
        if ($_POST) {
            $jur = $this->check_jurisdiction_ok('w','goods/goods_coupon');
            if(!$jur){
                echo "<script>parent.error('访问权限受控，您无权操作此项！至少拥有‘编辑’的权限')</script>";
                exit;
            }
            // 数据验证
            $goodsValidate = Loader::Validate('Goods');
            if (!$goodsValidate->check($data1)) {
                $baocuo = $goodsValidate->getError();
                echo "<script>parent.error('".$baocuo."')</script>";
                exit;
            }
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $goods_id = isset($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;
            $term = isset($_POST['term']) ? intval($_POST['term']) : 0;
            $quota = isset($_POST['quota']) && Digital_Verification($_POST['quota']) ? Digital_Verification($_POST['quota']) : 0.00;
            $money = isset($_POST['money']) && Digital_Verification($_POST['money']) ? Digital_Verification($_POST['money']) : 0.00;
            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 0;
            $status = isset($_POST['status']) ? intval($_POST['status']) : 0;
            $deadline = isset($_POST['deadline']) ? strtotime($_POST['deadline']) : 0;

            if (!$term) {
                echo "<script>parent.error('请正确填写使用期限')</script>";
                exit;
            }
            if (!$quota) {
                echo "<script>parent.error('请正确填写使用额度')</script>";
                exit;
            }
            if ($deadline<time()) {
                echo "<script>parent.error('日期填写不正确')</script>";
                exit;
            }               
            
            // 插入数据
            $data = [
               'name' => $name,
               'goods_id' => $goods_id,
               'term' =>$term,
               'quota'=>$quota,
               'money'=>$money,
               'limit'=>$limit,
               'status'=>$status,
               'deadline'=>$deadline,
               'addtime' =>time()      
            ];
            // 编辑跟新数据
            if($_POST['coupon_id']){
                unset($data['addtime']);
                $data['id']=$_POST['coupon_id'];
                $data['updatetime'] =time();
                
                // 启动事务
                Db::startTrans();
                try {
                    $res = Db::name('goods_coupon')->update($data);
                    // 更新user_coupon表
                    $where = [
                        'goods_id' => $goods_id,
                        'coupon_id' => $_POST['coupon_id']
                    ];
                    $where1 = [
                        'quota' => $quota,
                        'money' => $money
                    ];
                    $u_cou = Db::name('user_coupon')->where($where)->update($where1);
                    $action = 'update_coupon';
                    $desc   = '编辑优惠券';
                    $log    = adminLog($action, $desc); 
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    echo "<script>parent.ajax_from_callback(0,'操作失败，正在跳转...')</script>"; 
                }                
            }else{
                $res = Db::name('goods_coupon')->insert($data);
                $action = 'add_coupon';
                $desc   = '添加优惠券';
                $log    = adminLog($action, $desc);   
            }
            
            if($res){
                echo "<script>parent.ajax_from_callback(1,'操作成功，正在跳转...')</script>";
            }else{
                echo "<script>parent.ajax_from_callback(0,'操作失败，正在跳转...')</script>"; 
            }
        }

        $jur = $this->check_jurisdiction_ok('w','goods/goods_coupon');
        if(!$jur){
            error_h1('访问权限受控，您无权操作此项！','至少拥有‘编辑’的权限');
        }
        $coupon_id = isset($_GET['coupon_id']) ? intval($_GET['coupon_id']) : 0;
        // 编辑
        if($coupon_id){
            $info = Db::name('goods_coupon')->where('id', $coupon_id)->find();
            if($info){
                $where = [
                    'id' => $info['goods_id'],
                    'is_del' => 0,
                ];
                // 查找未删除的商品信息
                $goods = Db::name('goods')->where($where)->find();  
                $this->assign('goods',$goods);
                $this->assign('info',$info);
            }
        }         

        return $this->fetch();
    }


    /**
     * 添加商品（优惠券添加页面）
     */
    public function select_coupon_goods(){
        
        $where['id'] = ['>', 0];
        $keywords = isset($_GET['keywords']) ? trim($_GET['keywords']) : '';
        if ($keywords) {
            $where['name'] = ['like', "%$keywords%"];
        }

        $list = Db::name('goods')->where($where)->paginate(15);
        $count = Db::name('goods')->where($where)->count();

        $this->assign('keywords', $keywords);
        $this->assign('list', $list);
        $this->assign('count', $count);
        return $this->fetch();
    }

    // 修改优惠券状态
    public function edit_status_coupon()
    {
        $jur = $this->check_jurisdiction_ok('w','goods/goods_coupon');
        if(!$jur){
            return json(['status' => 0,'msg'=>'访问权限受控，您无权操作此项！至少拥有‘编辑’的权限']);
        }
        $status = isset($_POST['status']) && in_array(intval($_POST['status']), [0, 1]) ? intval($_POST['status']) : 99;
        $coupon_id = isset($_POST['coupon_id']) ? intval($_POST['coupon_id']) : 0;
        if ($coupon_id && $status < 2) {
            $time = time();
            $res = Db::name('goods_coupon')->where('id', $coupon_id)->update(['status' => $status, 'updatetime' => $time]);
            if ($res) {
                return json(['status' => 1, 'utime' => date('Y-m-d H:i:s', $time)]);
            }
        }
        return json(['status' => 0]);
    }

    /**
     * 删除和批量删除优惠券
     */
    function del_coupon(){
        $jur = $this->check_jurisdiction_ok('d','goods/goods_coupon');
        if(!$jur){
            return json(['status' => 0,'msg'=>'访问权限受控，您无权操作此项！至少拥有‘删除’的权限']);
        }
        $data = input('post.');
        if($_POST){
            Db::startTrans();
            if($data['act'] == 'del'){
                $coupon_id = isset($_POST['coupon_id']) ? intval($_POST['coupon_id']) : 0;
                if ($coupon_id) {
                    // 删除users_coupon表的相应优惠券数据
                    // 启动事务
                    try {
                        $res   = Db::name('goods_coupon')->where('id', $coupon_id)->delete();
                        $u_res = Db::name('user_coupon')->where('coupon_id', $coupon_id)->delete();
                        // 提交事务
                        Db::commit();
                        $action = 'del_coupon';
                        $desc   = '删除优惠券';
                        $log    = adminLog($action, $desc); 
                        return json(['status' => 1,'msg'=>'删除成功']);
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        return json(['status' => 0,'msg'=>'删除失败']);
                    }
                }
            }else{
                $id = json_decode($data['id'], true);
                $where['id'] = array('in', $id);
                $where1['coupon_id'] = array('in', $id);
                // 启动事务
                try {
                    $res   =  Db::name('goods_coupon')->where($where)->delete();
                    // 删除users_coupon表的相应优惠券数据
                    $u_res =  Db::name('user_coupon')->where($where1)->delete();      
                    // 提交事务
                    Db::commit();
                    $action = 'bdel_coupon';
                    $desc   = '批量删除优惠券';
                    $log    = adminLog($action, $desc); 
                    return json(['status' => 1,'msg'=>'删除成功']);
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    return json(['status' => 0,'msg'=>'删除失败']);
                }
            }
        }
        return json(['status' => 0,'msg'=>'操作失败']);
    }


    # 订单列表
    public function order(){
        $jur = $this->check_jurisdiction_ok('r','goods/order');
        if(!$jur){
            error_h1('访问权限受控，您无权操作此项！','至少拥有‘查看’的权限');
        }
        $where['id'] = ['>', 0];

        $time = isset($_GET['time']) ? trim($_GET['time']) : 'add_time';
        $time_min = isset($_GET['time_min']) ? trim($_GET['time_min']) : '';
        $time_max = isset($_GET['time_max']) ? trim($_GET['time_max']) : '';
        $name = isset($_GET['name']) ? trim($_GET['name']) : '';
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        $type = isset($_GET['type']) ? trim($_GET['type']) : 'order';

        if($time_min){
            $where[$time] = ['>=', strtotime($time_min)];
        }
        if($time_max){
            $where[$time] = ['<=', strtotime($time_max)+86399];
        }
        if($status){
            $where['order_status'] = ['=', $status - 1];
        }
        if ($name) {
            if($type == 'order'){
                $where['order_sn'] = ['like',"%$name%"];
            }else{
                $user_id = Db::name('users')->where('nickname',$name)->value('id');
                if($user_id){
                    $where['user_id'] = ['=', "$user_id"];
                }
            }
        }
        
        $lists = Db::name('order')->where($where)->order('add_time desc')->paginate(15);      
        $count = Db::name('order')->where($where)->count();
        $list = array();
        if($lists){
            foreach($lists as $k => $v){
                $list[$k] = $v;
                # 地址
                $list[$k]['province_name'] = Db::name('area')->where('id',$v['province'])->value('name');
                $list[$k]['city_name'] = Db::name('area')->where('id',$v['city'])->value('name');
                $list[$k]['district_name'] = Db::name('area')->where('id',$v['district'])->value('name');

                # 商品信息
                $list[$k]['name'] = Db::name('goods')->where('id',$v['goods_id'])->value('name');

                # 会员信息
                $list[$k]['user_info'] = Db::name('users')->field('nickname,email')->where('id',$v['user_id'])->find();
            }

        }


        # 搜索条件
        $this->assign('time',$time);
        $this->assign('time_min',$time_min);
        $this->assign('time_max',$time_max);
        $this->assign('name',$name);
        $this->assign('status',$status);
        $this->assign('type',$type);


        $this->assign('sname',[0=>'待付款',1=>'待发货',2=>'待收货',3=>'待评价',4=>'交易成功']);
        $this->assign('list',$list);
        $this->assign('lists',$lists);
        $this->assign('count', $count);
        return $this->fetch();
    }


    # 订单详情 | 编辑订单
    public function order_info(){


        if($_POST){
            $jur = $this->check_jurisdiction_ok('w','goods/order');
            if(!$jur){
                echo "<script>parent.error('访问权限受控，您无权操作此项！至少拥有‘操作’的权限');</script>";exit;
            }
            if($_POST['submit'] == '发货'){
                
                $id = intval($_POST['id']);
                $shipping_name = trim($_POST['shipping_name']);
                $shipping_code = trim($_POST['shipping_code']);
                $admin_note = trim($_POST['admin_note']);
                $res = Db::name('order')->where('id',$id)->update(['shipping_name'=>$shipping_name, 'shipping_code'=>$shipping_code, 'shipping_time'=>time(), 'admin_note'=>$admin_note, 'order_status' => 2]);
                if($res){
                    echo "<script>parent.success('操作成功！');</script>";exit;
                }else{
                    echo "<script>parent.error('操作失败！');</script>";exit;
                }
            }



            exit;
        }
        
        $jur = $this->check_jurisdiction_ok('w','goods/order');
        if(!$jur){
            error_h1('访问权限受控，您无权操作此项！','至少拥有‘操作’的权限');
        }

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        $info = Db::query("select a.*,b.name,c.nickname,c.email as user_email from `zf_order` as a left join `zf_goods` as b on a.goods_id = b.id left join `zf_users` as c on a.user_id = c.id where a.id = '$id'");
        if(!$info){
            echo "<script>parent.open_closeAll('订单信息不存在！');</script>";exit;
        }


        $this->assign('sname',[0=>'待付款',1=>'待发货',2=>'待收货',3=>'待评价',4=>'交易成功']);
        $this->assign('info', $info[0]);
        return $this->fetch();
    }
}