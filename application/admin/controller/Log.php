<?php
/**
 * 后台管理系统首页
 */
namespace app\admin\controller;

use think\Db;

class Log extends Base
{
     public function log(){
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
        
        $where['l.id'] = ['>', '0'];        
        $list = Db::name('admin_log')
        ->alias('l')
        ->join('admin a','a.id = l.admin_id')
        ->field('l.*,a.name')
        ->where($where)
        ->order('l.addtime desc')
        ->paginate(15, false, ['query' => request()->param()]);
        
        $total = count($list);
        $this->assign('total',$total);
        $this->assign('list',$list);
        return $this->fetch();
     }

     public function search_data($keywords){
            $m_conditions1 = trim($keywords['m_conditions']);
            $datemin1      = trim($keywords['datemin']);
            $datemax1      = trim($keywords['datemax']);
            if($m_conditions1){
                $m_conditions = str_replace(' ', '', $m_conditions1);
                $where['a.name'] = ['like',"%$m_conditions%"];
            }
            if ($datemin1 && $datemax1) {
                $datemin = strtotime($datemin1);
                $datemax = strtotime($datemax1);
                $where['l.addtime'] = [['>= time',$datemin],['<= time',$datemax],'and'];
            } elseif ($datemin1) {
                $where['l.addtime'] = ['>= time',strtotime($keywords['datemin'])];
            } elseif ($datemax1) {
                $where['l.addtime'] = ['<= time',strtotime($keywords['datemax'])];
            }
            return $where;
     }
     

}