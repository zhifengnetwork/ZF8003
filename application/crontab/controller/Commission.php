<?php

/**
 * 分销系统定时任务
 * 分佣
 */

 namespace app\crontab\controller;

 use think\Db;

 class Commission
 {

    public $config = ['status' => 0];


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
     }

    public function index()
    {

        $info = $this->getOrder();
        dump($info);exit;





    }

    # 检索未分成的订单
    public function getOrder(){
        $where['is_distribut'] = ['=', 0];
        $time = $this->config['status'];
        if($this->config['distr_time'] == 0){
            $where['pay_status'] = ['=', 1];
            $where['pay_time'] = ['>=', $time];
        }else{
            $where['confirm_time'] = ['>=', $time];
        }

        $info = Db::name('order')->where($where)->find();
        return $info;
    }



 }

