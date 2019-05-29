<?php

namespace app\admin\controller;
use think\Db;

class Distribution extends Base
{

    # 分享设置
    public function setting(){


        if($_POST){

            $d['status'] = isset($_POST['status']) ? intval($_POST['status']) : 0;

            $d['distr_time'] = isset($_POST['distr_time']) ? intval($_POST['distr_time']) : 0;

            $d['one_quota'] = isset($_POST['one_quota']) && Digital_Verification($_POST['one_quota']) ? Digital_Verification($_POST['one_quota']) : 0;

            $d['one_quota_min'] = isset($_POST['one_quota_min']) && Digital_Verification($_POST['one_quota_min']) ? Digital_Verification($_POST['one_quota_min']) : 0;

            $d['one_quota_max'] = isset($_POST['one_quota_max']) && Digital_Verification($_POST['one_quota_max']) ? Digital_Verification($_POST['one_quota_max']) : 0;

            $d['two_quota'] = isset($_POST['two_quota']) && Digital_Verification($_POST['two_quota']) ? Digital_Verification($_POST['two_quota']) : 0;

            $d['two_quota_min'] = isset($_POST['two_quota_min']) && Digital_Verification($_POST['two_quota_min']) ? Digital_Verification($_POST['two_quota_min']) : 0;

            $d['two_quota_max'] = isset($_POST['two_quota_max']) && Digital_Verification($_POST['two_quota_max']) ? Digital_Verification($_POST['two_quota_max']) : 0;


            foreach($d as $k=>$v){
                if(Db::name('config')->where(['type'=>'distribution_setting', 'name'=>$k])->count()){
                    Db::name('config')->where(['type'=>'distribution_setting', 'name'=>$k])->update(['value' => $v]);
                }else{
                    Db::name('config')->insert(['name'=>$k,'value'=>$v,'type'=>'distribution_setting']);
                }
            }

            echo "<script>parent.layermsg('操作成功！正在刷新.....')</script>";
            exit;
        }


        $config = Db::name('config')->where('type','distribution_setting')->field('name,value')->select();
        $info = [];
        if($config){
            foreach($config as $v){
                $info[$v['name']] = $v['value'];
            }
        }
        
        $this->assign('info', $info);
        return $this->fetch();
    }


    # 分享海报
    public function shareposter(){
        # 缓存文件夹
        $temp_dir = ROOT_PATH.'public/shareposter/temp/';
        # 配置文件夹
        $load_dir = ROOT_PATH.'public/shareposter/load/';
        # 会员文件夹
        $user_dir = ROOT_PATH.'public/shareposter/user/';
        # 默认配置二维码地址
        $qrcode_path =  $load_dir.'qrcode.png';
        # 默认缓存二维码地址
        $qrcode_temp_path =  $temp_dir.'qrcode.png';
        # 默认底部图片地址
        $image_path =  $temp_dir.'qr_backgroup.png';
        # 默认海报地址
        $poster_path = '/public/shareposter/temp/poster_image.png';

        if($_POST){
            $data = input('post.');
            $val['w'] = $data['w'] ? $data['w'] : 75;
            $val['h'] = $data['h'] ? $data['h'] : 75;
            $val['x'] = $data['code_x'] ? $data['code_x'] : 0;
            $val['y'] = $data['code_y'] ? $data['code_y'] : 0;
            
            $value = json_encode($val);

            # 移动背景图片
            // $re = rename($image_path,$load_dir.'qr_backgroup.png');
            $qr_image = \think\Image::open($image_path);
            $qr_image->save($load_dir.'qr_backgroup.png');
            

            # 删除会员文件夹下的文件
            delFileUnderDir($user_dir);


            $conf = Db::name('config')->where(['type' => 'distribution_shareposter', 'name' => 'shareposter'])->find();
            if($conf){
                Db::name('config')->where(['type' => 'distribution_shareposter', 'name' => 'shareposter'])->update(['value' => $value]);
            }else{
                Db::name('config')->insert(['name' => 'shareposter', 'value' => $value, 'type' => 'distribution_shareposter']);
            }

            echo "<script>parent.layer_success_msg('分享海报保存成功！')</script>";
            exit;
        }

        $conf = Db::name('config')->where(['type' => 'distribution_shareposter', 'name' => 'shareposter'])->find();
        $config = [];
        if($conf){
            $config = json_decode($conf['value'],true);
        }

        $this->assign('poster_path', $poster_path);
        $this->assign('config',$config);
        return $this->fetch();
    }

    
    # 分享海报图片静默处理
    public function silence_image(){
        # 缓存文件夹
        $temp_dir = ROOT_PATH.'public/shareposter/temp/';
        # 配置文件夹
        $load_dir = ROOT_PATH.'public/shareposter/load/';
        # 默认配置二维码地址
        $qrcode_path =  $load_dir.'qrcode.png';
        # 默认缓存二维码地址
        $qrcode_temp_path =  $temp_dir.'qrcode.png';
        # 默认底部图片地址
        $image_path =  $temp_dir.'qr_backgroup.png';
        # 默认海报地址
        $poster_path = $temp_dir.'poster_image.png';

        if($_POST){
            $t = input('post.t');
            if($t == 'temp_image'){
                $file = request()->file('image');
                if($file){
                    $info = $file->validate(['ext'=>'jpg,png,jpeg'])->move($temp_dir,'qr_backgroup.png');
                    if(!$info){
                        echo '<script>window.parent.temp_msg("背景图片上传失败，请重试!")</script>';
                    }
                }
                exit;
            }

            if($t == 'preview'){
                $data = input('post.');

                $image_w = $data['w'] ? $data['w'] : 75;
                $image_h = $data['h'] ? $data['h'] : 75;
                $image_x = $data['x'] ? $data['x'] : 0;
                $image_y = $data['y'] ? $data['y'] : 0;
                

                
                if(!file_exists($qrcode_path)){
                    return json(['status' => 0, 'msg' => '文件缺失，默认演示二维码不存在！']);
                }
                # 根据设置的尺寸，生成缓存二维码
                $qr_image = \think\Image::open($qrcode_path);
                $qr_image->thumb($image_w,$image_h,\think\Image::THUMB_SOUTHEAST)->save($qrcode_temp_path);
                
                if($image_x > 0 || $image_y > 0){
                    $water = [$image_x, $image_y];
                }else{
                    $water = 5;
                }
                

                if(!file_exists($image_path)){
                    return json(['status' => 0, 'msg' => '背景图片不存在，请先上传背景图片']);
                }

                # 图片合成
                $image = \think\Image::open($image_path);
                $image->water($qrcode_temp_path, $water)->save($poster_path);

                return json(['status' => 1, 'msg' => '操作成功', 'time' => time()]);
            }

        }
    }

    # 推广记录
    public function extension(){

        $search = isset($_GET['search']) ? $_GET['search'] : '';

        $where['id'] = ['>', 0];

        if($search){

            if($search['nickname']){
                $nickname = $search['nickname'];
                $search_id = Db::name('users')->where(['nickname' => ['like', "%$nickname%"]])->value('id');
                if($search_id){
                    $where['user_id|add_user_id'] = ['=', $search_id];
                }else{
                    $where['id'] = 0;
                }
            }

            if($search['email']){
                $email = $search['email'];
                $search_id = Db::name('users')->where(['email' => ['like', "%$email%"]])->value('id');
                if($search_id){
                    $where['user_id|add_user_id'] = ['=', $search_id];
                }else{
                    $where['id'] = 0;
                }
            }

            if($search['datemin'] || $search['datemax']){

                $datemin = $search['datemin'] ? strtotime($search['datemin']) : 0;
                $datemax = $search['datemax'] ? strtotime($search['datemax']) : 0;
                $where['addtime'] = [['>=',$datemin], ['<=',$datemax],'and'];
            }
            
        }
        
        $list = Db::name('extension_log')->where($where)->order('id desc')->paginate(15);
        $count = Db::name('extension_log')->where($where)->count();
        $user_info = '';
        if($list){
            $ids = [0];
            foreach($list as $v){
                $ids[$v['user_id']] = $v['user_id'];
                $ids[$v['add_user_id']] = $v['add_user_id'];
            }
            $ids = implode(',',$ids);
            $user_arr = Db::name('users')->field('id,nickname,avatar,email')->where(['id'=>['in',$ids]])->select();
            if($user_arr){
                foreach($user_arr as $v){
                    $user_info[$v['id']] = $v;
                }
            }
            
        }

        $type_name = [
            'share' => '分享海报',
        ];


        $this->assign('search', $search);
        $this->assign('user_info', $user_info);
        $this->assign('type_name', $type_name);
        $this->assign('list', $list);
        $this->assign('count', $count);
        return $this->fetch();
    }

    # 佣金记录
    public function commission(){



        $search = isset($_GET['search']) ? $_GET['search'] : '';

        $where['id'] = ['>', 0];

        if($search){

            if($search['nickname']){
                $nickname = $search['nickname'];
                $search_id = Db::name('users')->where(['nickname' => ['like', "%$nickname%"]])->value('id');
                if($search_id){
                    $where['user_id|source_user_id'] = ['=', $search_id];
                }else{
                    $where['id'] = 0;
                }
            }

            if($search['email']){
                $email = $search['email'];
                $search_id = Db::name('users')->where(['email' => ['like', "%$email%"]])->value('id');
                if($search_id){
                    $where['user_id|source_user_id'] = ['=', $search_id];
                }else{
                    $where['id'] = 0;
                }
            }

            if($search['datemin'] || $search['datemax']){

                $datemin = $search['datemin'] ? strtotime($search['datemin']) : 0;
                $datemax = $search['datemax'] ? strtotime($search['datemax']) : 0;
                $where['addtime'] = [['>=',$datemin], ['<=',$datemax],'and'];
            }
            
        }
        
        $list = Db::name('commission_log')->where($where)->order('id desc')->paginate(15);
        $count = Db::name('commission_log')->where($where)->count();
        $user_info = '';
        if($list){
            $ids = [0];
            foreach($list as $v){
                $ids[$v['user_id']] = $v['user_id'];
                $ids[$v['add_user_id']] = $v['add_user_id'];
            }
            $ids = implode(',',$ids);
            $user_arr = Db::name('users')->field('id,nickname,avatar,email')->where(['id'=>['in',$ids]])->select();
            if($user_arr){
                foreach($user_arr as $v){
                    $user_info[$v['id']] = $v;
                }
            }
            
        }

        $source_name = [
            'buy' => '购买商品',
        ];


        $this->assign('search', $search);
        $this->assign('user_info', $user_info);
        $this->assign('source_name', $source_name);
        $this->assign('list', $list);
        $this->assign('count', $count);
        return $this->fetch();
    }



}