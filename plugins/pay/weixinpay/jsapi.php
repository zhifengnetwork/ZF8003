<?php 
/**
*
* example目录下为简单的支付样例，仅能用于搭建快速体验微信支付使用
* 样例的作用仅限于指导如何使用sdk，在安全上面仅做了简单处理， 复制使用样例代码时请慎重
* 请勿直接直接使用样例对外提供服务
* 
**/


require_once ROOT_PATH."plugins/pay/weixinpay/lib/WxPay.Api.php";
require_once "WxPay.JsApiPay.php";
require_once "WxPay.Config.php";
require_once 'log.php';

//初始化日志
$log_path = ROOT_PATH.'public/wxpay_logs/';
if(!file_exists($log_path)){
	@mkdir($log_path,0777,true);
}
$logHandler= new CLogFileHandler($log_path.date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);


try{

	$tools = new JsApiPay();
	// $openId = $tools->GetOpenid();

	//②、统一下单
	$input = new WxPayUnifiedOrder();
	$input->SetBody("test");
	$input->SetAttach("test");
	$input->SetOut_trade_no("sdkphp".date("YmdHis"));
	$input->SetTotal_fee("1");
	$input->SetTime_start(date("YmdHis"));
	$input->SetTime_expire(date("YmdHis", time() + 600));
	$input->SetGoods_tag("test");
	$input->SetNotify_url("http://paysdk.weixin.qq.com/notify.php");
	$input->SetTrade_type("JSAPI");
	$input->SetOpenid($openid);
	$config = new WxPayConfig();
	$order = WxPayApi::unifiedOrder($config, $input);

	$jsApiParameters = $tools->GetJsApiParameters($order);
	//获取共享收货地址js函数参数
	$editAddress = $tools->GetEditAddressParameters();
} catch(Exception $e) {
	Log::ERROR(json_encode($e));
}

?>
