<?php

/**
 * 分销系统定时任务
 * 分佣
 */

 namespace app\crontab\controller;

 use think\Db;
 use think\Controller;
 use think\Session;

 class Commission extends Controller
 {

    private  $config = ['status' => 0];


     public function __construct()
     {
         $conf = Db::name('config')->where(['type'=>'distribution_setting'])->field('name,value')->select();
         if($conf){
             foreach($conf as $v){
                $config[$v['name']] = (double)$v['value'];
             }
             if(!$config['status']){
                 exit('分销功能未开启，请联系管理员！');
             }
             $this->config = $config;
         }
         set_time_limit(50);
         if(!Session::has('crontab_commission')){
             Session::set('crontab_commission', 40, 'crontab');
         }
     }

    public function index()
    {
        $info = $this->getOrder();
        if($info){
            $res = $this->quota_money($info);
            if($res){
                $d['money'] = $info['user_money'] + $info['order_amount'];
                $d['source_user_id'] = $info['user_id'];
                $d['source'] = 'buy';
                $d['source_id'] = $info['id'];
                $d['addtime'] = time();
                foreach($res as $k => $v){
                    $v['source_user_level'] = $k;
                    $inar = array_merge($d,$v);
                    Db::name('commission_log')->insert($inar);
                    Db::name('users')->where('id', $v['user_id'])->setInc('money', $v['commission']);
                }
            }
            Db::name('order')->where('id',$info['id'])->update(['is_distribut' => 1]);
            unset($res);
        }
        unset($info);
        $crontab_commission = Session::get('crontab_commission', 'crontab');
        if($crontab_commission){
            Session::set('crontab_commission', $crontab_commission-1, 'crontab');
            $this->index();
        }else{
            Session::pull('crontab_commission', 'crontab');
        }
    }

    # 检索未分成的订单
    private  function getOrder(){
        $where['is_distribut'] = ['=', 0];
        $time = $this->config['status'];
        if(!$time){
            return false;
        }
        if($this->config['distr_time'] == 0){
            $where['pay_status'] = ['=', 1];
            $where['pay_time'] = ['>=', $time];
        }else{
            $where['confirm_time'] = ['>=', $time];
        }

        $info = Db::name('order')->where($where)->find();
        return $info;
    }

    # 统计金额
    private function quota_money($data){
        $money = $data['user_money'] + $data['order_amount'];

        $leader = $this->check_up_user($data['user_id']);
        $re = [];

        if($leader['one']){
            $one_money = Db::name('users')->where('id',$leader['one'])->value('money');

            $one_quota = ($this->config['one_quota'] / 100) * $money;
            if($this->config['one_quota_min'] > 0 && $this->config['one_quota_min'] > $one_quota){
                $one_quota = $this->config['one_quota_min'];
            }        

            if($this->config['one_quota_max'] > 0 && $this->config['one_quota_max'] < $one_quota){
                $one_quota = $this->config['one_quota_max'];
            }

            $re[1] = ['user_id'=>$leader['one'],'commission'=>$one_quota,'user_money'=>$one_money,'user_money2'=>$one_quota+$one_money];
        }
        if($leader['two']){
            $two_money = Db::name('users')->where('id',$leader['two'])->value('money');
            $two_quota = ($this->config['two_quota'] / 100) * $money;
            if($this->config['two_quota_min'] > 0 && $this->config['two_quota_min'] > $two_quota){
                $two_quota = $this->config['two_quota_min'];
            }        

            if($this->config['two_quota_max'] > 0 && $this->config['two_quota_max'] < $two_quota){
                $two_quota = $this->config['two_quota_max'];
            }

            $re[2] = ['user_id'=>$leader['two'],'commission'=>$two_quota,'user_money'=>$two_money,'user_money2'=>$two_quota+$two_money];
        }
        return $re;
    }

    # 查找上级
    private function check_up_user($user_id){
        $first_leader = $two_leader = 0;
        
        $first_leader = Db::name('users')->where('id', $user_id)->value('first_leader');
        if($first_leader){
            $two_leader = Db::name('users')->where('id', $first_leader)->value('first_leader');
        }

        return [ 'one' => $first_leader, 'two' => $two_leader];
    }



 }

