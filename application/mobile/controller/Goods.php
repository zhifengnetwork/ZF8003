<?php
namespace app\mobile\controller;

use app\mobile\controller\Base;
use think\Db;
use think\Request;
use think\Session;

class Goods extends Base
{
    public $user_id = 0;
    public function _initialize()
    {

        parent::_initialize();
        // $this->Verification_User();
        $this->user_id = Session::get('user.id');
    }
    public function index()
    {
        return $this->fetch();
    }
    /**
     * 分类列表显示
     */
    public function categoryList()
    {
        return $this->fetch();
    }
    /**
     * 商品列表显示
     */
    public function goodsList()
    {
        $where = [
            'status' => 1,
        ];
        $list = Db::name('goods')->where($where)->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 商品详情
     */
    public function goodInfo()
    {
        
        $id = input('id');
        $images = '';
        //  商品信息
        $info = Db::name('goods')->where('id', $id)->find();


        if ($info) {
            $image = explode(',', $info['image']);
            foreach ($image as $key => $value) {
                # code...
                $images[$key]['img'] = $value;
            }
            $user_id = $this->user_id;
            // 默认为未收藏
            $is_focus = 0;
            $where = [
                'goods_id' => $id,
                'user_id' => $user_id
            ];
            // 判断是否已经收藏
            $focus = Db::name('goods_focus')->where($where)->find();
            if (!empty($focus)) {
                // 已收藏
                $is_focus = 1;
            }
            
            $where = [
                'goods_id' => $id,
                'status' => 0,
            ];
            // 判断能否重复领取券在订单提交时判断，删除该数据就可以
            
            $coupon    = Db::name('goods_coupon')->where($where)->where('deadline', '>= time', time())->select();  
            $in_coupon = Db::query( "select `coupon_id` from `zf_user_coupon` where `user_id` = '$user_id' and `goods_id` = '$id' or `goods_id` = 0");
            // 用来判断用户是否已经领取优惠券
            $cp_ids = array_column($in_coupon, 'coupon_id');

            $this->assign('cp_ids', $cp_ids);
            $this->assign('coupon', $coupon);
            $this->assign('is_focus', $is_focus);
            $this->assign('images', $images);
            $this->assign('info', $info);
            return $this->fetch();
        }        

    }
    /**
     * 领券
     */
    public function get_coupon(){
        $data = input('post.');
        if(Session::has('user')){
            $user_id = Session::get('user.id');
        }else{
            Session::set('re_url', '/mobile/goods/goodinfo/id/'.$data['goods_id']);
            return json(['status'=>-1,'msg'=>'请登录']); 
        }        

        // 优惠券信息
        $coupon_info = Db::name('goods_coupon')->where('id',$data['coupon_id'])->find();
         
        if($coupon_info){
            // 判断是否已经领取
            $where=[
              'user_id'  => $user_id,
              'goods_id' => $data['goods_id'],
              'coupon_id'=> $data['coupon_id']
            ];            
            $result = Db::name('user_coupon')->where($where)->find();
            if($result){
               return json(['status'=>0,'msg'=>'您已领取此券']); 
            }

            // 添加券时间
            $addtime = $coupon_info['addtime'];
            // 券过期时间
            $etime = strtotime(date('Y-m-d H:i:s', $addtime + $coupon_info['term'] * 24 * 60 * 60));
            // 判断商品优惠券过期时间是否大于优惠券截止时间
            if ($etime > $coupon_info['deadline']) {
                $etime = $coupon_info['deadline'];
            }
            $data1 = [
                 'user_id'   => $user_id,
                 'coupon_id' => $coupon_info['id'],
                 'goods_id'  => $data['goods_id'],
                 'quota'     => $coupon_info['quota'],
                 'money'     => $coupon_info['money'], 
                 //优惠券加入时间   
                 'addtime'   => $addtime,
                 'stime'     => time(),
                 'etime'     => $etime
            ];

            $res = Db::name('user_coupon')->insert($data1); 
            if($res){
                return json(['status'=>1,'msg'=>'领取成功']);    
            }else{
                return json(['status'=>0,'msg'=>'领取失败']);   
            }
        }else{
            return json(['status'=>0,'msg'=>'没有此优惠券信息']);    
        }

    }


    # 确认订单
    public function check_order(){
        $goods_id = isset($_GET['goods_id']) ? intval($_GET['goods_id']) : 0;
        $info = Db::name('goods')->find($goods_id);
        if(!$info){
            layer_error('商品信息不存在或已下架！');
            exit;
        }
        if($info['status'] != 1 || $info['is_del'] == 1){
            layer_error('商品信息不存在或已下架！');
            exit;
        }
        if($info['is_stock'] == 1 && $info['stock'] < 1){
            layer_error('商品库存不足，无法购买！');
            exit;
        }

        # 用户验证
        if(!$this->user_id){
            Session::set('re_url', '/mobile/goods/check_order?goods_id='.$goods_id);
            $this->redirect('/mobile/index/login');
        }

        # 更新用户缓存
        $user = Db::name('users')->find($this->user_id);
        $this->user = $user;
        Session::set('user', $user);

        # 默认地址
        $area_address = array();
        $def_address_id = $this->user['default_address_id'];
        if($def_address_id){
            $def_address = Db::name('user_address')->find($def_address_id);
        }else{
            $def_address = Db::name('user_address')->where('user_id', $this->user_id)->find();
            Db::name('users')->where('id', $this->user_id)->update(['default_address_id' => $def_address['id']]);
        }
        if($def_address){
            $def_address_id = $def_address['id'];
            $area_address = [$def_address['province'],$def_address['city'],$def_address['district']];
        }

        # 运费
        $freight = $info['freight'];
        if($info['freight_temp'] > 0){
            $freight_temp = Db::name('freight_temp')->find($info['freight_temp']);
            if($freight_temp){
                $temp = json_decode($freight_temp['temp'],true);
                $freight = $temp['freight'];
                if($area_address){
                    foreach($temp as $tk => $tv){
                        if(in_array($tk, $area_address)){
                            $freight = $tv;
                        }
                    }
                }
            }
        }

        # 优惠券
        # 将过期的优惠券更新到已过期状态
        Db::name('user_coupon')->where(['etime' => ['<=', time()]])->update(['status' => 2]);
        # 我的优惠券
        $my_coupon = Db::query("select a.*,b.name from `zf_user_coupon` as a left join `zf_goods_coupon` as b on a.coupon_id = b.id where a.user_id = '$this->user_id' and a.status = 0");
        
        
        $this->assign('freight', $freight);
        $this->assign('my_coupon', $my_coupon);
        $this->assign('return', '/mobile/goods/goodInfo?id='.$goods_id);
        $this->assign('user', $this->user);
        $this->assign('def_address', $def_address);
        $this->assign('def_address_id', $def_address_id);
        $this->assign('info', $info);
        $this->assign('goods_id', $goods_id);
        return $this->fetch();
    }

    # 提交订单 | 订单详情
    public function order_info(){
        if($_POST){
            $goods_id = isset($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;
            $balance = isset($_POST['balance']) ? intval($_POST['balance']) : 0;
            $paypass = isset($_POST['paypass']) ? trim($_POST['paypass']) : '';
            $number = isset($_POST['number']) ? intval($_POST['number']) : 1;
            $note = isset($_POST['note']) ? addslashes($_POST['note']) : '';
            $coupon_id = isset($_POST['coupon_id']) ? intval($_POST['coupon_id']) : 0;
           
            $info = Db::name('goods')->find($goods_id);
            if(!$info){
                return json(['status'=>0,'msg'=>'商品信息不存在或已下架！']);
                exit;
            }
            if($info['status'] != 1 || $info['is_del'] == 1){
                return json(['status'=>0,'msg'=>'商品信息不存在或已下架！']);
                exit;
            }
            if($info['is_stock'] == 1 && $info['stock'] < $number){
                return json(['status'=>0,'msg'=>'商品库存不足，无法购买！']);
                exit;
            }
            if(!$this->user_id){
                return json(['status'=>0,'msg'=>'用户未登录']);
                exit;
            }
            
            # 用户信息
            $user = Db::name('users')->find($this->user_id);
            if(!$user['default_address_id']){
                return json(['status'=>0,'msg'=>'未设置收货地址']);
                exit;
            }
            
            # 支付密码判断
            if($balance){
                if(!$paypass){
                    return json(['status'=>0,'msg'=>'请输入支付密码！']);
                    exit;
                }
                if(pwd_encryption($paypass) != $user['payment_password']){
                    return json(['status'=>0,'msg'=>'支付密码错误！']);
                    exit;
                }
            }

            
            # 收货信息
            $area_address = array();
            $def_address = Db::name('user_address')->find($user['default_address_id']);
            $area_address = [$def_address['province'],$def_address['city'],$def_address['district']];
            
            
            # 运费
            $freight = $info['freight'];
            if($info['freight_temp'] > 0){
                $freight_temp = Db::name('freight_temp')->find($info['freight_temp']);
                if($freight_temp){
                    $temp = json_decode($freight_temp['temp'],true);
                    $freight = $temp['freight'];
                    if($area_address){
                        foreach($temp as $tk => $tv){
                            if(in_array($tk, $area_address)){
                                $freight = $tv;
                            }
                        }
                    }
                }
            }
            
        
            ### 状态
            #订单状态
            $order_status = 0;
            #支付状态
            $pay_status = 0;

            ### 价格
            # 商品总价
            $goods_price = $info['price'] * $number;
            # 运费
            $shipping_price = $freight;
            # 订单总价
            $total_amount = $goods_price + $shipping_price;
            # 优惠券抵扣
            $coupon_price = 0;
            # 应付金额
            $order_amount = $total_amount;

            if($coupon_id > 0){
                $coupon_info = Db::name('user_coupon')->where(['id'=>$coupon_id,'user_id'=>$this->user_id,'status'=>0])->find();
                if(!$coupon_info){
                    return json(['status'=>0,'msg'=>'优惠券信息读取失败，请刷新页面重试！']);
                    exit;
                }
                if($total_amount >= $coupon_info['quota']){
                    $coupon_price = $coupon_info['money'];

                    if($total_amount - $coupon_price > 0){
                        $order_amount = $total_amount - $coupon_price;
                    }else{
                        $order_amount = 0;
                    }
                }
            }

            
            # 余额抵扣
            $user_money = 0;
            if($balance && $user['money'] > 0){
                if($user['money'] >=  $order_amount){
                    $user_money = $order_amount;
                    $order_amount = 0;
                    $order_status = 1;
                    $pay_status = 1;
                }else{
                    $user_money = $user['money'];
                    $order_amount = $order_amount -  $user['money'];
                    $pay_status = 2;
                }
            }

            # 判断支付金额，修改订单状态
            if($order_amount == 0){
                $order_status = 1;
                $pay_status = 1;
            }

            # 时间
            $time = time();
            # 订单号
            $sn = order_sn();
            # 订单信息
            $order_data = [
                'order_sn' => $sn,
                'user_id' => $this->user_id,
                'goods_id' => $goods_id,
                'order_status' => $order_status,
                'pay_status' => $pay_status,
                'consignee' => $def_address['consignee'],
                'province' => $def_address['province'],
                'city' => $def_address['city'],
                'district' => $def_address['district'],
                'address' => $def_address['address'],
                'mobile' => $def_address['mobile'],
                'goods_price' => $goods_price,
                'number' => $number,
                'shipping_price' => $shipping_price,
                'user_money' => $user_money,
                'coupon_price' => $coupon_price,
                'order_amount' => $order_amount,
                'total_amount' => $total_amount,
                'add_time' => $time,
                'pay_time' => $pay_status == 1 ? $time : 0,
                'user_note' => $note,
            ];
            
            $o_res = Db::name('order')->insertGetId($order_data);
            if($o_res){

                if($coupon_id > 0){
                    # 删除优惠券
                    Db::name('user_coupon')->where('id', $coupon_id)->update(['status' => 1]);
                }

                if($user_money > 0){
                    $pay_name = $pay_status == 1 ? '已支付' : '部分支付';
                    $t_log = [
                        'user_id' => $this->user_id,
                        'type' => 'order',
                        'sn' => $sn,
                        'platform' => 'system',
                        'to' => $o_res,
                        'money' => $user_money,
                        'addtime' => $time,
                        'desc' => '订单：'.$o_res.' 使用了余额支付, 时订单状态为：'.$pay_name,
                        'init_time' => $time,
                    ];
                    Db::name('transaction_log')->insert($t_log);
                    Db::name('users')->where('id', $this->user_id)->setDec('money', $user_money);

                    $user_res = Db::name('users')->where('id',$this->user_id)->field('first_leader,level')->find();
                    if($user_res['level'] == 1){
                        Db::name('users')->where('id',$this->user_id)->setInc('level');
                    }
                    
                    if($user_res['first_leader']){
                        //佣金分成
                        commission($this->user_id ,$user_res['first_leader'] ,$o_res ,$user_money);

                        //升级
                        upgrade_level($this->user_id);
                    }

                }

                # 增加购买数量
                Db::name('goods')->where('id',$goods_id)->setInc('sold', $number);
                # 减库存
                if($info['is_stock'] == 1){
                    Db::name('goods')->where('id', $goods_id)->setDec('stock', $number);
                }

                # 更新缓存
                $user = Db::name('users')->find($this->user_id);
                Session::set('user', $user);
                $this->user = $user;

                $url = $pay_status != 1 ? '/mobile/weixin/pay?t=order_pay&id='.$o_res : '/mobile/goods/order_info?id='.$o_res;
                return json(['status'=>1,'msg'=>'订单提交成功！正在跳转...','url'=>$url]);
                exit;
            }
            return json(['status'=>0,'msg'=>'订单提交失败，请重试']);
            exit;
        }


        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $info = Db::query("select a.*,b.name,b.thumb from `zf_order` as a left join `zf_goods` as b on a.goods_id = b.id where a.id = '$id'");
        
        if(!$info){
            $this->error('订单信息不存在', '/mobile/user/index');
            exit;
        }
        $info = $info[0];
        if($info['user_id'] != $this->user_id){
            $this->error('非法访问！', '/mobile/user/index');
            exit;
        }

        $sname = [0=>'待付款',1=>'待发货',2=>'待收货',3=>'待评价',4=>'已完成'];
        
        $province = $info['province'] ? Db::name('area')->where('id',$info['province'])->value('name') : '';
        $city = $info['city'] ? Db::name('area')->where('id',$info['city'])->value('name') : '';
        $district = $info['district'] ? Db::name('area')->where('id',$info['district'])->value('name') : '';
        $twon = $info['twon'] ? Db::name('area')->where('id',$info['twon'])->value('name') : '';
        $info['address'] = $province . $city . $district . $twon . $info['address'];
        
        $this->assign('sname', $sname);
        $this->assign('info', $info);
        return $this->fetch();
    }


    public function focus()
    {
        $data = input('post.');
        if(Session::has('user')){
            // $this->user = Session::get('user');
            $user_id = Session::get('user.id');
        }else{
            Session::set('re_url', '/mobile/goods/goodinfo/id/'.$data['id']);
            return json(['status'=>-1,'msg'=>'请登录']); 
        }    


        // $admin_id = session('admin_id');
        $user_id = $this->user_id;
        $where = [
            'goods_id' => $data['id'],
            'user_id' => $user_id,

        ];
        // 判断是否已收藏
        if ($data['act'] == 'focus') {

            $where['addtime'] = time();
            $res = Db::name('goods_focus')->insert($where);
            if ($res) {
                return json(['status' => 1, 'msg' => '收藏成功']);
            } else {
                return json(['status' => 0, 'msg' => '收藏失败']);
            }            
        } else {
            $res = Db::name('goods_focus')->where($where)->delete();
            if ($res) {
                return json(['status' => 1, 'msg' => '取消成功']);
            } else {
                return json(['status' => 0, 'msg' => '取消失败']);
            }              
        }


    }

    /**
     * 通过IP获取对应城市信息(该功能基于淘宝第三方IP库接口)
     * @param $ip IP地址,如果不填写，则为当前客户端IP
     * @return  如果成功，则返回数组信息，否则返回false
     */
    function getIpInfo($ip)
    {
        $url = 'http://ip.taobao.com/service/getIpInfo.php?ip=' . $ip;
        $result = file_get_contents($url);
        $result = json_decode($result, true);
        if ($result['code'] !== 0 || !is_array($result['data'])) return false;
        return $result['data'];
    }
}
