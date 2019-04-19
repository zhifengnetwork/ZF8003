<?php
namespace app\mobile\controller;
use think\Db;
use think\Session;

class Weixin extends Base{

    public function _initialize()
    {
        Session::set('re_url','/mobile/user/top_up');
        $this->Verification_User();

    }

    public function pay(){

        # 操作类型
        $t = isset($_GET['t']) ? trim($_GET['t']) : '';
        $money = isset($_GET['money']) ? Digital_Verification($_GET['money']) : 0;
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        
        
        if(!$t){
            layer_error('参数错误！');
            exit;
        }

        # 标识符，订单号
        $sn = get_rand_str(22,1,1);
        switch($t){
            # 账号充值
            case 'recharge':
                if($money <= 0){
                    layer_error('请输入充值金额');
                    exit;
                }
                $data = [
                    'sn'    =>  $sn,
                    'user_id'   =>  $this->user_id,
                    'money' =>  $money,
                    'body'  =>  '账户充值',
                    'attach'    =>  '会员账户充值：'.$money,
                    'addtime'   =>  time(),
                    'type'  =>  $t,
                ];
                $res = Db::name('wxpay_cache')->insert($data);
                if($res){
                    if(is_weixin()){
                        $view = 'jsapi_recharge';
                        $this->assign('sn',$sn);
                        $this->jsapi_recharge($data);
                    }else{
                        layer_error('请在微信浏览器上操作！');
                        exit;
                    }
                    return $this->fetch($view);
                }
                layer_error('系统错误，请重试！');
                break;
            # 订单支付
            case 'order_pay':
                $info = Db::query("select a.*,b.name,b.thumb from `zf_order` as a left join `zf_goods` as b on a.goods_id = b.id where a.id = '$id'");
                
                if(!$info){
                    layer_error('订单信息不存在',true,'/mobile/user/my_order');
                    exit;
                }
                $info = $info[0];
                if($info['user_id'] != $this->user_id){
                    layer_error('非法访问！',true,'/mobile/user/my_order');
                    exit;
                }
                if($info['pay_status'] == 1){
                    layer_error('订单已支付，请勿重复支付！',true,'/mobile/user/my_order');
                    exit;
                }
                if(!is_weixin()){
        
                    layer_error('请在微信浏览器上操作！',true,'/mobile/user/my_order');
                    exit;
                }

                $data = [
                    'sn'    =>  $sn,
                    'user_id'   =>  $this->user_id,
                    'money' =>  $info['order_amount'],
                    'body'  =>  '订单支付',
                    'attach'    =>  '订单支付:'.$info['id'],
                    'addtime'   =>  time(),
                    'type'  =>  $t,
                    'order_id' => $id,
                ];
                $res = Db::name('wxpay_cache')->insert($data);
                if($res){
                    
                    $view = 'jsapi_orderpay';
                    $this->assign('order_id',$id);
                    $this->assign('sn',$sn);
                    $this->jsapi_orderpay($data);
                    return $this->fetch($view);
                }
                layer_error('系统错误，请重试！');
                break;
        }

        exit;
    }

    # jsapi 订单支付
    public function jsapi_orderpay($data){
        $user = $this->user;
        $openid = $user['openid'];
        if(!$openid){
            $this->user_wxinfo_completion();
            $openid = $this->user['openid'];
        }
        
        require_once ROOT_PATH."plugins/pay/weixinpay/lib/WxPay.Api.php";
        require_once ROOT_PATH."plugins/pay/weixinpay/WxPay.JsApiPay.php";
        require_once ROOT_PATH."plugins/pay/weixinpay/WxPay.Config.php";

        $tools = new \JsApiPay();
        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $config = new \WxPayConfig();
        $input->SetBody($data['body']);
        $input->SetAttach($data['attach']);
        $input->SetOut_trade_no($data['sn']);
        $input->SetTotal_fee($data['money'] * 100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetNotify_url($_SERVER["REQUEST_SCHEME"].'://'.$_SERVER['HTTP_HOST'].'/mobile/weixin/jsapi_notify_url');
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openid);
        $order = \WxPayApi::unifiedOrder($config, $input);

        $jsApiParameters = $tools->GetJsApiParameters($order);
        //获取共享收货地址js函数参数
        $editAddress = $tools->GetEditAddressParameters();
       
        $this->assign('jsApiParameters', $jsApiParameters);
        $this->assign('editAddress', $editAddress);
    }

    public function order_pay_notify(){

        $sn = isset($_POST['sn']) ? trim($_POST['sn']) : '';
        if($sn){
            require_once ROOT_PATH."plugins/pay/weixinpay/lib/WxPay.Api.php";
            require_once ROOT_PATH."plugins/pay/weixinpay/WxPay.Config.php";

            $input = new \WxPayOrderQuery();
            $input->SetOut_trade_no($sn);
            $config = new \WxPayConfig();
            $res = \WxPayApi::orderQuery($config, $input);
            if($res && $res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS'){
                $info = Db::name('wxpay_cache')->where(['sn' => $sn, 'type' => 'order_pay'])->find();
                if($info){
                    if($res['trade_state'] == 'SUCCESS'){
                        $order_id = $info['order_id'];
                        $time = time();
                        $udata = [
                            'pay_status'=>1,
                            'order_status'=>1,
                            'pay_time' => $time,
                            'transaction_id' => isset($res['transaction_id']) ? $res['transaction_id'] : '',
                        ];

                        $transaction = [
                            'user_id' => $info['user_id'],
                            'type' => 'order',
                            'sn' => $sn,
                            'transaction_id' => isset($res['transaction_id']) ? $res['transaction_id'] : '',
                            'platform' => 'weixin',
                            'trade_type' => isset($res['trade_type']) ? $res['trade_type'] : '',
                            'to' => $info['order_id'],
                            'money' => $info['money'],
                            'addtime' => $time,
                            'desc' => $info['attach'],
                            'init_time' => $info['addtime'],
                        ];

                        Db::name('order')->where('id', $order_id)->update($udata);
                        Db::name('transaction_log')->insert($transaction);
                        Db::name('wxpay_cache')->where('sn', $sn)->delete();

                        return json(['status'=>1]);
                    }

                }

            }

        }

        return json(['status'=>0]);

    }

    # jsapi 充值
    public function jsapi_recharge($data){

        $user = $this->user;
        $openid = $user['openid'];
        if(!$openid){
            $this->user_wxinfo_completion();
            $openid = $this->user['openid'];
        }
        
        require_once ROOT_PATH."plugins/pay/weixinpay/lib/WxPay.Api.php";
        require_once ROOT_PATH."plugins/pay/weixinpay/WxPay.JsApiPay.php";
        require_once ROOT_PATH."plugins/pay/weixinpay/WxPay.Config.php";

        $tools = new \JsApiPay();
        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $config = new \WxPayConfig();
        $input->SetBody($data['body']);
        $input->SetAttach($data['attach']);
        $input->SetOut_trade_no($data['sn']);
        $input->SetTotal_fee($data['money'] * 100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetNotify_url($_SERVER["REQUEST_SCHEME"].'://'.$_SERVER['HTTP_HOST'].'/mobile/weixin/jsapi_notify_url');
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openid);
        $order = \WxPayApi::unifiedOrder($config, $input);

        $jsApiParameters = $tools->GetJsApiParameters($order);
        //获取共享收货地址js函数参数
        $editAddress = $tools->GetEditAddressParameters();
       
        $this->assign('jsApiParameters', $jsApiParameters);
        $this->assign('editAddress', $editAddress);
        // require_once ROOT_PATH."application/mobile/view/weixin/jsapi_recharge.html";
    }




}


