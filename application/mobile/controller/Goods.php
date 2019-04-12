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
        $ip = '119.131.60.165';
        $ip_info = $this->getIpInfo($ip);

        // 拼接地址省市
        $address = $ip_info['region'] . $ip_info['city'];

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
        $this->assign('address', $address);
        $this->assign('images', $images);
        $this->assign('info', $info);
        return $this->fetch();
    }

    public function order()
    {
        $id = input('id');
        $user_id = 29;
        // 用户信息
        $where = [
            'u.id' => $user_id,
            'is_default' => 1
        ];
        $user_info = Db::name('users')
            ->alias('u')
            ->join('user_address us', 'u.default_address_id = us.id')
            ->where($where)
            ->find();

        // 地址  
        $province = Db::name('area')->where('id', $user_info['province'])->find();
        $city     = Db::name('area')->where('id', $user_info['city'])->find();
        $district = Db::name('area')->where('id', $user_info['district'])->find();
        $dizhi    = $province['name'] . $city['name'] . $district['name'] . $user_info['address'];
        // 商品信息
        $res = Db::name('goods')->where('id', $id)->find();
        // 计算邮费

        $freight_temp = Db::name('freight_temp')->where('id',$res['freight_temp'])->find();
        $temp = json_decode($freight_temp['temp'],true);

        // 判断是否存在运费模板
        if($freight_temp){
             $num = count($temp); 
            //  判断是否设置特定地区运费
            if($num == 1){
                $price = $temp['freight'];  
             }else{
                $m=array_keys($temp);
                // 判断用户地区id是否等于模板的id
                // $a=[
                //     ,
                //     '1' => $user_info['city'],
                //     '2' => $user_info['district'],
                // ];
          
                $price = $temp['freight'];  
                $a['0'] =  ['address' => $user_info['province']];
                $a['1'] =  ['address' => $user_info['city']];
                $a['2'] =  ['address' => $user_info['district']]; 
          
                foreach ($a as $key => $value) {
                    # code...
                    
                    if($value['address'] == $m[1]){
                          $price = $temp[$value];
                    }
                }
             

             } 
        }else{
             $price = $res['freight'];
        }
        $this->assign('user_info', $user_info);
        $this->assign('dizhi', $dizhi);
        $this->assign('info', $res);
        return $this->fetch();
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
