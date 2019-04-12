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
        $this->assign('list',$list);
        return $this->fetch();
    }  
    
    /**
     * 商品详情
     */
    public function goodInfo(){
        $id = input('id');
        $images = '';
        $temp_price = 0;
        $info = Db::name('goods')->where('id',$id)->find();
        // 获取地址  
        // $request = Request::instance();
        // $ip = $request->ip();
        $ip = '119.131.60.165';
        $ip_info = $this->getIpInfo($ip);
        
        // 拼接地址省市
        $address = $ip_info['region'] . $ip_info['city'];

        if($info){
            $image = explode(',',$info['image']);
            foreach ($image as $key => $value) {
                # code...
                $images[$key]['img'] = $value;
            }
              
        }
        // $admin_id = session('admin_id');
        $user_id = Session::set('admin_id', 1);
        $where = [
             'goods_id'=> $info['id'],
             'user_id' => $user_id
        ];
        // 判断是否已经收藏
        $focus = Db::name('goods_focus')->where($where)->find();
        if(!empty($focus)){
            // 已收藏
            $this->assign('is_focus', 1);
        }

        $this->assign('is_focus', 0);
        $this->assign('temp_price', $temp_price);
        $this->assign('address', $address);
        $this->assign('images', $images);
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function order(){
        $id = input('id');
        
        $res = Db::name('goods')->where('id', $id)->find();
        $this->assign('info',$res);
        return $this->fetch();
    }

    public function focus(){
        $data = input('post.');
        // $admin_id = session('admin_id');
        $user_id = 1;
        // 判断是否已收藏
        if($data['act']=='focus'){
           
            $where = [
              'goods_id'=> $data['id'],  
              'user_id' => $user_id,
              'addtime' => time(),
            ];
           $res = Db::name('goods_focus')->insert($where);
           if($res){
                return json(['status' => 1,'msg'=> '收藏成功']);
           }else {
                return json(['status' => 0,'msg' => '收藏失败']);
           }   
        }else{
            
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
