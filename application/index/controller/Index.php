<?php
namespace app\index\controller;

use think\Controller;
use think\Db;
use think\Session;

class Index extends Base
{
    

    public function index()
    {

        return $this->fetch();
    }
    public function information()
    {

        return $this->fetch();
    }
    /**
     * 导入数据
     */
    public function import_data()
    {
        
        if(request()->isPost()){
            
            // $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            // $sex = isset($_POST['sex']) ? intval($_POST['sex']) : 0;
            // $year = isset($_POST['year']) ? intval($_POST['year']) : 0;
            // $month = isset($_POST['month']) ? intval($_POST['month']) : 0;
            // $day = isset($_POST['day']) ? intval($_POST['day']) : 0;
            $user_id = $this->user_id ? $this->user_id : 0;
            if(!$user_id){
                echo "<script>parent.layer.msg('请先登录！',{icon:5});</script>";die;
            }
            // if(!$name){
            //     echo "<script>parent.layer.msg('请输入姓名！',{icon:5});</script>";
            //     exit;
            // }
            // if( !preg_match("/^\W+$/",$name) ){
            //     echo "<script>parent.layer.msg('请输入正确的姓名！',{icon:5});</script>";
            //     exit;
            // }
            // if(!$day){
            //     echo "<script>parent.layer.msg('请选择出生日期',{icon:5});</script>";
            //     exit;
            // }
            $file = $file = request()->file('myfile');
            if(!$file){
                echo "<script>parent.layer.msg('请选择您要上传的检测报告',{icon:5});</script>";
                exit;
            }

            $filename = date('YmdHis',time()) .'_'. $user_id ;
            $dirpath = ROOT_PATH . 'public' . DS . 'gene' . DS . 'import';
            $info = $file->validate(['ext'=>'zip,rar,xls,xlsx'])->move($dirpath,$filename,false);
            if(!$info){
                echo "<script>parent.layer.msg('文件上传失败，请重试！',{icon:5});</script>";
                exit;
            }



            $savename = $info->getSaveName();
            
            if(strpos($savename, 'xls') || strpos($savename, 'xlsx')){
                $file_name = ROOT_PATH.'public/gene/import/'.$savename;
                vendor ('PHPExcel.PHPExcel');
                $objPHPExcel = new \PHPExcel();
                $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
                $obj_PHPExcel = $objReader->load($file_name, $encode = 'utf-8');
                $excel_array = $obj_PHPExcel->getsheet(0)->toArray(); //转换为数组格式

                $bitian = Standard_Gene_BiTian();
                $arr_name = [];
                $arr = [];
                foreach($excel_array as $key=>$value){
                    if($key==2){
                        foreach($value as $k=>$v){
                            if($k<1){continue;}
                            if($v){$arr_name[$k] = $v;}
                        }
                    }
                    if($key>2){
                        foreach($arr_name as $k=>$v){
                            $arr[$k]['name'] = $arr_name[$k];
                            if( in_array( strtolower($value[0]) , $bitian) && !$value[$k] ){
                                echo "<script>parent.error('红色字体的基因座为必填！');</script>";
                                exit;
                            }
                            $arr[$k][$value[0]] = $value[$k];
                        }
                    }
                }
                if(!$arr){
                    // $arr = array_values($arr);
                    echo "<script>parent.layer.msg('姓名不能为空！',{icon:5});</script>";die;
                }

                $res = Db::name('users')->update(['id'=>$user_id,'gene_file'=>$savename]);
                if($res){
                    echo "<script>parent.success();</script>";exit;
                }else{
                    echo "<script>parent.layer.msg('文件上传失败，请重试！',{icon:5});</script>";
                    exit;
                }

                

                // if($excel_array[0][0] == '结果报告' && $excel_array[3][0] == '29Y-STR基因座分型结果'){
                //     $e['user_id'] = $this->user_id ? $this->user_id : 0;
                //     $e['name'] = $name;
                //     $e['desc'] = isset($excel_array[1][0]) ? trim($excel_array[1][0]) : '';
                //     $e['nation'] = isset($excel_array[1][1]) ? trim($excel_array[1][1]) : '';
                //     $e['addtime'] = $e['utime'] = time();
                //     $completion = '';
                //     foreach($excel_array as $k=>$v){
                //         if($k > 4){
                //             if($v && isset($v[0]) && isset($v[1])){
                                
                //                 if(Standard_Gene($v[0])){
                //                     $e[strtolower($v[0])] = $v[1] ? (double)$v[1] * 100 : 0;
                //                 }else{
                //                     $completion[strtolower($v[0])] = $v[1] ? (double)$v[1] * 100 : 0;
                //                     $e['completion'] = json_encode($completion);
                //                 }
                //             }
                //         }
                //     }
                //     Db::name('gene')->insert($e);
                // }

                $n_arr = [
                    1 => 'name',
                    2 => 'nation',
                    3 => 'on_addr',
                    4 => 'addr_log',
                    5 => 'mobile',
                    6 => 'haplogroup',
                    7 => 'variation',
                    8 => 'nomutation',
                    9 => 'desc',
                ];
                
                if($excel_array){
                    foreach($excel_array as $k=>$v){
                        if($k > 0 && $k != 10){
                            foreach($v as $key=>$val){
                                $kk = isset($n_arr[$k]) ? $n_arr[$k] : $v[0];
                                if($v[0]){
                                    $d[$key][$kk] = $val ? $val : '';   
                                }
                            }
                        }
                    }
                    array_shift($d);
        
                    $res_success = 0;
                    $res_error = 0;
                    foreach($d as $k => $v){
                        if($v['name']){
                            $inar['user_id'] = $this->user_id ? $this->user_id : 0;
                            $inar['name'] = $inar['surname'] = $v['name'];
                            $inar['variation'] = $v['variation'];
                            $inar['nomutation'] = $v['nomutation'];
                            $inar['desc'] = '现居：'.$v['on_addr'].'，故居：'.$v['addr_log'].'，联系电话：'.$v['mobile'] .'，'. $v['desc'];
                            $inar['haplogroup'] = $v['haplogroup'];
                            $inar['nation'] = $v['nation'];
                            $inar['region'] = $v['on_addr'];
                            unset($v['name'],$v['variation'],$v['nomutation'],$v['on_addr'],$v['addr_log'],$v['mobile'],$v['desc'],$v['haplogroup'],$v['nation']);
        
                            $inar['addtime'] = $inar['utime'] = time();
                            foreach($v as $k1=>$v1){
                                if(Standard_Gene($k1)){
                                    $k1 = str_replace('-','_',$k1);
                                    $inar[strtolower($k1)] = $v1 ? (double)$v1 * 100 : 0;
                                }else{
                                    $inar['completion'][strtolower($k1)] = $v1 ? (double)$v1 * 100 : 0;
                                }
                            }
                            $inar['completion'] = isset($inar['completion']) ? json_encode($inar['completion']) : '';
                            
                            $res = Db::name('gene')->insert($inar);
                            if($res){
                                $res_success += 1;
                            }else{
                                $res_error += l;
                            }
                            $inar = null;
                            unset($d[$k]);
                        }
                    }
                }
            }
            
            $res = Db::name('import_gene')->insert([
                'user_id' => $user_id,
                'name' => $name,
                'sex' => $sex,
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'filepath' => $savename,
                'addtime' => time(),
            ]);
            
            if($res){
                echo "<script>parent.success();</script>";
                exit;
            }else{
                @unlink(ROOT_PATH . 'public' . DS . 'gene/'.$filename);
                echo "<script>parent.layer.msg('上传数据失败，请重试！',{icon:5});</script>";
                exit;
            }
            exit;
        }

        return $this->fetch();
    }

    public function import_data1(){
        if(request()->isPost()){

            $name = input('name');
            $mobile = input('mobile');
            $nation = input('nation');
            $on_addr = input('on_addr');
            $addr_log = input('addr_log');
            $migration = input('migration');
            $pai = input('pai');
            $is_family_tree = input('is_family_tree');
            $is_open = input('is_open');
            $desc1 = input('desc');
            $sex = input('sex');
            

            if(!$name){
                useJson([],'请输入姓名！',0);
                exit;
            }
            if( !preg_match("/^\W+$/",$name) ){
                useJson([],'请输入正确的姓名！',0);
                exit;
            }
            if(!$mobile){
                useJson([],'请输入联系方式！',0);
                exit;
            }
            if($mobile){
                if(!preg_match("/^1[34578]\d{9}$/", $mobile)){
                    return json(['status'=>0,'msg'=>'手机号码格式不正确！']); 
                }
            }
            
            $info['mobile'] = $mobile;
            $info['nation'] = $nation;
            $info['on_addr'] = $on_addr;
            $info['addr_log'] = $addr_log;
            $info['migration'] = $migration;
            $info['pai'] = $pai;
            $info['is_family_tree'] = $is_family_tree;
            $info['sex'] = $sex;
            $info['desc'] = $desc1;

            $desc = '';
            if($on_addr){
                $desc .= '现居：'.$on_addr;
            }
            if($addr_log){
                $desc .= '，故居：'.$addr_log;
            }
            if($mobile){
                $desc .= '，联系电话：'.$mobile;
            }
            if($migration){
                $desc .= '，迁移记录：'.$migration.'，';
            }
            $desc = $desc . $desc1;


            $user_id = $this->user_id ? $this->user_id : 0;
            if(!$user_id){
                useJson([],'请先登录！',0);
            }

            $savename = Db::name('users')->where('id',$user_id)->value('gene_file');
            if(!$savename){
                useJson([],'请选择您要上传的检测报告！',0);
            }
            
            $file_name = ROOT_PATH.'public/gene/import/'.$savename;
            vendor ('PHPExcel.PHPExcel');
            $objPHPExcel = new \PHPExcel();
            $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
            $obj_PHPExcel = $objReader->load($file_name, $encode = 'utf-8');
            $excel_array = $obj_PHPExcel->getsheet(0)->toArray(); //转换为数组格式

            $arr_name = [];
            $arr = [];
            $time = time();
            foreach($excel_array as $key=>$value){
                if($key==2){
                    foreach($value as $k=>$v){
                        if($k<1){continue;}
                        if($v){$arr_name[$k] = $v;}
                    }
                }
                if($key>2){
                    foreach($arr_name as $k=>$v){
                        $arr[$k]['name'] = $arr_name[$k];
                        $arr[$k][$value[0]] = $value[$k];
                    }
                }
            }
            $arr = array_values($arr);
            
            foreach($arr as $key=>&$value){
                $completion = [];
                foreach($value as $k=>$v){
                    if($k=='name'){continue;}
                    if( strtolower($k) === $k ){continue;}
                    if(Standard_Gene($k)){
                        $value[strtolower($k)] = $v ? intval((double)$v * 100) : 0;
                    }else{
                        $completion[strtolower($k)] = $v ? intval((double)$v * 100) : 0;
                    }
                }
                if($completion){
                    $value['completion'] = json_encode($completion);
                }
                
                $value['is_open'] = $is_open ? $is_open : 0;
                $value['user_id'] = $user_id;
                $value['desc'] = $desc;
                $value['nation'] = $nation;
                $value['pai'] = $pai;
                $value['is_family_tree'] = $is_family_tree;
                $value['addtime'] = $time;
                
                $info['name'] = $value['name'];
                $value['info'] = json_encode($info);
            }
            
            Db::name('gene')->strict(false)->insertAll($arr);
            Db::name('users')->update(['id'=>$user_id,'gene_file'=>'']);

            $res = Db::name('import_gene')->insert([
                'user_id' => $user_id,
                'name' => $name,
                'sex' => $sex,
                'year' => date('Y',$time),
                'month' => date('m',$time),
                'day' => date('d',$time),
                'filepath' => $savename,
                'addtime' => time(),
            ]);

            if($res){
                useJson(url('gene/index'));
            }else{
                @unlink(ROOT_PATH . 'public' . DS . 'gene/'.$filename);
                useJson([],'上传数据失败，请重试！',0);
            }
        }
    }

    # 在线填写报告
    public function online_import_data(){

        $this->Verification_User();
        
        if($_POST){
            $key = isset($_POST['key']) ? $_POST['key'] : array();
            $value = isset($_POST['value']) ? $_POST['value'] : array();
            $info = isset($_POST['info']) ? $_POST['info'] : array();
            $name = $info['name'];

            if(!$name){
                echo "<script>parent.error('请输入您的姓名');</script>";
                exit;
            }

            if( !preg_match("/^\W+$/",$name) ){
                echo "<script>parent.error('请输入正确的姓名！');</script>";
                exit;
            }

            if($info['mobile']){
                if(!preg_match("/^1[34578]\d{9}$/", $info['mobile'])){
                    return json(['status'=>0,'msg'=>'手机号码格式不正确！']); 
                }
            }
            
            $bitian = Standard_Gene_BiTian();

            if($key){
                $completion = '';
                foreach($key as $k=>$v){
                    if($v){
                        if( $value[$k] ){
                            if( !preg_match("/^[0-9]+$/",$value[$k]) ){
                                echo "<script>parent.error('请输入正确的频度！只能是数字！');</script>";
                                exit;
                            }
                        }
                        if(Standard_Gene($v)){
                            $v = strtolower($v);
                            if( in_array($v ,$bitian) && !$value[$k] ){
                                echo "<script>parent.error('红色字体的基因座为必填！');</script>";
                                exit;
                            }

                            $data[$v] = $value[$k] ? intval((double)$value[$k] * 100) : 0;
                        }else{
                            $completion[strtolower($v)] = $value[$k] ? intval((double)$value[$k] * 100) : 0;
                        }
                    }
                }
                
                $user_id = $this->user_id ? $this->user_id : 0;
                $data['name'] = $name;
                $data['user_id'] = $user_id;
                $data['completion'] = json_encode($completion);
                $data['addtime'] = $data['utime'] = time();
                $data['info'] = json_encode($info);
                $res = Db::name('gene')->strict(false)->insert($data);
                if($res){
                    echo "<script>parent.success('提交成功！正在刷新...');</script>";
                    exit;
                }
            }

            echo "<script>parent.error('操作失败，请重试！');</script>";
            exit;
        }

        
        return $this->fetch();
    }


    # 发送注册码邮件
    public function send_register_mail()
    {
        $register_id = isset($_POST['register_id']) ? trim($_POST['register_id']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';

        if ($register_id && $email) {

            $user = Db::name('users')->where('email', $email)->find();
            if ($user) {
                return json(['status' => 0, 'msg' => '该账号已被注册！']);
            }

            $code = rand(100000, 999999);

            $config = Db::query("select `name`,`value` from `zf_config` where `type` = 'email_setting'");
            if (empty($config)) {
                return json(['status' => 0, 'msg' => '系统未设置相关参数，请联系管理员']);
            }
            foreach ($config as $v) {
                $conf[$v['name']] = $v['value'];
            }

            $param = [
                'host'      => $conf['host'],
                'username'  => $conf['username'],
                'password'  => $conf['password'],
                'secure'    => $conf['secure'],
                'port'      => $conf['port'],
                'nickname'  => $conf['nickname'],
                'to'        => $email,
                'title'     => $conf['register_title'],
                'body'      => $conf['register_body'] ? str_replace('{{$code}}', $code, $conf['register_body']) : "<h1>注册码：$code</h1>",
                'altbody'   => $conf['register_altbody'] ? str_replace('{{$code}}', $code, $conf['register_altbody']) : "注册码：$code",
            ];

            if ($this->base_send_mail($param)) {
                $time = time();
                Db::execute("delete from `zf_mail_code` where `type` = 'register' and `sn` = '$register_id'");
                Db::execute("insert into `zf_mail_code` (`sn`,`email`,`code`,`addtime`,`type`) values ('$register_id', '$email', '$code', '$time', 'register')");
                return json(['status' => 1]);
            } else {
                return json(['status' => 0, 'msg' => '注册码发送失败！']);
            }
        }
        exit('null');
    }


    public function logout(){
        $is_logout = input('post.is_logout');
        if($is_logout == 1){
            Session::clear();
            return json(['status'=>1,'msg'=>'退出成功！']);      
        }else{
            return json(['status'=>1,'msg'=>'退出失败！']); 
        }
    }
}
