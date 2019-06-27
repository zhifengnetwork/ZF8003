<?php

namespace app\admin\controller;
use think\Db;

class Distribution extends Base
{

    #分销设置
    public function setting(){
        
        if(request()->isPost()){
            $jur = $this->check_jurisdiction_ok('w','distribution/setting');
            if(!$jur){
                echo "<script>parent.layermsg('访问权限受控，您无权操作此项！至少拥有‘操作’的权限',5)</script>";
                exit;
            }

            $data = input('post.');
            $arr = [];
            foreach($data['id'] as $key=>$value){
                if($value){
                    Db::name('user_level')->where('id',$key)->update(['upgrade'=>$data['upgrade'][$key],'quota'=>$data['quota'][$key],'quota_min'=>$data['quota_min'][$key],'quota_max'=>$data['quota_max'][$key]]);
                }else{
                    Db::name('user_level')->insert(['id'=>$key,'upgrade'=>$data['upgrade'][$key],'quota'=>$data['quota'][$key],'quota_min'=>$data['quota_min'][$key],'quota_max'=>$data['quota_max'][$key]]);
                }
            }

            $d['status'] = isset($_POST['status']) ? intval($_POST['status']) : 0;
            $d['distr_time'] = isset($_POST['distr_time']) ? intval($_POST['distr_time']) : 0;

            foreach($d as $k=>$v){
                if(Db::name('config')->where(['type'=>'distribution_setting', 'name'=>$k])->count()){
                    Db::name('config')->where(['type'=>'distribution_setting', 'name'=>$k])->update(['value' => $v]);
                }else{
                    Db::name('config')->insert(['name'=>$k,'value'=>$v,'type'=>'distribution_setting']);
                }
            }

            $this->success('修改成功！');
        }

        
        $jur = $this->check_jurisdiction_ok('r','distribution/setting');
        if(!$jur){
            error_h1('访问权限受控，您无权操作此项！','至少拥有‘查看’的权限');
        }

        $config = Db::name('config')->where('type','distribution_setting')->field('name,value')->select();
        $info = [];
        if($config){
            foreach($config as $v){
                $info[$v['name']] = $v['value'];
            }
        }

        $user_level = Db::name('user_level')->order('id ASC')->select();

        return $this->fetch('',[
            'info'  =>  $info,
            'user_level'    =>  $user_level,
        ]);

    }

    # 分享设置
    public function setting1(){


        if($_POST){
            $jur = $this->check_jurisdiction_ok('w','distribution/setting');
            if(!$jur){
                echo "<script>parent.layermsg('访问权限受控，您无权操作此项！至少拥有‘操作’的权限',5)</script>";
                exit;
            }

            $d['status'] = isset($_POST['status']) ? intval($_POST['status']) : 0;

            $d['distr_time'] = isset($_POST['distr_time']) ? intval($_POST['distr_time']) : 0;

            $d['one_quota'] = isset($_POST['one_quota']) && Digital_Verification($_POST['one_quota']) ? Digital_Verification($_POST['one_quota']) : 0;

            $d['one_quota_min'] = isset($_POST['one_quota_min']) && Digital_Verification($_POST['one_quota_min']) ? Digital_Verification($_POST['one_quota_min']) : 0;

            $d['one_quota_max'] = isset($_POST['one_quota_max']) && Digital_Verification($_POST['one_quota_max']) ? Digital_Verification($_POST['one_quota_max']) : 0;

            $d['two_quota'] = isset($_POST['two_quota']) && Digital_Verification($_POST['two_quota']) ? Digital_Verification($_POST['two_quota']) : 0;

            $d['two_quota_min'] = isset($_POST['two_quota_min']) && Digital_Verification($_POST['two_quota_min']) ? Digital_Verification($_POST['two_quota_min']) : 0;

            $d['two_quota_max'] = isset($_POST['two_quota_max']) && Digital_Verification($_POST['two_quota_max']) ? Digital_Verification($_POST['two_quota_max']) : 0;

            if($d['status'] == 1){
                $d['status'] = time();
            }

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

        $jur = $this->check_jurisdiction_ok('r','distribution/setting');
        if(!$jur){
            error_h1('访问权限受控，您无权操作此项！','至少拥有‘查看’的权限');
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
        $poster_path = '/public/shareposter/temp/poster_image.png?t='.time();

        if($_POST){
            $jur = $this->check_jurisdiction_ok('w','distribution/shareposter');
            if(!$jur){
                echo "<script>parent.layer_error_msg('访问权限受控，您无权操作此项！至少拥有‘操作’的权限')</script>";
                exit;
            }
            $data = input('post.');
            $val['w'] = intval($data['w']) ? intval($data['w']) : 75;
            $val['h'] = intval($data['h']) ? intval($data['h']) : 75;
            $val['x'] = intval($data['code_x']) ? intval($data['code_x']) : 0;
            $val['y'] = intval($data['code_y']) ? intval($data['code_y']) : 0;

            $val['name_x'] = intval($data['name_x']) ? intval($data['name_x']) : 0;
            $val['name_y']= intval($data['name_y']) ? intval($data['name_y']) : 0;
            $val['name_font_size'] = intval($data['name_font_size']) ? intval($data['name_font_size']) : 20;
            $val['name_font_color'] = $data['name_font_color'] ? $data['name_font_color'] : '#fff';

            $val['title'] = $data['title'] ? $data['title'] : '';
            $val['title_x'] = intval($data['title_x']) ? intval($data['title_x']) : 0;
            $val['title_y'] = intval($data['title_y']) ? intval($data['title_y']) : 0;
            $val['title_font_size'] = intval($data['title_font_size']) ? intval($data['title_font_size']) : 20;
            $val['title_font_color'] = $data['title_font_color'] ? $data['title_font_color'] : '#fff';
            
            $value = json_encode($val);

            # 移动背景图片
            // $re = rename($image_path,$load_dir.'qr_backgroup.png');
            $qr_image = \think\Image::open($image_path);
            if(file_exists($load_dir.'qr_backgroup.png')){
                @unlink($load_dir.'qr_backgroup.png');
            }
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

        $jur = $this->check_jurisdiction_ok('r','distribution/shareposter');
        if(!$jur){
            error_h1('访问权限受控，您无权操作此项！','至少拥有‘查看’的权限');
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
        # 默认字体
        $ttc = ROOT_PATH.'public/simsun.ttc';

        if($_POST){
            
            $t = input('post.t');
            if($t == 'temp_image'){
                $jur = $this->check_jurisdiction_ok('w','distribution/shareposter');
                if(!$jur){
                    echo '<script>window.parent.temp_msg("访问权限受控，您无权操作此项！至少拥有‘操作’的权限")</script>';
                    exit;
                }
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
                $jur = $this->check_jurisdiction_ok('w','distribution/shareposter');
                if(!$jur){
                    return json(['status' => 0, 'msg' => '访问权限受控，您无权操作此项！至少拥有‘操作’的权限']);
                }
                $data = input('post.');
                $image_w = intval($data['w']) ? intval($data['w']) : 75;
                $image_h = intval($data['h']) ? intval($data['h']) : 75;
                $image_x = intval($data['x']) ? intval($data['x']) : 0;
                $image_y = intval($data['y']) ? intval($data['y']) : 0;

                $name = '我是：MyRoot';
                $name_x = intval($data['name_x']) ? intval($data['name_x']) : 0;
                $name_y = intval($data['name_y']) ? intval($data['name_y']) : 0;
                $name_font_size = intval($data['name_font_size']) ? intval($data['name_font_size']) : 20;
                $name_font_color = $data['name_font_color'] ? $data['name_font_color'] : '#fff';

                $title = $data['title'] ? $data['title'] : '';
                $title_x = intval($data['title_x']) ? intval($data['title_x']) : 0;
                $title_y = intval($data['title_y']) ? intval($data['title_y']) : 0;
                $title_font_size = intval($data['title_font_size']) ? intval($data['title_font_size']) : 20;
                $title_font_color = $data['title_font_color'] ? $data['title_font_color'] : '#fff';
                

                
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

                # 名字 字体合成
                if($name_x > 0 || $name_y > 0){
                    $name_water = [$name_x, $name_y];
                }else{
                    $name_water = 5;
                }
                $image = \think\Image::open($poster_path);
                $image->text($name,$ttc,$name_font_size,$name_font_color,$name_water)->save($poster_path);


                # 标语 字体合成
                if($title){
                    if($title_x > 0 || $title_y > 0){
                        $title_water = [$title_x, $title_y];
                    }else{
                        $title_water = 5;
                    }
                    $image = \think\Image::open($poster_path);
                    $image->text($title,$ttc,$title_font_size,$title_font_color,$title_water)->save($poster_path);
                }



                return json(['status' => 1, 'msg' => '操作成功', 'time' => time()]);
            }

        }
    }

    # 推广记录
    public function extension(){
        $jur = $this->check_jurisdiction_ok('r','distribution/extension');
        if(!$jur){
            error_h1('访问权限受控，您无权操作此项！','至少拥有‘查看’的权限');
        }

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
            foreach($list as $v1){
                $ids[$v1['user_id']] = $v1['user_id'];
                $ids[$v1['add_user_id']] = $v1['add_user_id'];
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

        // dump($list);
        // dump($user_info);
        // die;
        $this->assign('search', $search);
        $this->assign('user_info', $user_info);
        $this->assign('type_name', $type_name);
        $this->assign('list', $list);
        $this->assign('count', $count);
        return $this->fetch();
    }

    # 佣金记录
    public function commission(){
        $jur = $this->check_jurisdiction_ok('r','distribution/commission');
        if(!$jur){
            error_h1('访问权限受控，您无权操作此项！','至少拥有‘查看’的权限');
        }
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
                $ids[$v['source_user_id']] = $v['source_user_id'];
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

        // pred($list);
        $this->assign('search', $search);
        $this->assign('user_info', $user_info);
        $this->assign('source_name', $source_name);
        $this->assign('list', $list);
        $this->assign('count', $count);
        return $this->fetch();
    }



}