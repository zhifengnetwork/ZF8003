<?php
# 微信公众号接入
namespace app\admin\controller;

use think\Db;
use think\Session;

class Weixin extends Base
{

    # 自定义菜单
    public function menu()
    {
        // $this->create_menu();


        return $this->fetch();
    }

    # 创建自定义菜单
    public function create_menu()
    {
        exit;
        $create = [
            'button' => [
                [
                    'type' => 'view',
                    'name' => '再来一个',
                    'url' => 'http://zf8003.zhifengwangluo.c3w.cc/index',
                ],
            ],
        ];

        $this->get_weixin_global_token();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$this->weixin_config['weixin_access_token'];
        $res = httpRequest($url,'POST',json_encode($create, JSON_UNESCAPED_UNICODE));
        $res = json_decode($res,true);
        dump($res);exit;
    }

    # 自动回复
    public function automatic()
    {



        return $this->fetch();
    }


    # 素材管理
    public function material(){






        return $this->fetch();
    }
}