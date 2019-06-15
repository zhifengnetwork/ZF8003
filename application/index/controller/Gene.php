<?php
namespace app\index\controller;

use think\Controller;
use think\Db;
use think\Session;

class Gene extends Base
{
    public function _initialize()
    {
        parent::_initialize();
        Session::set('re_url',"/index/$this->controller/$this->action");
        $this->Verification_User();
    }
    public function my_information()
    {

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $info = Db::name('gene')->where(['user_id'=>$this->user_id, 'id'=>$id])->field('id,info')->find();
        if(!$info){
            layer_error('查询信息不存在！');die;
        }
        
        $info = $info['info'] ? $info['info'] : '';
        $info = json_decode($info,true);
        
        return $this->fetch('',[
            'info'  =>  $info,
        ]);
    }

    # 我的基因数据
    public function index()
    {

        $gene_file = Db::name('users')->where('id',$this->user_id)->value('gene_file');
        if($gene_file){
            $url = url('index/information');
            header("Location: $url");die;
        }

        $list = Db::name('gene')->field('id,name')->where('user_id',$this->user_id)->select();

        $this->assign('list', $list);
        return $this->fetch();
    }

    # 基因数据详情
    public function info(){
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        $info = Db::name('gene')->where(['id'=>$id, 'user_id'=>$this->user_id])->find();
        if(!$info){
            layer_error('查询信息不存在！');
            exit;
        }

        $this->assign('name',$info['name']);
        $completion = $info['completion'] ? json_decode($info['completion'],true) : '';
        unset($info['id'],$info['user_id'],$info['name'],$info['desc'],$info['completion'],$info['addtime'],$info['utime']);
        if($completion){
            foreach($completion as $k => $v){
                $info[$k] = $v;
            }
        }

        $this->assign('list',$info);
        return $this->fetch();
    }

    public function analysis(){
        ini_set('memory_limit','2048M');
        set_time_limit(0);

        $re = isset($_GET['re']) ? intval($_GET['re']) : 0;
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $page = input('page');
        if(!$id){
            layer_error('请选择进行匹对的基因数据');
        }
        $calculation = Db::name('config')->where(['value'=>['>', 0], 'type'=>['=', 'gene_config_calculation']])->field('name')->select();
        if(!$calculation){
            layer_error('管理员未设置基因库检测参数，功能暂不可用！',true);
        }

        foreach($calculation as $v){
            $mutation[] = $v['name'];
        }

        $mutation = implode(",", $mutation);
        $config_mutation = Db::name('config')->where(['type'=>['=', 'gene_config_mutation'], 'name'=>['in',"$mutation"]])->field('name,value')->select();
        foreach($config_mutation as $v){
            if($v['value'] == 0){
                layer_error('管理员未设置基因库检测参数，功能暂不可用！',true);
            }
            $config[$v['name']] = $v['value'];
        }

        $i = Db::name('gene')->where(['user_id'=>$this->user_id, 'id'=>$id])->find();
        if(!$i){
            layer_error('非法访问，无权限的资源数据！');
        }
        $w['id'] = ['<>', $id];
        $pageParam = ['query' => []];
        foreach($config as $k => $v){
            $val = (double)$i[$k];
            $min = $val-200 > 0 ? $val-200 : 0;
            $max = $val+200;
            $w[strtolower($k)] = ['between',"$min,$max"];
            $pageParam['query'][strtolower($k)] = ['between',"$min,$max"];
        }
        $list_count = Db::name('gene')->field("id,name,nation,region,$mutation")->where($w)->count();
        if($list_count > 100 && !$re){
            $this->assign('loading', 1);
            $this->assign('id', $id);
            return $this->fetch();
            exit;
        }
        $list = Db::name('gene')->field("id,name,nation,region,$mutation")->where($w)->order('utime desc')->paginate(50,false,$pageParam);
        $list = $list->all();

        // $pindex = max(1, intval($page));
		// $psize = 10;
		// $pageCount = ceil(count($list_count) / $psize);
		// $offset = ($pindex - 1) * $psize;
        // $list = Db::name('gene')->field("id,name,nation,region,$mutation")->where($w)->order('utime desc')->limit($offset,$psize)->select();



        if(!$list){
            layer_error('抱歉！数据库没有匹配到相应的基因信息');
        }
        $lately = 1;
        $data = array();
        $count = count($list);
        foreach($list as $v){
            $r['id1'] = $i['id'];
            $r['id2'] = $v['id'];
            $r['name1'] = $i['name'] ? $i['name'] : '--';
            $r['name2'] = $v['name'] ? $v['name'] : '--';
            $r['nation1'] = $i['nation'] ? $i['nation'] : '--';
            $r['nation2'] = $v['nation'] ? $v['nation'] : '--';
            $r['region1'] = $i['region'] ? $i['region'] : '--';
            $r['region2'] = $v['region'] ? $v['region'] : '--';

            if(mb_strlen( $r['nation1'] ) > 10){
                $r['nation1'] = substr( $r['nation1'], 10 ) . '...';
            }
            if(mb_strlen( $r['nation2'] ) > 10){
                $r['nation2'] = substr( $r['nation1'], 10 ) . '...';
            }

            if(mb_strlen( $r['region1'] ) > 10){
                $r['region1'] = substr( $r['nation1'], 10 ) . '...';
            }

            if(mb_strlen( $r['region2'] ) > 10){
                $r['region2'] = substr( $r['nation1'], 10 ) . '...';
            }


            # 实际突变 | 基因座
            $diff = $locus = array();
            # 平均传递值 | 共祖年
            $pass = $cay = 0;

            foreach($config as $key=>$val){
                $d = math_diff($i[$key],$v[$key]);
                if($d > 0){
                    $d = $d * 0.01;
                }
                $diff[$key] = $d;
                $loc = $d > 0 && $val > 0 ? $d/$val : 0;
                $locus[$key] = $loc;
                $pass = $pass + $loc;
            }
            
            $pass = $pass/count($locus);
            $cay = $pass * 25 / 2;
            if($cay < 1401){
                $lately = 0;
            }
            $generation = intval($cay / 25);
            
            // $r['diff'] = $diff;
            // $r['locus'] = $locus;
            // $r['pass'] = $pass;
            $r['cay'] = intval($cay*100)/100;
            if($generation > 2){
                $r['generation'] = intval($generation - 2) . ' - ' . intval($generation + 2);
            }else{
                $r['generation'] = '0 - 3';
            }
            $r['line'] = $this->check_timeline($r['cay']);
            $data[] = $r;
            
        }
        
        if($data){
            $last_names = array_column($data,'cay');
            array_multisort($last_names,SORT_ASC,$data);
        }

        if($page>1){
            echo json_encode(['status'=>1,'msg'=>'获取成功！','data'=>$data]);die;
        }

        // dump($data);exit;
        $this->assign('lately', $lately);
        $this->assign('data', $data);
        $this->assign('info', json_decode($i['info'],true));
        return $this->fetch();
    }

    # 匹配时间线
    function check_timeline($time){
        # 时间线
        $time = intval($time);
        $timeline_config = Db::name('config')->field('name,value')->where(['type' => 'gene_config_timeline'])->select();
        if($timeline_config){
            foreach($timeline_config as $k => $v){
                $key = explode('_', $v['name']);
                if($time >= $key[0] && $time <= $key[1]){
                    return json_decode($v['value'], true);
                }
            }
        }
        return '';
    }
}