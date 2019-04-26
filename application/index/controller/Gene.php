<?php
namespace app\index\controller;

use think\Controller;
use think\Db;
use think\Session;

class Gene extends Base
{
    public function _initialize()
    {
        parent::_initialize();
        Session::set('re_url',"/index/$this->controller/$this->action");
        $this->Verification_User();
    }

    # 我的基因数据
    public function index()
    {

        $list = Db::name('gene')->field('id,name')->where('user_id',$this->user_id)->select();

        $this->assign('list', $list);
        return $this->fetch();
    }

    # 基因数据详情
    public function info(){
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        $info = Db::name('gene')->where(['id'=>$id, 'user_id'=>$this->user_id])->find();
        if(!$info){
            layer_error('查询信息不存在！');
            exit;
        }

        $this->assign('name',$info['name']);
        $completion = $info['completion'] ? json_decode($info['completion'],true) : '';
        unset($info['id'],$info['user_id'],$info['name'],$info['desc'],$info['completion'],$info['addtime'],$info['utime']);
        if($completion){
            foreach($completion as $k => $v){
                $info[$k] = $v;
            }
        }

        $this->assign('list',$info);
        return $this->fetch();
    }
}