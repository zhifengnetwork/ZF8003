<?php
/**
 * 系统设置
 */

namespace app\admin\Controller;
use think\Db;

class System extends Base
{


    # 菜单管理
    public function menu(){

        $where['id'] = ['>',0];
        
        $list = Db::name('menu')->where( $where )->paginate(2);
        if($list){
            $pname[0] = '顶级菜单';

            foreach($list as $v){
                if($v['parent_id'] > 0) $pid[] = $v['parent_id'];
            }
            if(isset($pid)){
                $pid = implode("','", $pid);
                $plist = Db::query("select `id`,`name` from `zf_menu` where `id` in ('$pid')");
                
                foreach($plist as $v){
                    $pname[$v['id']] = $v['name'];
                }
            }

            $this->assign('pname', $pname);
            $this->assign('list', $list);
        }

        $count = Db::name('menu')->where( $where )->count();
        $this->assign('count', $count);
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




}
