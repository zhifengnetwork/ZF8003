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
        
        
        if(!$t){
            layer_error('参数错误！');
            exit;
        }
        if($money <= 0){
            layer_error('请输入充值金额');
            exit;
        }

        # 标识符，订单号
        $sn = get_rand_str(22,1,1);
        switch($t){
            # 账号充值
            case 'recharge':
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
                        $this->jsapi_recharge($data);
                    }else{
                        $view = 'web_recharge';
                        $this->web_recharge($data);
                    }
                    return $this->fetch($view);
                }
                layer_error('系统错误，请重试！');
                break;
        }

        exit;
    }


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

    public function jsapi_notify_url(){

        $data = input('get.');




    }



}


