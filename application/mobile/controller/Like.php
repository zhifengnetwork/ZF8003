<?php

namespace app\mobile\controller;

use think\Db;
use think\Session;

class Like extends Base
{
    public $user_id = 0;

    public function _initialize(){
        parent::_initialize();
        
        # 验证登录
        $this->Verification_User();
        $this->user_id = session('user.id');
    }

     # 点赞/取消点赞
     public function give_a_like()
     {
         $id = input('id/d');
         $user_id = $this->user_id;
         if (!$user_id) {
            $this->redirect('/mobile/index/login');
         }
         $return = ['code' => 0];
         if ($id && $user_id) {
             $where = array('user_id'=>$user_id,'article_id'=>$id);
             $is_like = Db::name('article_star')->where($where)->find();
             $count = 0;
             Db::startTrans();
             if ($is_like) {
                 $bool = Db::name('article_star')->where($where)->delete();
                 $count = -1;
             } else {
                 $where['addtime'] = time();
                 $bool = Db::name('article_star')->insertGetId($where);
                 $count = 1;
             }
 
             if ($bool) {
                 $is_update = Db::name('article')->where('id',$id)->setInc('star',$count);
                 if ($is_update) {
                     Db::commit();
                     $return['code'] = 1;
                 } else {
                     Db::rollback();
                 }
             }
         }
 
         return $return;
     }
 
     # 是否点赞
     public function is_like($id)
     {
         $like = Db::name('article_star')->where('user_id',$this->user_id)->where('article_id',$id)->find();
         $is_like = $like ? 1 : 0;
         return $is_like;
     }
}