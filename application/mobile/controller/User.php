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
    
    public function __construct(){
        parent::__construct();
        
        # 验证登录
        $this->Verification_User();
    }

    /** 
     * 我的
     */
    public function index()
    {
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
        return $this->fetch();
    }

    /**
     * 账单明细
     */
    public function billing_detail()
    {
        return $this->fetch();
    }

    /**
     * 充值明细
     */
    public function top_up_detail()
    {
        return $this->fetch();
    }

    /**
     * 提现记录
     */
    public function withdrawal_detail()
    {
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
        return $this->fetch();
    }

    /**
     * 设置
     */
    public function set_up()
    {
        $user = Db::name('users')->where('id',$this->user_id)->field('mobile,email,nickname,sex,avatar')->find();
        $path = ROOT_PATH;
        if(!$user['avatar'] || !is_file($path.$user['avatar'])){
            $user['avatar'] = "";
        }
        $this->assign('info',$user);
        return $this->fetch();
    }
}