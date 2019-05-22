<?php
# 微信公众号接入
namespace app\admin\controller;

use think\Db;
use think\Session;

class Weixin extends Base
{

    # 自定义菜单
    public function custom_menu(){

        $list = '';
        $lists  = Db::name('wx_menu')->select();
        if($lists){
            foreach($lists as $k=>$v){
                if($v['level'] == 1){
                    $list[] = $v;
                    unset($lists[$k]);
                    foreach($lists as $k1 => $v1){
                        if($v1['pid'] == $v['id']){
                            $list[] = $v1;
                            unset($lists[$k1]);
                        }
                    }
                }
            }
        }

        $this->assign('list', $list);
        return $this->fetch();
    }
    # 新增或编辑自定义菜单
    public function edit_custom_menu(){
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if($_POST){
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $pid = isset($_POST['pid']) ? intval($_POST['pid']) : 0;
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $url = isset($_POST['url']) ? trim($_POST['url']) : '';

            $level = 1;
            if($pid){
                $level++;
            }

            if(!$name){
                return json(['status'=>0,'msg'=>'请输入菜单名']);
            }
            if(!$url){
                return json(['status'=>0,'msg'=>'请输入菜单链接']);
            }

            if($pid && $id){
                $last = Db::name('wx_menu')->where('pid',$id)->count();
                if($last){
                    return json(['status'=>0,'msg'=>'菜单下拥有子菜单，不允许变更菜单级别']);
                }
            }
            
            $pcount = Db::name('wx_menu')->where('pid', $pid)->count();
            if($level == 1 && $pcount >= 3){
                return json(['status'=>0,'msg'=>'最多只能添加3个顶级菜单']);
            }
            if($level == 2 && $pcount >= 5){
                return json(['status'=>0,'msg'=>'每个顶级菜单最多拥有5个子菜单']);
            }

            
            $info = Db::name('wx_menu')->find($id);
            if($info){
                $res = Db::name('wx_menu')->where('id', $id)->update(['name'=>$name,'value'=>$url,'pid'=>$pid,'level'=>$level]);
            }else{
                $res = Db::name('wx_menu')->insert(['pid'=>$pid,'name'=>$name,'value'=>$url,'level'=>$level,'type'=>'view']);
            }
            if($res){
                return json(['status'=>1,'msg'=>'操作成功']);
            }
            return json(['status'=>0,'msg'=>'操作失败，请重试！']);
        }
        $cate = Db::name('wx_menu')->where(['id'=>['<>', $id], 'level' => ['=', 1]])->field('id,name')->order('sort desc')->select();
        $info = Db::name('wx_menu')->find($id);

        $this->assign('id', $id);
        $this->assign('info', $info);
        $this->assign('cate', $cate);
        return $this->fetch();
    }



    # 自定义菜单
    public function menu()
    {
        
        



        return $this->fetch();
    }

    # 自定义菜单查询
    public function menu_query(){
        $this->get_weixin_global_token();
        $url ="https://api.weixin.qq.com/cgi-bin/menu/get?access_token=".$this->weixin_config['weixin_access_token'];
        $res = httpRequest($url,'GET');
        $res = json_decode($res,true);
        dump($res);exit;
    }

    # 创建自定义菜单
    public function create_menu()
    {
        exit;
        $create = [
            'button' => [
                [
                    'type' => 'view',
                    'name' => '再来一个',
                    'url' => 'http://zf8003.zhifengwangluo.c3w.cc/index',
                ],
            ],
        ];

        $this->get_weixin_global_token();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$this->weixin_config['weixin_access_token'];
        $res = httpRequest($url,'POST',json_encode($create, JSON_UNESCAPED_UNICODE));
        $res = json_decode($res,true);
        dump($res);exit;
    }

    # 自动回复
    public function automatic()
    {

        
        
        return $this->fetch();
    }


    # 素材管理
    public function material(){



        $where['id'] = ['>', 0];

        $list = Db::name('wx_material')->field('id,type,title,author,show_cover_pic,content_source_url,need_open_comment,only_fans_can_comment,addtime,utime')->where($where)->order('utime desc')->paginate(15);
        $count = Db::name('wx_material')->where($where)->count();


        $this->assign('count', $count);
        $this->assign('list', $list);


        return $this->fetch();
    }

    # 添加或编辑素材
    public function edit_material(){

        $id = isset($_POS['id']) ? intval($_POST['id']) : 0;




        return $this->fetch();
    }
}