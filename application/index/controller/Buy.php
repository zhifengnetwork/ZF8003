<?php
namespace app\index\controller;
use think\Db;
use think\Session;

class Buy extends Base
{
    public function index()
    {
        return $this->redirect('index/buy/buy');
    }

    # 商品列表
    public function buy()
    {
        $where = [
            'is_del' => 0,
            'status' => 1
        ];
        $where['type'] = ['neq',6];
        $list = Db::name('goods')->where($where)->order('addtime desc')->paginate(16);
        $this->assign('list',$list);
        return $this->fetch();
    }

    # 商品详情
    public function details()
    { 
       
        $id = input('get.id');
        $info = Db::name('goods')->where('id', $id)->find();
        if(!$info){
            layer_error('商品信息不存在或已下架');
            exit;
        }
        
        if($info['status'] == 2){
            layer_error('商品信息不存在或已下架');
            exit;
        }
        $info['attr'] = json_decode($info['second_title'], true);
        $this->assign('list', $info);
        return $this->fetch();
    }


    # 提交订单
    public function submit_order()
    {
        $id = input('get.id');
        $info = Db::name('goods')->where('id', $id)->find();
        if(!$info){
            layer_error('商品信息不存在或已下架');
            exit;
        }

        $user = $this->user;
        if(!$user){
            Session::set('re_url', '/index/buy/submit_order?id='.$id);
            $this->redirect('/index/login/login');
        }

        # 地址 - 省
        $province = Db::name('area')->field('`id`,`name`')->where('parent_id', 0)->select();

        # 用户默认收货地址
        $default_address_id = Db::name('users')->where('id',$this->user_id)->value('default_address_id');
        if($default_address_id > 0){
            $default_address = Db::name('user_address')->where('id', $default_address_id)->find();
            if($default_address){
                $default_address['city_name'] = Db::name('area')->field('name')->where('id', $default_address['city'])->find()['name'];
                $default_address['district_name'] = Db::name('area')->field('name')->where('id', $default_address['district'])->find()['name'];
            }
        }else{
            $default_address = Db::name('user_address')->order('id DESC')->find();
            if($default_address){
                $default_address['city_name'] = Db::name('area')->field('name')->where('id', $default_address['city'])->find()['name'];
                $default_address['district_name'] = Db::name('area')->field('name')->where('id', $default_address['district'])->find()['name'];
            }
        }

        
        $this->assign('province', $province);
        $this->assign('default_address', $default_address);
        $this->assign('info',$info); 
        return $this->fetch();
    }


    # 确认订单 | 生成订单转支付
    public function check_order(){

        if($_POST['data']){
            $data = $_POST['data'];
            
            # 用户
            $user_id = Session::has('user') ? Session::get('user.id') : 0;

            # 商品信息
            $info = Db::name('goods')->where('id', $data['goods_id'])->find();
            
            if(!$info){
                return json(['status'=>0,'msg'=>'订单提交失败，商品信息不存在或已下架！']);
                exit;
            }
            if($info['status'] != 1 || $info['is_del'] == 1){
                return json(['status'=>0,'msg'=>'订单提交失败，商品信息不存在或已下架！']);
                exit;
            }
            if($info['is_stock'] == 1 && $info['stock'] < $data['number']){
                return json(['status'=>0,'msg'=>'订单提交失败，商品库存不足，无法购买！']);
                exit;
            }
            if(!$this->user_id){
                return json(['status'=>0,'msg'=>'订单提交失败，未检测到用户信息，请先登录']);
                exit;
            }

            if(!$data['address']){
                return json(['status'=>0,'msg'=>'详细地址必须填写']);
                exit;
            }

            if($data['mobile']){
                if(!preg_match("/^1[34578]\d{9}$/", $data['mobile'])){
                    return json(['status'=>0,'msg'=>'手机号码格式不正确！']); 
                }
            }

            if($data['email']){
                if(!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/", $data['email'])){
                    return json(['status'=>0,'msg'=>'邮箱格式不正确！']); 
                }
            }
            # 运费
            $freight = $info['freight'];
            if($info['freight_temp'] > 0){
                $freight_temp = Db::name('freight_temp')->find($info['freight_temp']);
                if($freight_temp){
                    $temp = json_decode($freight_temp['temp'],true);
                    $freight = $temp['freight'];
                    foreach($temp as $tk => $tv){
                        if(in_array($tk, [ $data['province'],$data['city'],$data['district'] ])){
                            $freight = $tv;
                        }
                    }
                }
            }
            
            # 状态
            $order_status = 0;
            $pay_status = 0;

            ### 价格
            # 商品总价
            $goods_price = $info['price'] * $data['number'];
            # 运费
            $shipping_price = $freight;
            # 订单总价
            $total_amount = $goods_price + $shipping_price;
            # 应付金额
            $order_amount = $total_amount;
            # 时间
            $time = time();
            # 订单号
            $sn = order_sn();

            if($order_amount == 0){
                $order_status = 1;
                $pay_status = 1;
            }

            # 填充数据
            $data['order_sn'] = $sn;
            $data['user_id'] = $user_id;
            $data['order_status'] = $order_status;
            $data['pay_status'] = $pay_status;
            $data['goods_price'] = $goods_price;
            $data['shipping_price'] = $shipping_price;
            $data['total_amount'] = $total_amount;
            $data['order_amount'] = $order_amount;
            $data['add_time'] = $time;

            $res = Db::name('order')->insertGetId($data);
            if($res){
                # 增加购买数量
                Db::name('goods')->where('id',$data['goods_id'])->setInc('sold', $data['number']);
                # 减库存
                if($info['is_stock'] == 1){
                    Db::name('goods')->where('id', $data['goods_id'])->setDec('stock', $data['number']);
                }

                $url = '/index/buy/order_pay?id='.$res;
                if($order_status == 1){
                    # 当交易金额为 0 时，自动跳过支付，写入空交易记录
                    $url = '/index/buy/order_info?id='.$res;
                    $transaction = [
                        'user_id' => $user_id,
                        'type' => 'order',
                        'sn' => $sn,
                        'transaction_id' => '',
                        'platform' => 'system',
                        'trade_type' => '',
                        'to' => $res,
                        'money' => $order_amount,
                        'addtime' => $time,
                        'desc' => '',
                        'init_time' => $time,
                    ];
                    Db::name('transaction_log')->insert($transaction);
                }
                return json(['status'=>1,'msg'=>'订单提交成功，正在跳转...', 'url'=>$url]);
                exit;
            }else{
                return json(['status'=>0,'msg'=>'订单提交失败，请重试！']);
                exit;
            }
        }
        exit;
    }


    # 订单支付
    public function order_pay(){
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        # 用户判断
        Session::set('re_url', '/index/buy/order_pay?id='.$id);
        $this->Verification_User();
        $user = $this->user;
        
        # 订单信息
        $info = Db::query("select a.*,b.name from `zf_order` as a left join `zf_goods` as b on a.goods_id = b.id where a.id = '$id' and a.user_id = '$this->user_id'");
        if(!$info){
            layer_error('订单信息不存在');
            exit;
        }
        $info = $info[0];
        if($info['order_status'] > 0){
            layer_error('订单已完成支付，请勿重复支付订单！',true,'/index/buy/order_info?id='.$id);
            exit;
        }

        # 标识符，订单号
        $sn = get_rand_str(22,1,1);
        $data = [
            'sn'    =>  $sn,
            'user_id'   =>  $this->user_id,
            'money' =>  $info['order_amount'],
            'body'  =>  '订单支付',
            'attach'    =>  '订单支付:'.$info['id'],
            'addtime'   =>  time(),
            'type'  =>  'order_pay',
            'order_id' => $id,
        ];
        
        # 微信支付缓存
        $res = Db::name('wxpay_cache')->insert($data);
        if(!$res){
            layer_msg('订单发起失败，请重试！',true,'/index/buy/order_info?id='.$id);
            exit;
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
        $input->SetNotify_url($_SERVER["REQUEST_SCHEME"].'://'.$_SERVER['HTTP_HOST'].'/index/weixin/native_notify');
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id($info['order_sn']);
        $order = \WxPayApi::unifiedOrder($config, $input);
        
        if( (!$order['return_code'] || $order['return_code'] != 'SUCCESS') || (!$order['result_code'] || $order['result_code'] != 'SUCCESS')){
            layer_error('微信支付订单发起失败，请重试！',true,'/index/buy/order_info?id='.$id);
            exit;
        }

        $this->assign('code_url', $order['code_url']);
        $this->assign('info', $info);
        $this->assign('sn',$sn);
        return $this->fetch(); 
    }
    
    # 生成微信支付二维码
    public function create_wx_qrcode($url = ''){
        if(!$url){
            return false;
        }
        include ROOT_PATH.'/vendor/phpqrcode/phpqrcode.php';
        
        return \QRcode::png($url);
        exit;

        
    }

    # 异步订单交易结果查询
    public function ajax_order_pay_status(){

        $sn = isset($_POST['sn']) ? trim($_POST['sn']) : '';
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        // dump([$sn,$order_id]);exit;
        # 订单查询
        if($order_id){
            $order_info = Db::name('order')->field('id,pay_status')->find($order_id);
            if($order_info && $order_info['pay_status'] == 1){
                return json(['status'=>1]);
                exit;
            }
        }
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

                        $user_res = Db::name('users')->where('id',$info['user_id'])->field('first_leader,level')->find();
                        if($user_res['level'] == 1){
                            Db::name('users')->where('id',$info['user_id'])->setInc('level');
                        }
                        
                        if($user_res['first_leader']){
                            //佣金分成
                            commission($info['user_id'] ,$user_res['first_leader'] ,$info['order_id'] ,$info['money']);

                            //升级
                            upgrade_level($info['user_id']);
                        }

                        return json(['status'=>1]);
                    }

                }
            }
        }
    }

    # 我的订单
    public function my_order(){
        # 用户判断
        Session::set('re_url', '/index/buy/my_order');
        $this->Verification_User();

        $where['user_id'] = ['=', $this->user_id];

        $lists = Db::name('order')->where($where)->order('order_status asc, add_time desc')->paginate(3);
        $list = array();
        foreach($lists as $k => $v){
            $list[$k] = $v;
            $goods = Db::name('goods')->field('name,thumb')->find($v['goods_id']);
            $list[$k]['name'] = $goods['name'];
            $list[$k]['thumb'] = $goods['thumb'];
        }

        $sname = [0=>'待付款', 1=>'待发货', 2=>'待收货', 3=>'待评价', 4=>'交易完成'];

        $this->assign('sname',$sname);
        $this->assign('list',$list);
        $this->assign('lists',$lists);
        return $this->fetch();
    }


    # 订单详情
    public function order_info(){
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        # 用户判断
        Session::set('re_url', '/index/buy/order_info?id='.$id);
        $this->Verification_User();

        # 订单信息
        $info = Db::query("select a.*,b.name,b.thumb from `zf_order` as a left join `zf_goods` as b on a.goods_id = b.id where a.id = '$id' and a.user_id = '$this->user_id'");

        if(!$info){
            layer_error('订单信息不存在');
            exit;
        }

        $info = $info[0];

        $info['address_info'] = Db::name('area')->where('id',$info['province'])->value('name');
        $info['address_info'] .= ' '.Db::name('area')->where('id',$info['city'])->value('name');
        $info['address_info'] .= ' '.Db::name('area')->where('id',$info['district'])->value('name');
        $info['address_info'] .= ' '.$info['address'];


        $sname = [0=>'待付款', 1=>'待发货', 2=>'待收货', 3=>'待评价', 4=>'交易完成'];

        
        $this->assign('info',$info);
        $this->assign('sname',$sname);
        return $this->fetch();
    }


    # 确认收货
    public function confirm_order(){
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $user_id = $this->user_id;
        if(!$user_id){
            return json(['status'=>0,'msg'=>'用户未登录']);
            exit;
        }
        
        $info = Db::name('order')->where(['id'=>$id,'user_id'=>$user_id])->field('order_sn,order_status')->find();
        if(!$info){
            return json(['status'=>0,'msg'=>'订单信息不存在！']);
            exit;
        }
        if($info['order_status'] != 2){
            return json(['status'=>0,'msg'=>'订单状态错误，请刷新页面！']);
            exit;
        }
        $res = Db::name('order')->where('id',$id)->update(['order_status' => 3]);
        if($res){
            return json(['status'=>1,'msg'=>'订单状态更新成功！']);
            exit;
        }else{
            return json(['status'=>0,'msg'=>'订单状态更新失败，请重试！']);
            exit;
        }
    }
}