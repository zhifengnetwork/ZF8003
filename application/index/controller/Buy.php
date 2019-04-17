<?php
namespace app\index\controller;
use think\Db;
class Buy extends Base
{
    public function index()
    {
        return $this->redirect('index/buy/buy');
    }

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

    public function details()
    { 
        // return json(['检验报告'=> '约200项', '基因位点'=> '2000万', '生成报告'=> '约2周']);exit;
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
}