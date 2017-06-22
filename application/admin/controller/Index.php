<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Admin;
class Index extends AdminBase
{
    public function index()
    {
        $admin = Admin::all();
        $name = 'ceshi';
        $this->assign('ceshi',$name);
        $this->assign('admin',$admin);
        return  $this->fetch();
    }
}
