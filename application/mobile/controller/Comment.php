<?php

namespace app\mobile\controller;

use think\Db;
use think\Session;

class Comment extends Base
{
    public $user_id = 0;

    public function _initialize(){
        parent::_initialize();
        
        # 验证登录
        $this->Verification_User();
        $this->user_id = session('user.id');
    }

    # 评论
    public function comment()
    {
        $id = input('id/d');
        
        if(!$id){
            layer_error('参数错误！');
        }
        
        $this->assign('id',$id);
        return $this->fetch();
    }

    # 获取评论
    public function get_comment()
    {
        $data = input('get.');
        $page = intval($data['page']);
        $count = intval($data['count']);
        $add_count = 5;
        $list = Db::name('comment')->where(['to'=>$data['id'],'type'=>$data['type'],'status'=>1])->order('id','desc')->page($page,($count + $add_count))->select();
        
        if ($list) {
            $result['lists'] = $this->comment_list($list);
            $result['page'] = $page + 1;
            $result['count'] = $count + $add_count;
        } else {
            $result['page'] = 1;
            $result['count'] = $count;
            $list = Db::name('comment')->where(['to'=>$data['id'],'type'=>$data['type'],'status'=>1])->order('id','desc')->select();
            $result['lists'] = $this->comment_list($list);
        }
        
        return json($result);
    }

    public function comment_list($data)
    {
        if ($data) {
            sort($data);
            $id = array_column($data,'user_id');
            $user = Db::name('users')->field('id,avatar,nickname')->select($id);
            
            foreach ($data as $k1 => $v1) {
                $data[$k1]['avatar'] = '';
                $data[$k1]['nickname'] = '';
                $data[$k1]['del'] = '';
                foreach ($user as $k2 => $v2) {
                    if ($v1['user_id'] == $v2['id']) {
                        $data[$k1]['avatar'] = $v2['avatar'];
                        $data[$k1]['nickname'] = $v2['nickname'];
                    }
                }
            }
        }
        return $data;
    }

    # 处理评论
    public function handle_comment()
    {
        $id = input('id/d');
        $comment = input('comment/s');
        $type = input('type/d');
        $comment = trim($comment);
        $user_id = $this->user_id;
        
        $result['code'] = 0;
        if (($id > 0) && ($user_id > 0) && $comment) {
            $bool = Db::name('comment')->insert(['user_id'=>$user_id,'to'=>$id,'content'=>$comment,'status'=>0,'type'=>$type,'addtime'=>time(),'utime'=>time()]);
            $result['code'] = $bool ? 1 : 0;
        }
        return json($result);
    }
}