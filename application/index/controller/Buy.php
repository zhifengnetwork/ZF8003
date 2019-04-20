<?php
namespace app\index\controller;
use think\Db;
use think\Session;

class Buy extends Base
{
    public function index()
    {
        return $this->redirect('index/buy/buy');
    }

    # 商品列表
    public function buy()
    {
        $where = [
            'is_del' => 0,
            'status' => 1
        ];
        $list = Db::name('goods')->where($where)->order('addtime desc')->paginate(16);
        $this->assign('list',$list);
        return $this->fetch();
    }

    # 商品详情
    public function details()
    { 
       
        $id = input('get.id');
        $info = Db::name('goods')->where('id', $id)->find();
        if(!$info){
            layer_error('商品信息不存在或已下架');
            exit;
        }
        
        if($info['status'] == 2){
            layer_error('商品信息不存在或已下架');
            exit;
        }
        $info['attr'] = json_decode($info['second_title'], true);
        $this->assign('list', $info);
        return $this->fetch();
    }


    # 提交订单
    public function submit_order()
    {
        $id = input('get.id');
        $info = Db::name('goods')->where('id', $id)->find();
        if(!$info){
            layer_error('商品信息不存在或已下架');
            exit;
        }

        $user = $this->user;
        if(!$user){
            Session::set('re_url', '/index/buy/submit_order?id='.$id);
            $this->redirect('/index/login/login');
        }

        # 地址 - 省
        $province = Db::name('area')->field('`id`,`name`')->where('parent_id', 0)->select();

        # 用户默认收货地址
        $default_address_id = $user['default_address_id'];
        if($default_address_id > 0){
            $default_address = Db::name('user_address')->where('id', $default_address_id)->find();
            if($default_address){
                $default_address['city_name'] = Db::name('area')->field('name')->where('id', $default_address['city'])->find()['name'];
                $default_address['district_name'] = Db::name('area')->field('name')->where('id', $default_address['district'])->find()['name'];
            }
        }

        $this->assign('province', $province);
        $this->assign('default_address', $default_address);
        $this->assign('info',$info); 
        return $this->fetch();
    }
}