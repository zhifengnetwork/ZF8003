<?php
namespace app\mobile\controller;

use app\mobile\controller\Base;
use think\Db;
use think\Request;
use think\Session;

class Goods extends Base
{
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
        $temp_price = 0;
        $info = Db::name('goods')->where('id', $id)->find();
        // 获取地址  
        // $request = Request::instance();
        // $ip = $request->ip();
        // $ip = '119.131.60.165';
        // $ip_info = $this->getIpInfo($ip);

        // // 拼接地址省市
        // $address = $ip_info['region'] . $ip_info['city'];

        if ($info) {
            $image = explode(',', $info['image']);
            foreach ($image as $key => $value) {
                # code...
                $images[$key]['img'] = $value;
            }
        }
        // $admin_id = session('admin_id');
        $user_id = 1;
        $is_focus = 0;
        $where = [
            'goods_id' => $info['id'],
            'user_id' => $user_id
        ];
        // 判断是否已经收藏
        $focus = Db::name('goods_focus')->where($where)->find();
        if (!empty($focus)) {
            // 已收藏
            $is_focus = 1;
        }
        $this->assign('is_focus', $is_focus);
        $this->assign('temp_price', $temp_price);
        // $this->assign('address', $address);
        $this->assign('images', $images);
        $this->assign('info', $info);
        return $this->fetch();
    }

    public function order()
    {
        // 商品id 
        $id = input('id');
        $user_id = 28;
        $price = 0;
        $get_address = [
            'province' => '',
            'city'     => '',
            'district' => '',
            'address'  => '',
            'dizhi'    => ''
        ];
        // 商品信息
        $res = Db::name('goods')->where('id', $id)->find();
        if($res){
            // 用户信息
            $where = [
                'user_id'    => $user_id,
                'is_default' => 1
            ];
            $user_info1 = Db::name('users')->where('id',$user_id)->find();
            if($user_info1){
                $address1   = Db::name('user_address')->where($where)->find();
               
                if($address1){
                    $province = Db::name('area')->where('id', $address1['province'])->find();
                    $city     = Db::name('area')->where('id', $address1['city'])->find();
                    $district = Db::name('area')->where('id', $address1['district'])->find();
                    $dizhi    = $province['name'] . $city['name'] . $district['name'] . $address1['address'];

                    $get_address = [
                        'province' => $address1['province'],
                        'city'     => $address1['city'],
                        'district' => $address1['district'],
                        'address'  => $address1['address'],
                        'dizhi'    => $dizhi
                    ];

                    // 计算邮费
                    $freight_temp = Db::name('freight_temp')->where('id', $res['freight_temp'])->find();
                    $temp = json_decode($freight_temp['temp'], true);

                    // 判断是否存在运费模板
                    if ($freight_temp) {
                        $num = count($temp);
                        //  判断是否设置特定地区运费
                        if ($num == 1) {
                            $price = $temp['freight'];
                        } else {
                            // 模板地址
                            $m = array_keys($temp);
                            // 判断用户地区id是否等于模板的id 
                            $price = $temp['freight'];
                            // 用户地址 
                            $a['1'] =  ['address' => $address1['province']];
                            $a['2'] =  ['address' => $address1['city']];
                            $a['3'] =  ['address' => $address1['district']];

                            foreach ($a as $key => $value) {
                                for ($i = 0; $i < count($m); $i++) {
                                    if ($value['address'] == $m[$i]) {
                                        $price = $temp[$value['address']];
                                    }
                                }
                            }
                        }
                    } else {
                        $price = $res['freight'];
                    }
                }
            }
        
        // 地址  
        $this->assign('user_info', $user_info1);
        $this->assign('get_address',$get_address);
        // $this->assign('dizhi', $dizhi);
        $this->assign('info', $res);
        $this->assign('s_price', $price);
        return $this->fetch();
        }else{
           return $this->fetch('index/login'); 
        }
    }

    public function focus()
    {
        $data = input('post.');
        // $admin_id = session('admin_id');
        $user_id = 1;
        $where = [
            'goods_id' => $data['id'],
            'user_id' => $user_id,

        ];
        // 判断是否已收藏
        if ($data['act'] == 'focus') {

            $where['addtime'] = time();
            $res = Db::name('goods_focus')->insert($where);
        } else {
            $res = Db::name('goods_focus')->where($where)->delete();
        }

        if ($res) {
            return json(['status' => 1, 'msg' => '操作成功']);
        } else {
            return json(['status' => 0, 'msg' => '操作失败']);
        }
    }

    public function pay(){
        return $this->fetch();
    }

    
    public function orderForm()
    {
        $data = input('post.');
        if($_POST){
            if(empty($data['province'])){
                return json(['status' => -1, 'msg' => '请设置默认收货地址']);   
            }
            if(!$data['pay_password']){
                return json(['status'=>0,'msg'=>'请输入支付密码']);
            }

            // 判断支付密码
            $u_pwd = Db::name('users')->where('id',$data['user_id'])->value('payment_password'); 
            //   此处密码还需加密
            if($u_pwd!=$data['pay_password']){
                return json(['status'=>0,'msg'=>'密码错误']);
            }
            // 判断余额是否足够
            if($data['remain'] < $data['order_amount']){
            return json(['status' => 0, 'msg' => '余额不足请充值']);  
            } 

            $data1 = $this->form_data($data);
            
            $res = Db::name('order')->insert($data1); 
            if($res){
                return json(['status' => 1, 'msg' => '提交成功，正在跳转...']);
            }else{
                return json(['status' => 0, 'msg' => '提交失败']);
            }
        } 
    } 

    public function form_data($data){
        $order_sn = order_sn();

        $data1 = [
            "order_sn"  =>  $order_sn,
            "user_id"   =>  $data['user_id'],
            "consignee" =>  $data['nickname'],
            "province"  =>  $data['province'],
            // "user_money"=>  $data['user_money'],
            "city"      =>  $data['city'],
            "district"  =>  $data['district'],
            "address"   =>  $data['address'],
            "goods_price"    =>  $data['goods_price'],
            "shipping_price" => $data['shipping_price'],
            "order_amount"   =>  $data['order_amount'],
            "total_amount"   =>  $data['total_amount'],
            "user_note" =>  $data['user_note'],
            "pay_time"  =>  time()
        ];
        return $data1;
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
