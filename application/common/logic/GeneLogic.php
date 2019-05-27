<?php
namespace app\common\logic;
use think\Db;
use think\Session;

class GeneLogic
{
    public $config = [];
    public $mutation;
    public $count = 0;
    public $field;
    public $info;
    public $timeline = [];

    public function __construct()
    {
        ini_set('memory_limit','2048M');
        set_time_limit(0);
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getField(){
        if($this->field){
            return $this->field;
        }
        $field = 'id,name,nation,region,'.$this->mutation;
        $this->field = $field;
        return $field;
    }

    public function config_init($re = false)
    {
        $calculation = Db::name('config')->where(['value'=>['>', 0], 'type'=>['=', 'gene_config_calculation']])->field('name')->select();
        if(!$calculation){
            layer_error('管理员未设置基因库检测参数，功能暂不可用！',true);
        }

        foreach($calculation as $v){
            $mutation[] = $v['name'];
        }
        
        $mutation = implode(",", $mutation);
        $this->mutation = $mutation;

        $config_mutation = Db::name('config')->where(['type'=>['=', 'gene_config_mutation'], 'name'=>['in',"$mutation"]])->field('name,value')->select();
        foreach($config_mutation as $v){
            if($v['value'] == 0){
                layer_error('管理员未设置基因库检测参数，功能暂不可用！',true);
            }
            $config[$v['name']] = $v['value'];
        }

        $this->config = $config;

        if($re){
            return $this->config;
        }
    }


    public function getCount($where){
        $count = Db::name('gene')->field($this->getField())->where($where)->count();
        $this->count = $count;
        return $count;
    }

    public function analysis($i,$where)
    {
        
        $cache = $this->getAnalysisCache($i['id']);
        
        if($cache){
            return $cache;
        }

        $list = $this->getList($where);
        dump($list);exit;
        if(!$list){
            layer_error('抱歉！数据库没有匹配到相应的基因信息');
        }
        $lately = 1;
        $data = [];
        foreach($list as $v){
            $r['gene_id'] = $i['id'];
            $r['to_id'] = $v['id'];
            $r['name'] = $i['name'] ? $i['name'] : '--';
            $r['to_name'] = $v['name'] ? $v['name'] : '--';
            $r['nation'] = $i['nation'] ? $i['nation'] : '--';
            $r['to_nation'] = $v['nation'] ? $v['nation'] : '--';
            $r['region'] = $i['region'] ? $i['region'] : '--';
            $r['to_region'] = $v['region'] ? $v['region'] : '--';

            if(mb_strlen( $r['nation'] ) > 10){
                $r['nation'] = substr( $r['nation'], 10 ) . '...';
            }
            if(mb_strlen( $r['to_nation'] ) > 10){
                $r['to_nation'] = substr( $r['to_nation'], 10 ) . '...';
            }

            if(mb_strlen( $r['region'] ) > 10){
                $r['region'] = substr( $r['region'], 10 ) . '...';
            }

            if(mb_strlen( $r['to_region'] ) > 10){
                $r['to_region'] = substr( $r['to_region'], 10 ) . '...';
            }


            # 实际突变 | 基因座
            $diff = $locus = array();
            # 平均传递值 | 共祖年
            $pass = $cay = 0;

            foreach($this->config as $key=>$val){
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
            
            $this->check_cache($i['id'], $v['id'],$r);

            $data[] = $r;
            
        }
        
        if($data){
            $last_names = array_column($data,'cay');
            array_multisort($last_names,SORT_ASC,$data);
        }
        
        $sur = $this->count - 100;
        return ['id'=>$i['id'], 'count'=>$count,'lately'=>$lately, 'r_cache'=>0, 'page'=>'1', 'sur'=>$sur, 'data'=>$data];
    }

    public function check_cache($id,$toid,$data)
    {
        $c = Db::name('gene_analysis_cache')->where(['gene_id'=>$id, 'to_id'=>$toid])->count();
        if(!$c){
            $data['line'] = json_encode($data['line']);
            $data['date'] = date('Ymd');
            Db::name('gene_analysis_cache')->insert($data);
        }
    }

    public function getAnalysisCache($id)
    {
        $cache_count = Db::name('gene_analysis_cache')->where('gene_id',$id)->count();
        $cache = Db::name('gene_analysis_cache')->where('gene_id',$id)->order('cay asc')->limit(200)->select();
        if($cache){
            $lately_r = Db::name('gene_analysis_cache')->where(['gene_id'=>['=', $id], 'cay' => ['<', 1401]])->count();

            if($lately_r){
                $lately = 0;
            }else{
                $lately = 1;
            }
            foreach($cache as $k => $v){
                $cache[$k]['line'] = json_decode($v['line'],true);
            }
            $count = count($cache);
            $sur = $this->count - $count;
            $r_cache = $cache_count - $count;
            $page = 1;
            return ['id'=>$id, 'count'=>$this->count, 'lately'=>$lately, 'sur'=>$sur, 'r_cache'=>$r_cache, 'page'=>$page, 'data'=>$cache];
        }
        return '';
    }

    public function getCache($id,$page,$r_cache,$count){
        $page_limit = $page*100+200;
        $cache = Db::name('gene_analysis_cache')->where('gene_id',$id)->order('cay asc')->limit($page,200)->select();
        $c_count = count($cache);
        $sur = $count - $c_count;
        $r_cache = $r_cache - $c_count;
        return ['id'=>$id, 'sur'=>$sur, 'r_cache'=>$r_cache, 'page'=>$page+1, 'data'=>$cache];
    }

    public function getList($where)
    {

        $list = Db::name('gene')->field($this->getField())->where($where)->order('utime desc')->limit(100)->select();
        return $list;

    }

    # 匹配时间线
    public function check_timeline($time){
        # 时间线
        $time = intval($time);
        $config = $this->timeline_init();
        foreach($config as $k => $v){
            $key = explode('_', $k);
            if($time >= $key[0] && $time <= $key[1]){
                return $v;
            }
        }
        
        return '';
    }

    public function timeline_init()
    {
        if($this->timeline){
            return $this->timeline;
        }
        $timeline_config = Db::name('config')->field('name,value')->where(['type' => 'gene_config_timeline'])->select();
        if($timeline_config){
            foreach($timeline_config as $v){
                $config[$v['name']] = json_decode($v['value'], true); 
            }
        }else{
            $config = ['0_0' => ''];
        }
        $this->timeline = $config;
        return $config;
    }




}