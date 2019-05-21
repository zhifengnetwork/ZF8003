<?php
/**
 * Author: ppc
 * Date: 2019-4-10
 */

namespace app\mobile\controller;

use app\mobile\model\Area;
use think\Session;
use think\Db;

class User extends Base
{
    public $user_id = 0;

    public function __construct(){
        parent::__construct();
        
        # 验证登录
        $this->Verification_User();
        $this->user_id = session('user.id');
    }

    /** 
     * 我的
     */
    public function index()
    {



        $this->assign('user', $this->user);
        return $this->fetch();
    }

    # 我的基因
    public function my_gene(){
       
        $list = Db::name('gene')->where('user_id',$this->user_id)->field('id,`name`')->select();
        
        $this->assign('list',$list);
        return $this->fetch();
    }

    # 基因数据报告详情
    public function gene_info(){

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $info = Db::name('gene')->where(['user_id'=>$this->user_id,'id'=>$id])->find();
        if(!$info){
            layer_error('查询结果不存在！');
            exit;
        }
        $name = $info['name'];
        $id = $info['id'];
        $completion = $info['completion'];
        unset($info['id'],$info['user_id'],$info['name'],$info['desc'],$info['addtime'],$info['utime'],$info['completion']);
        if($completion){
            $completion = json_decode($completion,true);
            foreach($completion as $k => $v){
                $info[$k] = $v;
            }
        }

        // dump($info);exit;
        $this->assign('name',$name);
        $this->assign('info',$info);
        return $this->fetch();
    }

    # 基因查询
    public function gene_query(){

        $list = Db::name('gene')->where('user_id',$this->user_id)->field('id,`name`')->select();
        
        $this->assign('list',$list);
        return $this->fetch();
    }

    # 基因座筛选匹配
    public function gene_matching(){



        $this->assign('gene_list', Standard_Gene());
        return $this->fetch();
    }

    # 基因座筛选匹配结果
    public function gene_matching_result(){

        $keyworks = isset($_GET['keyworks']) ? $_GET['keyworks'] : '';
        if(!$keyworks){
            layer_error('参数错误！');
        }
        $w = '';
        foreach($keyworks as $k => $v){
            if(Standard_Gene($k)){
                $v = (double)$v;
                $min = $v*100-200 > 0 ? $v*100-200 : 0;
                $max = $v*100+200;
                $w[strtolower($k)] = ['between',"$min,$max"];
            }
        }
        if(!$w){
            layer_error('参数错误！');
        }
        $list = Db::name('gene')->field('id,name')->where($w)->order('utime desc')->select();
        
        $this->assign('list',$list);
        return $this->fetch();
    }

    # 基因检测结果
    public function gene_result(){
        $ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : '';
        if(!$ids){
            layer_error('参数错误！');
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

        foreach($ids as $v){
            $i = Db::name('gene')->where( 'id', $v)->find();
            if(!$i){
                layer_error('非法访问无权限的资源！',true);
            }
            $list[] = $i;
        }
        $data = array();
        $count = count($list);
        foreach($list as $k=>$v){
            if($k < $count - 1){
                foreach($list as $ke=>$va){
                    if($ke > $k){
                        $r['id1'] = $v['id'];
                        $r['id2'] = $va['id'];
                        $r['name1'] = $v['name'] ? $v['name'] : '--';
                        $r['name2'] = $va['name'] ? $va['name'] : '--';
                        $r['nation1'] = $v['nation'] ? $v['nation'] : '--';
                        $r['nation2'] = $va['nation'] ? $va['nation'] : '--';
                        $r['region1'] = $v['region'] ? $v['region'] : '--';
                        $r['region2'] = $va['region'] ? $va['region'] : '--';

                        # 实际突变 | 基因座
                        $diff = $locus = array();
                        # 平均传递值 | 共祖年
                        $pass = $cay = 0;

                        foreach($config as $key=>$val){
                            $d = math_diff($v[$key],$va[$key]);
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

                        $r['diff'] = $diff;
                        $r['locus'] = $locus;
                        $r['pass'] = $pass;
                        $r['cay'] = intval($cay*100)/100;
                        $data[] = $r;
                    }
                }
            }
        }
        $this->assign('data', $data);
        return $this->fetch();

    }

    # 基因查询报告
    public function gene_analysis(){
        $q = isset($_GET['q']) ? explode(',',$_GET['q']) : '';
        if(!$q || count($q) < 2){
            layer_error('参数错误！',true);
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

        foreach($q as $qv){
            $i = Db::name('gene')->where(['user_id'=>$this->user_id, 'id'=>$qv])->find();
            if(!$i){
                layer_error('非法访问无权限的资源！',true);
            }
            $list[] = $i;
        }
        $data = array();
        $count = count($list);
        foreach($list as $k=>$v){
            if($k < $count - 1){
                foreach($list as $ke=>$va){
                    if($ke > $k){
                        $r['name1'] = $v['name'];
                        $r['name2'] = $va['name'];

                        # 实际突变 | 基因座
                        $diff = $locus = array();
                        # 平均传递值 | 共祖年
                        $pass = $cay = 0;

                        foreach($config as $key=>$val){
                            $d = math_diff($v[$key],$va[$key]);
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

                        $r['diff'] = $diff;
                        $r['locus'] = $locus;
                        $r['pass'] = $pass;
                        $r['cay'] = $cay;
                        $data[] = $r;
                    }
                }
            }
        }

        $this->assign('data', $data);
        return $this->fetch();
    }

    # 基因检测报告 - 列表
    public function gene_analysis_list(){
        ini_set('memory_limit','2048M');
        set_time_limit(0);

        $re = isset($_GET['re']) ? intval($_GET['re']) : 0;
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
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
        foreach($config as $k => $v){
            $val = (double)$i[$k];
            $min = $val-200 > 0 ? $val-200 : 0;
            $max = $val+200;
            $w[strtolower($k)] = ['between',"$min,$max"];
        }
        $list_count = Db::name('gene')->field("id,name,nation,region,$mutation")->where($w)->count();
        if($list_count > 100 && !$re){
            $this->assign('loading', 1);
            $this->assign('id', $id);
            return $this->fetch();
            exit;
        }
        $list = Db::name('gene')->field("id,name,nation,region,$mutation")->where($w)->order('utime desc')->select();
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
        
        $this->assign('lately', $lately);
        $this->assign('data', $data);
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
                    return $v['value'];
                }
            }
        }
        return '';
    }

    # 删除基因数据
    public function del_my_gene(){
        
        $ids = isset($_POST['ids']) ? $_POST['ids'] : '';
        $sql = "delete from `zf_gene` where `user_id` = '$this->user_id' and `id` in ('$ids')";
        $res = Db::execute($sql);
        if($res){
            return json(['status'=>1]);
        }else{
            return json(['status'=>0]);
        }
        exit;
    }

    /**
     * 我的订单
     */
    public function my_order()
    {
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        $user_id = $this->user_id;

        $where = "a.user_id = '$user_id'";
        if($status > 0){
            $where .= " and a.order_status = ".($status - 1);
        }

        $lists = Db::query("select a.*,b.name,b.thumb from `zf_order` as a left join `zf_goods` as b on a.goods_id = b.id where $where order by a.order_status asc,a.add_time desc");
    
        $sname = [0=>'待支付',1=>'待发货',2=>'待收货',3=>'待评价',4=>'交易成功'];


        $this->assign('status', $status);
        $this->assign('sname', $sname);
        $this->assign('lists', $lists);
        return $this->fetch();
    }
	
	# 确认收货
	public function confirm_order(){
		
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		
		if($id > 0){
			$res = Db::name('order')->where(['user_id' => $this->user_id, 'id' => $id])->update(['order_status' => 3]);
			if($res){
				return json(['status' => 1]);
			}
		}
		return json(['status' => 0]);
    }
    
    # 取消订单 | 取消待支付的订单
    public function cancel_order(){
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $info = Db::name('order')->where(['id'=>$id,'user_id'=>$this->user_id,'order_status'=>0])->find();

        if($info){
            if($info['pay_status'] == 3){
                return json(['status'=>0,'msg'=>'业务处理中，订单暂不可操作']);
            }

            if($info['pay_status'] == 2){
                Db::name('users')->where(['id'=>$info['user_id']])->setInc('money', $info['user_money']);
                Db::name('order')->where('id', $id)->update(['pay_status'=>0,'user_money'=>0]);
                
                Db::name('transaction_log')->insert(['user_id'=>$info['user_id'],'type'=>'recharge','sn'=>$info['order_sn'],'platform'=>'system','money'=>$info['user_money'],'addtime'=>time(),'desc'=>'取消订单，返回使用余额抵扣部分金额，请留意账户余额']);

                $user = Db::name('users')->find($this->user_id);
                Session::set('user', $user);
                $this->user = $user;
            }


            $res = Db::name('order')->where(['id'=>$id,'user_id'=>$this->user_id])->delete();
            if($res){
                return json(['status'=>1]);
            }
        }
        return json(['status'=>0,'msg'=>'操作失败，请重试！']);
    }
	
	

    /**
     * 我的分销
     */
    public function distribution()
    {
        layer_msg('功能未开放...');
        exit;
        return $this->fetch();
    }

    /**
     * 我的团队
     */
    public function team_list()
    {
        layer_msg('功能未开放...');
        exit;
        return $this->fetch();
    }

    /**
     * 佣金记录
     */
    public function commission()
    {
        layer_msg('功能未开放...');
        exit;
        return $this->fetch();
    }

    /**
     * 业绩明细
     */
    public function performance()
    {
        layer_msg('功能未开放...');
        exit;
        return $this->fetch();
    }

    /**
     * 我的钱包
     */
    public function my_walet()
    {
        $user_id = $this->user_id;
        $user = Db::name('users')->where('id',$user_id)->field('money')->find();
        $this->assign('info',$user);
        return $this->fetch();
    }

    /**
     * 账户充值
     */
    public function top_up()
    {
        
        #充值设置
        $config = Db::name('config')->where('type','cash_setting')->select();
        if($config){
            foreach($config as $v){
                $conf[$v['name']] = $v['value'];
            }
            $config = $conf;
        }else{
            layer_error('管理员未开放充值入口！');
            exit;
        }

        if($config['recharge'] == 0){
            layer_error('管理员未开放充值入口！');
            exit;
        }


        $sn = isset($_GET['sn']) ? trim($_GET['sn']) : '';
        if($sn){

            $t_log = Db::name('transaction_log')->field('id')->where('sn', $sn)->find();
            if($t_log){
                goto SUBJECT;
            }
            require_once ROOT_PATH."plugins/pay/weixinpay/lib/WxPay.Api.php";
            require_once ROOT_PATH."plugins/pay/weixinpay/WxPay.Config.php";
            $input = new \WxPayOrderQuery(); 
            $input->SetOut_trade_no($sn);
            $config = new \WxPayConfig();
            $res = \WxPayApi::orderQuery($config, $input);
            if($res && $res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS' && $res['trade_state'] == 'SUCCESS'){
                $info = Db::name('wxpay_cache')->where(['sn' => $sn, 'type' => 'recharge'])->find();
                if($info){
                    $usql = "update `zf_users` set `money` = money + '$info[money]' where `id` = '$info[user_id]'";
                    Db::execute($usql);
                    $transaction = [
                        'user_id' => $info['user_id'],
                        'type' => 'recharge',
                        'sn' => $sn,
                        'transaction_id' => isset($res['transaction_id']) ? $res['transaction_id'] : '',
                        'platform' => 'weixin',
                        'trade_type' => isset($res['trade_type']) ? $res['trade_type'] : '',
                        'money' => $info['money'],
                        'addtime' => time(),
                        'desc' => $info['attach'],
                        'init_time' => $info['addtime'],
                    ];


                    $recharge_log = [
                        'user_id' => $info['user_id'],
                        'money' => $info['money'],
                        'body' => $info['body'],
                        'attach' => $info['attach'],
                        'addtime' => time(),
                        'init_time' => $info['addtime'],
                        'platform' => 'weixin',
                        'status' => 1,
                    ];

                    Db::name('transaction_log')->insert($transaction);
                    Db::name('recharge')->insert($recharge_log);
                    Db::name('wxpay_cache')->where('sn', $sn)->delete();
                    if($info['user_id'] == $this->user_id){
                        $user = Db::name('users')->find($info['user_id']);
                        Session::set('user', $user);
                        $this->user =  $user;
                    }
                }
            }
            
        }

        SUBJECT:

        $this->assign('info', $this->user);
        return $this->fetch();
    }

    /**
     * 申请提现
     */
    public function withdrawal()
    {
        $config = Db::name('config')->where('type', 'cash_setting')->select();
        if($config){
            foreach($config as $v){
                $conf[$v['name']] = $v['value'];
            }
            $config = $conf;
        }else{
            layer_error('管理员未开放提现入口！');
            exit;
        }

        if($_POST){
            $type = isset($_POST['type']) && in_array($_POST['type'],['alipay','weixin']) ? trim($_POST['type']) : 'alipay';
            $money = isset($_POST['money']) ? trim($_POST['money']) : 0;
            
            if(!$money){
                return json(['status'=>0,'msg'=>'请输入提现金额！']);
            }
            if($config['withdrawal_cash_max'] && $money > $config['withdrawal_cash_max']){
                return json(['status'=>0,'msg'=>'单笔提现最高：'.$config['withdrawal_cash_max'].' 元']);
            }
            if($config['withdrawal_cash_min'] && $money < $config['withdrawal_cash_min']){
                return json(['status'=>0,'msg'=>'单笔提现最低：'.$config['withdrawal_cash_min'].' 元']);
            }
            $fee = 0;
            if($config['withdrawal_fee'] > 0){
                $fee = $money * ($config['withdrawal_fee'] * 0.01);
                
                if($config['withdrawal_fee_max'] > 0 && $fee > $config['withdrawal_fee_max']){
                    $fee = $config['withdrawal_fee_max'];
                }
                if($config['withdrawal_fee_min'] > 0 && $fee < $config['withdrawal_fee_min']){
                    $fee = $config['withdrawal_fee_min'];
                }
                
                if(($money + $fee) > $this->user['money']){
                    return json(['status'=>0,'msg'=>'账户余额不足，请调整提现金额！']);
                }
            }
            
            if($type == 'weixin'){
                $account = $this->user['re_wx_account'];
                $name = $this->user['re_wx_name'];
            }else{
                $account = $this->user['re_alipay_account'];
                $name = $this->user['re_alipay_name'];
            }
            if(!$account || !$name){
                return json(['status'=>0, 'msg'=>'请先设置提现账号！']);
            }

            
            $inser_data = [
                'user_id' => $this->user_id,
                'money' => $money,
                'fee' => $fee,
                'type' => $type,
                'account' => $account,
                'name' => $name,
                'addtime' => time(),
                'utime' => time(),
            ];
            // dump( $inser_data);exit;
            $res = Db::name('withdrawal')->insert($inser_data);
            if($res){
                return json(['status'=>1]);
            }else{
                return json(['status'=>0,'msg'=>'操作失败，请重试！']);
            }

            exit;
        }



        if(!array_key_check($config,'withdrawal', true)){
            layer_error('管理员未开放提现入口！');
            exit;
        }

        $user = Db::name('users')->find($this->user_id);
        Session::set('user',$user);
        $this->user = $user;


        $this->assign('money_max', $config['withdrawal_cash_max']);
        $this->assign('money_min', $config['withdrawal_cash_min']);
        $this->assign('fee', $config['withdrawal_fee']);
        $this->assign('fee_max', $config['withdrawal_fee_max']);
        $this->assign('fee_min', $config['withdrawal_fee_min']);
        $this->assign('info', $user);
        return $this->fetch();
    }

    /**
     * 设置提现账号
     */
    public function set_withdrawal_account(){

        if($_POST){
            $type = isset($_POST['type']) && in_array($_POST['type'],['alipay','weixin']) ? trim($_POST['type']) : 'alipay';
            $account = isset($_POST['account']) ? trim($_POST['account']) : '';
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';

            $user_id = $this->user_id;
            if(!$user_id){
                return json(['status'=>0,'msg'=>'请先登录']);
            }
            if($type == 'alipay'){
                $sql = "update `zf_users` set `re_alipay_account` = '$account', `re_alipay_name` = '$name' where `id` = '$user_id'";
            }else{
                $sql = "update `zf_users` set `re_wx_account` = '$account', `re_wx_name` = '$name' where `id` = '$user_id'";
            }
            Db::execute($sql);
            return json(['status' => 1]);
            exit;
        }

        $type = isset($_GET['type']) && in_array($_GET['type'],['alipay','weixin']) ? trim($_GET['type']) : 'alipay';

        if($type == 'alipay'){
            $account = $this->user['re_alipay_account'];
            $name = $this->user['re_alipay_name'];
        }else{
            $account = $this->user['re_wx_account'];
            $name = $this->user['re_wx_name'];
        }

        $this->assign('type', $type);
        $this->assign('account', $account);
        $this->assign('name', $name);
        return $this->fetch();
    }


    /**
     * 账单明细
     */
    public function billing_detail()
    {
        $t = isset($_GET['t']) ? intval($_GET['t']) : 0;
        $where = ' where `user_id` = '.$this->user_id;
        if($t == 1){
            $where .= " and `type` = 'recharge'";
        }
        if($t == 2){
            $where .= " and `type` in ('withdrawal','order')";
        }
        $sql = "select `id`,`money`,`addtime` from `zf_transaction_log` $where order by `id` desc";
        $lists = Db::query($sql);


        $this->assign('lists', $lists);
        $this->assign('t', $t);
        return $this->fetch();
    }

    /**
     * 充值明细
     */
    public function top_up_detail()
    {
        $user_id = $this->user_id;
        $sql ="select `platform`,`addtime`,`money`,`status` from `zf_recharge` where `user_id` = '$user_id' order by init_time desc";
        $lists = Db::query($sql);
        $pname = ['weixin'=>'微信','alipay'=>'支付宝'];
        $sname = [0=>'处理中', 1=>'成功', 2=>'失败'];

        $this->assign('pname', $pname);
        $this->assign('sname', $sname);
        $this->assign('lists', $lists);
        return $this->fetch();
    }

    /**
     * 提现记录
     */
    public function withdrawal_detail()
    {


        $user_id = $this->user_id;
        $sql ="select `type`,`addtime`,`money`,`fee`,`status` from `zf_withdrawal` where `user_id` = '$user_id' order by addtime desc";
        $lists = Db::query($sql);
        $sname = [0=>'待审核', 1=>'成功', 2=>'失败'];

        $this->assign('sname', $sname);
        $this->assign('lists', $lists);
        return $this->fetch();
    }

    /**
     * 我的地址
     */
    public function my_address()
    {
        $re_url = isset($_GET['re_url']) ? trim($_GET['re_url']) : '';
        if($re_url){
            Session::set('re_url', str_replace('`','',$re_url));
        }
        $user_id = $this->user_id;
        $address_list = Db::name('user_address')->where('user_id',$user_id)->select();
        $list = $this->address_join($address_list);

        $this->assign('return',Session::has('re_url') ? Session::get('re_url') : '/mobile/user/index');
        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * 新增收货地址
     */
    public function add_address()
    {
        return $this->fetch();
    }

     /**
     * 修改地址
     */
    public function edit_address()
    {
        $id = input('id/d');
        $info = Db::name('user_address')->where('id',$id)->find();
        $info1 = $this->address_join($info);
        
        $this->assign('info',$info1);
        return $this->fetch();
    }

    /**
     * 地址拼接
     */
    public function address_join($info)
    {
        $address = "";
        if ($info && is_array($info)) {
            $area = new Area;
            $province = "";
            $city = "";
            $district = "";
            $twon = "";
            //判断是一位数组还是多维数组
            if (count($info) == count($info, 1)) {
                $province = $area->where('id',$info['province'])->value('name');
                $city = $area->where('id',$info['city'])->value('name');
                $district = $area->where('id',$info['district'])->value('name');
                
                $address = $province ? $province : '';
                $address = $city ? $address . ' ' . $city : $address;
                $address = $district ? $address . ' ' . $district : $address;
                $info['area'] = $address;
                $info['key'] = $info['province'] . "-" . $info['city'] . "-" . $info['district'];
            } else {
                foreach($info as $key => $value){
                    $province = $area->where('id',$value['province'])->value('name');
                    $city = $area->where('id',$value['city'])->value('name');
                    $district = $area->where('id',$value['district'])->value('name');
                    
                    $address = $province ? $province : '';
                    $address = $city ? $address . ' ' . $city : $address;
                    $address = $district ? $address . ' ' . $district : $address;
                    $info[$key]['area'] = $address;

                    $address = "";
                    $province = "";
                    $city = "";
                    $district = "";
                    $twon = "";
                }
            }
        }
        
        return $info;
    }

    /**
     * 地址处理函数
     */
    public function handle_address()
    {
        $data = $this->request->post();
        if (!$data) {
            return ['code' => 0];
        }
        $user_id = $this->user_id;
        
        if (!$user_id) {
            return ['code' => 0];
        }

        $return = array('code' => 0);

        //编辑
        if ($data['type'] == 'edit') {
            if (isset($data['myAddrs']) && $data['myAddrs']) {
                $address = explode('-',$data['myAddrs']);
                $area = ['province','city','district','twon'];
                if(is_array($address)){
                    foreach($address as $key => $value){
                        $result[$area[$key]] = $value;
                    }
                }
            }
            
            $result['user_id'] = $user_id;
            $result['consignee'] = $data['consignee'];
            $result['mobile'] = $data['mobile'];
            $result['address'] = $data['site'];
            $result['is_default'] = intval($data['is_default']);
            
            Db::startTrans();//开启事务
            $bool = false;
            $is_update = true;
            $address_id = intval($data['address_id']);
            
            if ($result['is_default'] == 1) {
                $is_update = Db::name('user_address')->where('user_id',$user_id)->where('is_default',1)->update(['is_default' => 0]);
            }
            
            if ($is_update !== false) {
                if ($address_id > 0) {
                    $bool = Db::name('user_address')->where('id',$address_id)->update($result);
                } else {
                    $address_id = Db::name('user_address')->insertGetId($result);
                    $bool = $address_id ? true : false;
                }
                
                if ($bool) {
                    $bool = Db::name('users')->where('id',$this->user_id)->update(['default_address_id'=>$address_id]);
                }
            }

            if(!$bool){
                Db::rollback();
            } else {
                Db::commit();
                $return['code'] = 1;
            }
        }
        
        //删除
        if ($data['type'] == 'del') {
            $id = intval($data['id']);
            if ($id) {
                $bool = Db::name('user_address')->delete($id);

                if ($bool) {
                    $return['code'] = 1;
                }
            }
        }

        return $return;
    }

    /**
     * 我的收藏
     */
    public function collection()
    {
        $user_id = $this->user_id;
        if($_POST){
            $goods_id = input('post.goods_id');
            $where = [
              'user_id' => $this->user_id,
              'goods_id'=> $goods_id
            ];
            $res = Db::name('goods_focus')->where($where)->delete();
            if($res){
                return json(['status'=>1,'msg'=>'删除成功']);
            }else{
                return json(['status' => 1,'msg'=>'删除失败']);
            }
        }
        $info = Db::name('goods_focus')
            ->alias('g')
            ->join('goods go', 'g.goods_id = go.id')
            ->where('user_id', $user_id)
            // ->field('u.*,g.name')
            ->select();
        $this->assign('list', $info);
        return $this->fetch();        
    }

    /**
     * 我的评价
     */
    public function evaluation()
    {
        return $this->fetch();
    }

    /**
     * 我的二维码
     */
    public function qr_code()
    {
        
        $user = $this->user;
        if(!$user['ticket'] || $user['ticket_expire_seconds'] < time()){
            $openid = $user['openid'];
            if(!$openid){
                if(!is_weixin()){
                    layer_error('您还没有绑定微信账号！请在微信浏览器上进行操作！');
                    exit;
                }
                $this->user_wxinfo_completion();
                $user = $this->user;
                $openid = $user['openid'];
            }
            $this->get_weixin_global_token();
            $token = $this->weixin_config['weixin_access_token'];
            $param = [
				'expire_seconds' => 2592000,
				'action_name' => 'QR_STR_SCENE',
				'action_info' => [
						'scene' => [
                            'scene_str' => 'openid:'.$user['openid'],
						],
				],
            ];
			$url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$token";
			$res = httpRequest($url,'POST',json_encode($param));
            $res = json_decode($res,true);
            
            if(isset($res['ticket'])){
                $ticket_data = [
                    'ticket' => $res['ticket'],
                    'ticket_expire_seconds' => time() + $res['expire_seconds'] - 200,
                    'ticket_url' => $res['url'],
                ];
                Db::name('users')->where('id', $user['id'])->update($ticket_data);
                $user['ticket'] = $ticket_data['ticket'];
                $user['ticket_expire_seconds'] = $ticket_data['ticket_expire_seconds'];
                $user['ticket_url'] = $ticket_data['ticket_url'];
                $this->user = $user;
                Session::set('user', $user);
                $this->redirect('qr_code');
            }
            layer_error('出错了！！！');
            exit;
        }
        


        $src = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".UrlEncode($user['ticket']);
        $this->assign('src',$src);
        return $this->fetch();
    }

    /**
     * 我的优惠券
     */
    public function coupons()
    {
        $user_id = $this->user_id;

        $list = Db::name('user_coupon')
                ->alias('u')
                ->join('goods_coupon g', 'u.coupon_id = g.id')
                ->where('user_id', $user_id)
                ->where('etime', '>= time', time())
                ->field('u.*,g.name,g.quota quota1,g.money money1')                
                ->select();       
        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * 设置
     */
    public function set_up()
    {

        $user = $this->user;
        $user['sex_name'] = [0=>'保密',1=>'男',2=>'女'][$user['sex']]; 


        $this->assign('user',$user);
        return $this->fetch();
    }


    /**
     * 绑定邮箱
     */
    public function edit_email(){
        
        $user_email = $this->user['email'];
        if($user_email){
            $this->redirect('index');
        }

        if($_POST){
            $pass_id = isset($_POST['pass_id']) ? trim($_POST['pass_id']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $code = isset($_POST['code']) ? trim($_POST['code']) : '';

            $user_id = $this->user_id;

            if(!$user_id){
                return json(['status'=>0,'msg'=>'用户未登录，非法访问']);
            }
            
            $info = Db::name('users')->field('email')->find($user_id);
            if($info['email']){
                return json(['status'=>0,'msg'=>'暂不开放修改邮箱账号功能！']);
            }

            if(!$pass_id){
                return json(['status'=>0,'msg'=>'系统错误，请刷新页面后重试！']);
            }
            
            if(!check_email($email)){
                return json(['status'=>0,'msg'=>'邮箱格式错误！']);
            }

            $code_info = Db::name('mail_code')->where(['type'=>'edit_mail', 'sn'=>$pass_id, 'code'=>$code])->find();
            if(!$code_info){
                return json(['status'=>0,'msg'=>'验证码错误！']);
            }

            $check_mail = Db::name('users')->field('id')->where('email',$email)->find();
            if($check_mail){
                return json(['status'=>0,'msg'=>'邮箱已被使用，请更换！']);
            }

            
            $sql = "update `zf_users` set `email` = '$email', `email_verification` = '`' where `id` = '$user_id'";
            $res = Db::execute($sql);

            if($res){
                $user = Db::name('users')->find($user_id);
                Session::set('user',$user);
                $this->user = $user;
                $re_url = Session::has('re_url') ? Session::get('re_url') : '/mobile/user/set_up';
                return json(['status'=>1,'msg'=>'邮箱绑定成功！', 'url'=> $re_url]);
            }

            return json(['status'=>0,'msg'=>'邮箱绑定成功，请重试！']);
            exit;
        }

        $this->assign('pass_id', md5(time().rand(1000,9999).rand(1000,9999)));
        return $this->fetch();
    }


     /**
     * 修改个人信息
     */
    public function edit_userInfo(){
        $t = isset($_POST['t']) && in_array($_POST['t'],['nickname','sex','mobile']) ? trim($_POST['t']) : '';
        $value = isset($_POST['value']) ? trim($_POST['value']) : '';
        $user_id = $this->user_id;
        if(!$user_id){
            return json(['status'=>0,'msg'=>'用户未登陆']);
        }
        if(!$t || $value == ''){
            return json(['status'=>0,'msg'=>'请求参数错误']);
        }
        $res = Db::name('users')->where('id', $user_id)->update([$t => $value]);
        if($res){
            $user = Db::name('users')->find($user_id);
            Session::set('user',$user);
            $this->user = $user;
            return json(['status'=>1,'msg'=>'操作成功！']);
        }else{
            return json(['status'=>0,'msg'=>'操作失败！']);
        }
        exit; 
    }
    /**
     * 修改用户头像
     */
    public function set_icon(){
        if(isset($_FILES['icon'])){
            $user_id = $this->user_id;
            if(!$user_id){
                return json(['status'=>0,'msg'=>'用户未登录！']);
            }
            $file = request()->file('icon');
            $files_dir = ROOT_PATH . 'public/images/user/icon/';
            
            $info = $file->move($files_dir, $this->user_id.'-icon.png');
            if($info){
                $src_dir = '/public/images/user/icon/';
                $savename = str_replace('\\','/',$info->getSaveName());
                $src_dir .= $savename.'?t='.time();
                Db::name('users')->where('id',$user_id)->update(['avatar'=>$src_dir]);
                $this->user['avatar'] =  $src_dir;
                echo "<script>parent.$('#avatarPic').attr('src','$src_dir');</script>";
            }
        }
        exit;
    }

    # 绑定 | 编辑 微信账号
    public function edit_wxaccount(){

        if($_POST){
            $sn = isset($_POST['sn']) ? trim($_POST['sn']) : '';
            if(!Session::has('edit_weixin_temp_sn') || Session::get('edit_weixin_temp_sn') != $sn || $sn == ''){
                return json(['status'=>0,'msg'=>'操作失败！']);
            }
            if(!Session::has('edit_weixin_account')){
                return json(['status'=>0,'msg'=>'操作失败！']);
            }
            if(!$this->user_id){
                return json(['status'=>0,'msg'=>'请先登录！']);
            }
            $e_data = Session::get('edit_weixin_account');
            $user = $this->user;
            $data['openid'] = $e_data['openid'];
            $data['unionid'] = $e_data['unionid'];
            if(!$user['avatar']){
                $data['avatar'] = $e_data['head_pic'];
            }
            
            $res = Db::name('users')->where('id',$this->user_id)->update($data);
            if($res){
                $user = Db::name('users')->find($this->user_id);
                $this->user = $user;
                Session::set('user',$user);

                Session::delete('edit_weixin_temp_sn');
                Session::delete('edit_weixin_account');
                
                return json(['status'=>1,'msg'=>'操作成功！']);
            }
            return json(['status'=>0,'msg'=>'操作失败！']);
            exit;
        }

        if(!$this->user_id){
            layer_error('请先登录！');
            exit;
        }
        if($this->user['openid']){
            layer_error('非法访问！');
            exit;
        }

        $data = $this->GetOpenid();
        Session::set('edit_weixin_account',['openid'=>$data['openid'], 'unionid'=>$data['unionid'],'head_pic' => $data['head_pic']]);
        
        $sn = order_sn();
        Session::set('edit_weixin_temp_sn', $sn);

        $this->assign('sn',$sn);
        $this->assign('data',$data);
        return $this->fetch();
    }

}