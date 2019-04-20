<?php

namespace app\admin\controller;

use think\Db;

class Money extends Base
{
    public $admin_id = 0;

    public function _initialize()
    {
        parent::_initialize();
        $this->admin_id = session('admin_id');
        
    }

    /**
     * 提现审核列表
     */
    public function index()
    {
        $where['id'] = ['>', '0'];
        $keywords = isset($_GET['keywords']) ? trim($_GET['keywords']) : '';
        if($keywords){
            $where['title'] = ['like', "%$keywords%"];
        }
          
        $list = Db::name('withdrawal')->where($where)->order('status','asc')->paginate(15,false)->each(function($item, $key){
            $id = $item['user_id'];
            $result = Db::name('users')->where(['id'=>$id])->field('nickname,mobile')->find();
            $item['nickname'] = $result['nickname'] ? $result['nickname'] : $result['mobile'];
            $admin = Db::name('admin')->where(['id'=>$item['admin']])->field('name')->find();
            $item['admin'] = $admin['name'];
            return $item;
        });
        
        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * 审核
     */
    public function audit()
    {
        $id = input('id/d');
        $status = input('status/d');
        $notic = input('notic/s');
        $admin_id = $this->admin_id;
        $result['code'] = 0;
        
        if ($id && $admin_id) {
            $data = array('id'=>$id,'status'=>$status,'admin_note'=>$notic,'admin'=>$admin_id,'utime'=>time());
            
            $bool = Db::name('withdrawal')->update($data);

            $result['code'] = $bool ? 1 : 0;
        }

        return json($result);
    }
    /**
     * 充值记录
     */
    public function recharge(){
        
        $keywords       = isset($_GET['seach']) ? $_GET['seach'] : '';
        $m_conditions   = isset($keywords['m_conditions']) ? $keywords['m_conditions'] : '';
        $datemin        = isset($keywords['datemin']) ? $keywords['datemin'] : '';
        $datemax        = isset($keywords['datemax']) ? $keywords['datemax'] : '';
        $total = 0;
        //搜索条件
        if($keywords){
            $seach = [
                'm_conditions'  => $m_conditions,
                'datemin'       => strtotime($datemin),
                'datemax'       => strtotime($datemax),
            ]; 
           $this->assign('seach', $seach);  
           $where =$this->search_data($keywords);          
        }
        
        $where['r.id'] = ['>', '0'];
        $list = Db::name('recharge')
                ->alias('r')
                ->join('users u', 'r.user_id = u.id')
                ->where($where)
                ->field('r.*,u.nickname')
                ->order('addtime desc')
                ->paginate(15, false, ['query' => request()->param()]);;
        if($list){
            $total = count($list);
        }

       
        $this->assign('list',$list);
        $this->assign('total', $total);
        return $this->fetch();         
    } 
    /**
     * 交易记录
     */
    public function transaction(){
        $keywords = isset($_GET['seach']) ? $_GET['seach'] : '';
        $m_conditions   = isset($keywords['m_conditions']) ? $keywords['m_conditions'] : '';
        $datemin        = isset($keywords['datemin']) ? $keywords['datemin'] : '';
        $datemax        = isset($keywords['datemax']) ? $keywords['datemax'] : '';        
        $total = 0;
        //搜索条件
        if($keywords){
            $seach = [
                'm_conditions'  => $m_conditions,
                'datemin'       => strtotime($datemin),
                'datemax'       => strtotime($datemax),
            ];
            $this->assign('seach', $seach);   
            $where = $this->search_data($keywords);
        }
        $where['r.id'] = ['>', '0'];
        $list = Db::name('transaction_log')
                ->alias('r')
                ->join('users u', 'r.user_id = u.id')
                ->where($where)
                ->field('r.*,u.nickname,u.id uid')
                ->order('addtime desc')
                ->paginate(15, false, ['query' => request()->param()]);
        if($list){
            $total = count($list);
        }       
        $this->assign('list',$list);
        $this->assign('total', $total);
        return $this->fetch();  
    }

     public function search_data($keywords){
            $m_conditions1 = trim($keywords['m_conditions']);
            $datemin1      = trim($keywords['datemin']);
            $datemax1      = trim($keywords['datemax']);
            if($m_conditions1){
                $m_conditions = str_replace(' ', '', $m_conditions1);
                $where['u.nickname'] = ['like',"%$m_conditions%"];
            }
            if ($datemin1 && $datemax1) {
                $datemin = strtotime($datemin1);
                $datemax = strtotime($datemax1);
                $where['addtime'] = [['>= time',$datemin],['<= time',$datemax],'and'];
            } elseif ($datemin1) {
                $where['addtime'] = ['>= time',strtotime($keywords['datemin'])];
            } elseif ($datemax1) {
                $where['addtime'] = ['<= time',strtotime($keywords['datemax'])];
            }
            return $where;
     }

}