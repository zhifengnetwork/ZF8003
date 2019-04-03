<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;

class Admin extends Controller
{
    public function list1()
    {
        return $this->fetch();
    }
    //   public function add(){
    //     return $this->fetch();
    //   } 
    //   public function edit(){
    //       return $this->fetch();
    //   }       
      public function permission(){
          return $this->fetch();
      }    
      public function role(){
          return $this->fetch();
      }          
}
