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



    public function order()
    {
        // 商品id 
        $id = input('id');
        if(Session::has('user')){
            $user_id = Session::get('user.id');
        }else{
            return $this->fetch('index/login');
        }  
        $price = 0;
        $get_address = [
            'province' => '',
            'city'     => '',
            'district' => '',
            'address'  => '',
            'dizhi'    => '',
            'consignee'=> '',
            'mobile'   => ''
        ];
        // 商品信息
        $res = Db::name('goods')->where('id', $id)->find();
        if($res){
            $user_info1 = Db::name('users')->where('id',$user_id)->find();
            if($user_info1){
                // 用户信息
                $where = [
                    'user_id'    => $user_id,
                    // 默认收货地址
                    'is_default' => 1
                ];                
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
                        'dizhi'    => $dizhi,
                        'consignee'=> $address1['consignee'],
                        'mobile'   => $address1['mobile']
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
                    
                    //显示未过期优惠券

                    $where1 = [
                         'goods_id' => $id,
                         'user_id'  => $user_id,
                         'status'   => 0 ,
                    ];
                    // $where2 = [
                    //     'goods_id' => $id,
                    //     'status'   => 0,
                    // ];

                    $coupon_list = Db::name('user_coupon')->where($where1)->where('etime', '>= time', time())->select();
                    $cname[0] = '';
                    
                    if ($coupon_list) {
                        foreach ($coupon_list as $v) {
                            $cids[] = $v['coupon_id'];
                        }
                        if (isset($cids)) {
                            $cids = implode("','", $cids);
                            $cids = Db::query("select `id`,`name` from `zf_goods_coupon` where `id` in ('$cids')");
                            foreach ($cids as $c) {
                                $cname[$c['id']] = $c['name'];
                            }
                        }
                    }
                    $this->assign('cname', $cname);       
                    $this->assign('coupon_list', $coupon_list);   
                }
            }
        
        // 地址  
        $this->assign('info', $res);
        $this->assign('s_price', $price);
        $this->assign('user_info', $user_info1);
        $this->assign('get_address',$get_address);

        return $this->fetch();
        }else{
           return $this->fetch('index/login'); 
        }
    }

    # 订单详情
    public function order_info(){

        if($_POST){


            dump($_POST);
            exit;
        }



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

    public function pay(){

        return $this->fetch();
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
