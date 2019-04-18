<?php
namespace app\index\controller;
use think\Db;
use think\Session;

class User extends Base{


    public function _initialize(){
        parent::_initialize();

        $this->Verification_User();
    }

    public function index(){

        // dump($this->user);
        


        $coupon = Db::name('user_coupon')->where('user_id',$this->user_id)->count(); 
        $this->assign('coupon',$coupon);          
        $this->assign('info', $this->user);
        return $this->fetch();
    }

    public function repwd(){
       
        
        if($_POST){
            $password = pwd_encryption(input('post.password'));
            $user_id = $this->user_id;
            $res = Db::name('users')->where('id',$user_id)->update(['password' => $password]);
            if ($res) { 
                return json(['status'=>1,'msg'=>'修改成功']);
            }else{
                return json(['status'=>0,'msg'=>'修改失败']);
            }
        }

    }


}