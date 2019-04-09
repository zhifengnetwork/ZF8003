<?php
/**
 * 系统设置
 */

namespace app\admin\Controller;
use think\Db;

class System extends Base
{
    private $list;

    public function __construct(){
        parent::__construct();

        $this->list = array(
            'shop_info' => ['url'=>'setting','name'=>"商店信息"],
            'basic'     => ['url'=>'basic','name'=>"基本设置"],
            'smtp'      => ['url'=>'smtp','name'=>"邮箱设置"]
        );

        $this->assign('type', 'web_setting');
        $this->assign('list',$this->list);
    }

    public function index(){

    }

    # 商店设置
    public function setting()
    {
        $data = input('get.');
        if (isset($data['type'])) {
            dump($data);
        }
        $this->assign('url','setting');
        $this->assign('index',0);
        return $this->fetch();
    }

    #基本设置
    public function basic()
    {
        $this->assign('index',1);
        $this->assign('url','basic');
        return $this->fetch();
    }

    # 邮箱配置
    public function smtp()
    {
        $data = input('post.');
        $this->assign('url','smtp');
        $this->assign('index',2);
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
            
        }else{
            $list = Db::name('area')->field('`id`,`name`')->where('parent_id',0)->select();
            
        }
        
        if(isset($list)){
            foreach($list as $v){
                $html .= '<option value="'.$v['id'].'">'.$v['name'].'</option>';
            }
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
