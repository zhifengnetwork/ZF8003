<?php
namespace app\index\controller;
use think\Db;
use think\Session;
use think\Loader;
class User extends Base{


    public function _initialize(){
        parent::_initialize();

        $this->Verification_User();
    }

    public function index(){

        
        $coupon = Db::name('user_coupon')->where('user_id',$this->user_id)->count();
        $avatar = Db::name('users')->where('id',$this->user_id)->value('avatar');
        $this->assign('avatar', $avatar);
        $this->assign('coupon',$coupon);          
        $this->assign('info', $this->user);
        return $this->fetch();
    }
    /**
     * 修改密码
     */
    public function repwd(){
         
        if($_POST){
            $data = input('post.');
            $adminValidate = Loader::Validate('User');
            if (!$adminValidate->check($data)) {
                $baocuo = $adminValidate->getError();
                return json(['status' => -1, 'msg' => $baocuo]);
            }
            $user_id = $this->user_id;
            $password = pwd_encryption($data['password']);
            $is_same = Db::name('users')->where('id', $user_id)->value('password');
            if($password == $is_same){
                return json(['status'=>0,'msg'=>'密码不可与原密码相同']);
            } 
            $res = Db::name('users')->where('id',$user_id)->update(['password' => $password]);
            if ($res) { 
                return json(['status'=>1,'msg'=>'修改成功']);
            }else{
                return json(['status'=>0,'msg'=>'修改失败']);
            }
        }

    }
    /**
     * 上传头像
     */
    public function upload(){
        $base64 = input('post.dataImg');
        $user_id = $this->user_id;
        $res = $this->uploadImg($base64,$user_id);
        return $res;      
    }

     /**
      * 处理base64
      */
    function uploadImg($base64,$user_id){
        header("content-type:text/html;charset=utf-8");
        $base64_image = str_replace(' ', '+', $base64);
        //post的数据里面，加号会被替换为空格，需要重新替换回来，如果不是post的数据，则注释掉这一行
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image, $result)){
            //匹配成功
            if($result[2] == 'jpeg'){
                $image_name = uniqid().'.jpg';
                //纯粹是看jpeg不爽才替换的
            }else{
                $image_name = uniqid().'.'.$result[2];
            }
            $image_file = "./uploads/".date('Ymd',time()).'/';
            if (!file_exists($image_file)) {

                mkdir($image_file,0755,true);
            }
            $image_url = "./uploads/".date('Ymd',time()).'/'."{$image_name}";
            $res_url = "/uploads/".date('Ymd',time()).'/'."{$image_name}";
            $res = Db::name('users')->where('id',$user_id)->update(['avatar' => $res_url]);
            $user = Db::name('users')->where('id', $user_id)->find();
            Session::set('user', $user);
            //服务器文件存储路径
            if ($res && file_put_contents($image_url, base64_decode(str_replace($result[1], '', $base64_image)))){
                return json(['code'=>200, 'msg'=>'上传成功', 'imgUrl'=>$res_url]);
            }else{
                return json(['code'=>0, 'msg'=>'上传失败']);

            }
        }else{
            return json(['code'=>0, 'msg'=>'上传失败..']);
        }
    }
    
}