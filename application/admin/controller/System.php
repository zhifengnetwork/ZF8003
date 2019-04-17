<?php
/**
 * 系统设置
 */

namespace app\admin\Controller;

use think\Db;
use think\Exception;

class System extends Base
{
    private $list;//基本设置菜单

    public function __construct(){
        parent::__construct();

        $this->list = array(
            'shop_info' => ['order'=>0,'url'=>'setting','name'=>"商店信息"],
            'smtp'      => ['order'=>1,'url'=>'smtp','name'=>"邮箱设置"],
            'weixin'      => ['order'=>2,'url'=>'weixin','name'=>"微信设置"],
            'cash'      => ['order'=>3,'url'=>'cash','name'=>"资金设置"],
        );

        $this->assign('list',$this->list);
    }

    public function index(){

    }

    # 商店设置
    public function setting()
    {
        $type = "web_setting";
        $temp = $this->get_setting($type);
        $config = $this->handle_setting($temp);

        if($_POST){
            $data = input('post.');
            if (isset($data['type'])) {
                if (isset($data['web_logo'])) {
                    if (isset($config['web_logo']) && ($data['web_logo'] == $config['web_logo'])) {
                        $num = 1;
                    } else {
                        $logo = $this->move_img($data['web_logo'],'web_logo');
                        $data['web_logo'] = $logo;
                    }
                }
                if (isset($data['title_logo'])) {
                    if (isset($config['title_logo']) && ($data['title_logo'] == $config['title_logo'])) {
                        $num = 1;
                    } else {
                        $title_logo = $this->move_img($data['title_logo'],'title_logo');
                        $data['title_logo'] = $title_logo;
                    }
                }

                $bool = $this->setting_handle($data);
                $temp = $this->get_setting($type);
                $config = $this->handle_setting($temp);
            }
        }
        
        $province = Db::name('area')->where(array('parent_id'=>0))->select();
        $city = array();
        $info = $this->list;
        if (isset($data['id']) && intval($data['id'] > 0)) {
            $city =  Db::name('area')->where('parent_id',intval($data['id']))->select();
            $result = $city ? ['code' => 1, 'data' => $city] : ['code' => 0];
            return json($result);
        }
        
        if (isset($config['province'])) {
            $province_name = Db::name('area')->where('id',$config['province'])->value('name');
            $config['province'] = $province_name;

            if (isset($config['city'])) {
                $city_name = Db::name('area')->where('id',$config['city'])->value('name');
                $config['city'] = $city_name;
            }

            if (isset($config['area'])) {
                $area_name = Db::name('area')->where('id',$config['area'])->value('name');
                $config['area'] = $area_name;
            }
        }
        
        $this->assign('type', $type);
        $this->assign('province',$province);
        $this->assign('url',$info['shop_info']['url']);
        $this->assign('index',$info['shop_info']['order']);
        $this->assign('config',$config);
        return $this->fetch();
    }

    # 设置处理函数
    public function setting_handle($data)
    {
        $bool = false;
        if (is_array($data)) {
            $type = $data['type'];
            array_shift($data);//去除第一个元素
            $temp = array();
            $key_diff = array();
            $key_same = array();
            $id = array();
            $config = $this->get_setting($type);
            
            if ($config) {
                foreach($config as $k2 => $v2){
                    $temp[$v2['name']] = $v2['value'];
                    $id[$v2['name']] = $v2['id'];
                }
                
                $key_diff = array_diff_key($data,$temp);//取键名差集
                $key_same = array_intersect_key($data,$temp);//取键名交集
            }
            
            $add = array();
            $update = array();
            Db::startTrans();//开启事务
            
            try {
                if ($key_diff) {
                    foreach($key_diff as $k1 => $v1){
                        array_push($add,['name'=>$k1,'value'=>trim($v1),'type'=>$type]);
                    }
                    $bool = Db::name('config')->insertAll($add);
                }
                if ($key_same) {
                    foreach($key_same as $k3 => $v3){
                        array_push($update,['id'=>$id[$k3],'value'=>$v3]);
                        $bool = Db::name('config')->update(['id'=>$id[$k3],'value'=>trim($v3)]);
                    }
                }
                
                Db::commit();//提交事务
            } catch (\Exception $e) {
                Db::rollback();//事务回滚
            }
        }
        
        return $bool;
    }

    #获取配置信息
    public function get_setting($type)
    {
        $result = Db::name('config')->where('type',$type)->field('id,name,value')->select();
        return $result;
    }

    #处理配置信息
    public function handle_setting($data)
    {
        $result = array();
        if (is_array($data)) {
            foreach($data as $key => $value){
                $result[$value['name']] = $value['value'];
            }
        }
        
        return $result;
    }

    # 移动图片
    public function move_img($img,$name='')
    {
        $img_path = '';
        $path = ROOT_PATH . 'public/images/setting/';
        $dir = 'temp/';

        if (is_file($path.$dir.$img)) {
            $image = \think\Image::open($path.$dir.$img);
            $bool = $image->save($path.$name.'.jpg');
            if($bool){
                $img_path = '/public/images/setting/'.$name.'.jpg';
            }
        }

        return $img_path;
    }

    # 删除图片
    public function del_img()
    {
        $data = input('post.');
        $path = 'public/images/setting/';
        $is_del = false;
        
        if (isset($data['type'])) {
            $info = $this->get_setting($data['type']);
            $result = $this->handle_setting($info);
            
            if (isset($data['img']) && $data['img']) {
                $file_path = ROOT_PATH . $path . $data['img'];
                if (isset($data['name']) && (isset($result[$data['name']]))){
                    if ($data['img'] == $result[$data['name']]) {
                        $name = $data['name'];
                        $bool = Db::name('config')->where('name',$name)->where('type',$data['type'])->update(['value'=>""]);
                        $file_path = $bool ? ROOT_PATH . $result[$data['name']] : '';
                    }
                }
                
                if (is_file($file_path)) {
                    $is_del = @unlink($file_path);
                }
            }
        }

        $code = $is_del ? ['code' => 1] : ['code' => 0];

        return json($code);
    }

    # 邮箱配置
    public function smtp()
    {
        if($_POST){
            
            $data = $_POST;
            $type = $data['type'];
            unset($data['type']);
            foreach($data as $k=>$v){
                if(Db::name('config')->field('id')->where(['type'=>$type,'name'=>$k])->find()){

                    Db::name('config')->where(['type'=>$type,'name'=>$k])->update(['value'=>$v]);
                }else{
                    Db::name('config')->insert(['name'=>$k,'value'=>$v,'type'=>$type]);
                }
            }
            echo "<script>parent.success('操作成功！');</script>";

            exit;
        }


        $info = Db::name('config')->where('type','email_setting')->select();
        if($info){
            foreach($info as $v){
                $data[$v['name']] = $v['value']; 
            }
            $info = $data;
        }
        $this->assign('info', $info);
        $this->assign('type', 'email_setting');
        $this->assign('index',1);
        return $this->fetch();
    }

    # 微信设置
    public function weixin(){

        if($_POST){
            
            $data = $_POST;
            $type = $data['type'];
            unset($data['type']);
            foreach($data as $k=>$v){
                if(Db::name('config')->field('id')->where(['type'=>$type,'name'=>$k])->find()){

                    Db::name('config')->where(['type'=>$type,'name'=>$k])->update(['value'=>$v]);
                }else{
                    Db::name('config')->insert(['name'=>$k,'value'=>$v,'type'=>$type]);
                }
            }
            echo "<script>parent.success('操作成功！');</script>";

            exit;
        }
        $info = Db::name('config')->where('type','weixin_config')->select();
        if($info){
            foreach($info as $v){
                $data[$v['name']] = $v['value']; 
            }
            $info = $data;
        }
        $this->assign('info', $info);
        $this->assign('type', 'weixin_config');
        $this->assign('index',2);
        return $this->fetch();
    }

    # 资金设置
    public function cash(){

        if($_POST){
            
            $data = $_POST;
            $type = $data['type'];
            unset($data['type']);
            foreach($data as $k=>$v){
                $v = Digital_Verification($v) ? Digital_Verification($v) : 0;
                if(Db::name('config')->field('id')->where(['type'=>$type,'name'=>$k])->find()){

                    Db::name('config')->where(['type'=>$type,'name'=>$k])->update(['value'=>$v]);
                }else{
                    Db::name('config')->insert(['name'=>$k,'value'=>$v,'type'=>$type]);
                }
            }
            echo "<script>parent.success('操作成功！');</script>";

            exit;
        }

        $info = Db::name('config')->where('type','cash_setting')->select();
        if($info){
            foreach($info as $v){
                $data[$v['name']] = $v['value']; 
            }
            $info = $data;
        }


        $this->assign('info', $info);
        $this->assign('type', 'cash_setting');
        $this->assign('index',3);
        return $this->fetch();
    }


    # 菜单管理
    public function menu(){


        $keywords = isset($_GET['keywords']) ? trim($_GET['keywords']) : '';

        $where['id'] = ['>',0];
        if($keywords){
            $where['name'] = ['like', "%$keywords%"];
        }
        
        $list = Db::name('menu')->where( $where )->select();
        
        if($list){
            $result = array();
            foreach($list as $k1 => $v1){
                if ($v1['parent_id'] == 0) {
                    array_push($result,$list[$k1]);
                    unset($list[$k1]);
                    if(is_array($list)){
                        foreach($list as $k2 => $v2){
                            if($v2['parent_id'] == $v1['id']){
                                array_push($result,$list[$k2]);
                                unset($list[$k2]);
                            }
                        }
                    }
                }
            }
            
            $this->assign('list', $result);
        }

        $count = Db::name('menu')->where( $where )->count();
        $this->assign('count', $count);
        $this->assign('keywords', $keywords);
        return $this->fetch();
    }

    # 菜单状态修改
    public function menu_islock(){
        if($_POST){
            $is_lock = isset($_POST['is_lock']) ? intval($_POST['is_lock']) : 0;
            $menu_id = isset($_POST['menu_id']) ? intval($_POST['menu_id']) : 0;

            if($menu_id > 0){
                $res = Db::execute("update `zf_menu` set `is_lock` = '$is_lock' where `id` = '$menu_id'");
                if($res){
                    return json_encode(['status' => 1]);
                }
            }
            return json_encode(['status' => 0]);
            
        }
        exit;
    }

    # 删除菜单
    public function del_menu(){
        if($_POST){
            $menu_id = isset($_POST['menu_id']) ? intval($_POST['menu_id']) : 0;
            if($menu_id > 0){
                $res = Db::execute("delete from `zf_menu` where `id` = '$menu_id' or `parent_id` = '$menu_id'");
                if($res){
                    return json_encode(['status' => 1]);
                }
            }
            return json_encode(['status' => 0]);
        }
        exit;
    }


    # 添加菜单
    public function add_menu(){

        if($_POST){
            $menu_id = isset($_POST['menu_id']) ? intval($_POST['menu_id']) : 0;
            $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $icon = isset($_POST['icon']) ? trim($_POST['icon']) : '';
            $url = isset($_POST['url']) ? trim($_POST['url']) : '';
            $is_lock = isset($_POST['is_lock']) ? intval($_POST['is_lock']) : 0;


            $level = $parent_id > 0 ? 2 : 1;
            $time = time();

            if(!$name){
                return json_encode(['status'=>0,'msg'=>'请填写菜单名称']);
                exit;
            }
            if($parent_id > 0 && !$url){
                return json_encode(['status'=>0,'msg'=>'请填写菜单地址']);
                exit;
            }

            if($menu_id > 0){
                $res = Db::execute("update `zf_menu` set `parent_id` = '$parent_id', `name` = '$name', `icon` = '$icon', `url` = '$url', `is_lock` = '$is_lock', `level` = '$level' where `id` = '$menu_id'");
                
            }else{
                $res = Db::execute("insert into `zf_menu` (`name`,`url`,`icon`,`is_lock`,`addtime`,`level`,`parent_id`) values ('$name','$url','$icon','$is_lock','$time','$level','$parent_id')");
            }
            if($res){
                return json_encode(['status'=>1,'msg'=>'操作成功']);
            }else{
                return json_encode(['status'=>0,'msg'=>'操作失败']);
            }
            exit;
        }

        $menu_id = isset($_GET['menu_id']) ? intval($_GET['menu_id']) : 0;
        if($menu_id){
            $info = Db::name('menu')->where('id',$menu_id)->find();
            $this->assign('info',$info);
        }

        $menu[0] = ['id'=>0, 'name' => '顶级菜单'];
        $res = Db::query("select `id`,`name` from `zf_menu` where `level` = 1 and `id` != '$menu_id' order by `id` asc");
        if($res){
            foreach($res as $v){
                $menu[$v['id']] = $v;
            }
        }

        $this->assign('menu',$menu);
        return $this->fetch();
    }

    # 三级联动
    public function open_area_select(){
        $province = Db::name('area')->field('`id`,`name`')->where('parent_id',0)->select();
        
        $this->assign('province', $province);
        return $this->fetch();
    }

    # 三级联动数据返回
    public function ajax_area(){
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
        $html = '';
        if($parent_id > 0){
            $list = Db::name('area')->field('`id`,`name`')->where('parent_id',$parent_id)->select();
            if (isset($list)) {
                foreach ($list as $v) {
                    $html .= '<option value="' . $v['id'] . '">' . $v['name'] . '</option>';
                }
            }
        }else{
            // $list = Db::name('area')->field('`id`,`name`')->where('parent_id',0)->select();
            $html = '';
        }
        

        return json($html);
        exit;
    }


     # 分类三级联动
     public function open_category_select(){
        $type = isset($_GET['type']) ? trim($_GET['type']) : '';
        $where['parent_id'] = ['=', 0];
        $where['is_lock'] = ['=', 0];
        $where['type'] = ['=', $type];
        $cate = Db::name('category')->field('`id`,`name`')->where($where)->select();
        
        $this->assign('cate', $cate);
        return $this->fetch();
    }

    # 分类三级联动数据返回
    public function ajax_category(){
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
        $html = '';
        $where['is_lock'] = ['=', 0];
        $where['parent_id'] = ['=', $parent_id];
            
        $list = Db::name('category')->field('`id`,`name`')->where($where)->select();
            
        if(isset($list)){
            foreach($list as $v){
                $html .= '<option value="'.$v['id'].'">'.$v['name'].'</option>';
            }
        }

        return json($html);
        exit;
    }


    # 商品图片上传
    public function upload_images(){
        if(isset($_FILES['image'])){
            $module = isset($_POST['module']) ? trim($_POST['module']) : '';
            if(!$module){
                echo "<script>parent.iframe_images_callback(0,'')</script>";
                exit;
            }
            $file = request()->file('image');
            $files_dir = ROOT_PATH . 'public/images/'.$module.'/temp';
            
            $info = $file->move($files_dir);
            if($info){
                $src_dir = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].'/public/images/'.$module.'/temp/';
                $savename = str_replace('\\','/',$info->getSaveName());
                $src_dir .= $savename;
                echo "<script>parent.iframe_images_callback(1,' $src_dir','$savename')</script>";
            }else{
                echo "<script>parent.iframe_images_callback(0,'')</script>";
            }
        }
        exit;
    }

}
