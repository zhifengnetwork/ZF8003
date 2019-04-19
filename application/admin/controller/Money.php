<?php

namespace app\admin\controller;

use think\Db;

class Money extends Base
{
    public $admin_id = 0;

    public function _initialize()
    {
        parent::_initialize();
        $this->admin_id = session('admin_id');
        
    }

    /**
     * 提现审核列表
     */
    public function index()
    {
        $where['id'] = ['>', '0'];
        $keywords = isset($_GET['keywords']) ? trim($_GET['keywords']) : '';
        if($keywords){
            $where['title'] = ['like', "%$keywords%"];
        }
          
        $list = Db::name('withdrawal')->where($where)->order('status','asc')->paginate(15,false)->each(function($item, $key){
            $id = $item['user_id'];
            $result = Db::name('users')->where(['id'=>$id])->field('nickname,mobile')->find();
            $item['nickname'] = $result['nickname'] ? $result['nickname'] : $result['mobile'];
            $admin = Db::name('admin')->where(['id'=>$item['admin']])->field('name')->find();
            $item['admin'] = $admin['name'];
            return $item;
        });
        
        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * 审核
     */
    public function audit()
    {
        $id = input('id/d');
        $status = input('status/d');
        $notic = input('notic/s');
        $admin_id = $this->admin_id;
        $result['code'] = 0;
        
        if ($id && $admin_id) {
            $data = array('id'=>$id,'status'=>$status,'admin_note'=>$notic,'admin'=>$admin_id,'utime'=>time());
            
            $bool = Db::name('withdrawal')->update($data);

            $result['code'] = $bool ? 1 : 0;
        }

        return json($result);
    }
}