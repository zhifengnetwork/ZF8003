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

    /**
     * 我的订单
     */
    public function my_order()
    {
        return $this->fetch();
    }

    /**
     * 我的分销
     */
    public function distribution()
    {
        return $this->fetch();
    }

    /**
     * 我的团队
     */
    public function team_list()
    {
        return $this->fetch();
    }

    /**
     * 佣金记录
     */
    public function commission()
    {
        return $this->fetch();
    }

    /**
     * 业绩明细
     */
    public function performance()
    {
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
            if($config['withdrawal_fee']){
                $fee = $money * ($config['withdrawal_fee'] * 0.01);
                
                if($config['withdrawal_fee_max'] && $fee > $config['withdrawal_fee_max']){
                    $fee = $config['withdrawal_fee_max'];
                }
                if($config['withdrawal_fee_min'] && $fee < $config['withdrawal_fee_min']){
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
            $res = Db::name('withdrawal')->insert($inser_data);
            if($res){
                return json(['status'=>1]);
            }

            exit;
        }



        if(!array_key_check($config,'withdrawal', true)){
            layer_error('管理员未开启提现功能！');
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
        }else{
            $where .= " and `type` in ('recharge','order')";
        }
        $sql = "select `sn`,`money`,`addtime` from `zf_transaction_log` $where order by `id` desc";
        $lists = Db::execute($sql);

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
        $user_id = $this->user_id;
        $address_list = Db::name('user_address')->where('user_id',$user_id)->select();
        $list = $this->address_join($address_list);
        
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
                $info['key'] = $info['province'] . "-" . $info['province'] . "-" . $info['province'];
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
        // 删除收藏
        if($_POST){
            $goods_id = input('post.goods_id');
            $where = [
                'goods_id' => $goods_id,
                'user_id'  => $user_id
            ];
            $del_foc = Db::name('goods_focus')->where($where)->delete();
            if($del_foc){
                return json(['status'=>1,'msg'=>'删除成功']);
            }else{
                return json(['status'=>0,'msg'=>'删除失败']);
            }
        }

        $list = Db::name('goods_focus')
            ->alias('g')
            ->join('goods go', 'g.goods_id = go.id')
            ->where('user_id', $user_id)
            // ->field('u.*,g.name')
            ->select();   
        $this->assign('list', $list);
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
        return $this->fetch();
    }

    /**
     * 我的优惠券
     */
    public function coupons()
    {
        $user_id = $this->user_id;
        $where = [
           'user_id' => $user_id,
           'u.status'  => 0
        ];
        // 未使用未过期的优惠券
        $list = Db::name('user_coupon')
                ->alias('u')
                ->join('goods_coupon g', 'u.coupon_id = g.id')
                ->where($where)
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
        $user = Db::name('users')->field('sex,mobile,nickname,avatar')->find($this->user_id);
        $user['sex_name'] = [0=>'保密',1=>'男',2=>'女'][$user['sex']]; 

        $this->assign('user',$user);
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
}