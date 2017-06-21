<?php
namespace app\common\controller;

use think\Controller;

/**
*
* 公用控制器
* @date: 2017年6月21日 下午5:19:28
* @author: 6005001708
*
*/
class Base extends Controller
{
    //空操作
    public function _empty()
    {
        $this->error('该页面不存在！');
    }
}