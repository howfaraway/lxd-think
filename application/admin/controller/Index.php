<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Admin as AdminModel;

class Index extends AdminBase
{
/**
	 * 后台首页
	 */
    public function index()
    {
		$this->assign('__MENU__', model("Common/Menu")->getMenuList());
        $this->assign('role_name', model('Common/AuthGroup')->getRoleIdName(is_login()));

        /*管理员收藏栏*/
        $this->assign('__ADMIN_PANEL__', model("Common/AdminPanel")->getAllPanel(is_login()));
        return $this->fetch();
    }


    /**
     * 设置常用菜单
     */
    public function common_operations()
    {
        $type  = input('type');
        $menuid = input('menuid');
        if (!in_array($type, array('add', 'del')) || empty($menuid)) {
            echo false;exit;
        }
        $quicklink = db('menu')->where('id',$menuid)->find();
        if (empty($quicklink)) {
            echo false;exit;
        }
        $info = array(
            'menuid' => $quicklink['id'],
            'userid' => is_login(),
            'name' => $quicklink['title'],
            'url' => "{$quicklink['app']}/{$quicklink['controller']}/{$quicklink['action']}",
        );
        if ($type == 'add') {
			 $result = model('Common/AdminPanel')->createPanel($info);
        }else{
        	 unset($info['name'],$info['url']);
        	 $result = model('Common/AdminPanel')->deletePanel($info);
        }
        if ($result) {
            echo true;exit;
        } else {
            echo false;exit;
        }
    }

    /**
     * 获取验证码
     */
    public function getVerify()
    {
        GetVerify();
    }

    /**
     * 后台登陆界面
     */
    public function login()
    {
       if(request()->isPost()){
	       	$data = $this->request->post();
	        // 验证数据
	        $result = $this->validate($data, 'User.checklogin');
	        if(true !== $result){
	            $this->error($result);
	        }
	        if(!CheckVerify($data['captcha'])){
	            $this->error('验证码输入错误！');
	            return false;
	        }
	        $admin = new AdminModel();
	       if($admin->checkLogin($data['username'], $data['password'])){
	            $this->success('登录成功！', url('Index/index'));
	        }else{
	            $this->error($admin->getError());
	        }

       }else{
            if(is_login()){
                $this->redirect('Index/index');
            }else{
                return $this->fetch();
            }
       }

    }

    /* 退出登录 */
    public function logout()
    {
        if(is_login()){
            model('Admin/User')->logout();
            session('[destroy]');
            $this->success('退出成功！', url('login'));
        } else {
            $this->redirect('login');
        }
    }
}
