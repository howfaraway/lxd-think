<?php
namespace app\common\validate;

use think\Validate;
/**
*
* 后台管理员验证类
* @date: 2017年6月22日 下午3:51:08
* @author: 6005001708
*
*/
class Admin extends Validate
{
    protected $rule = [
        'admin_name' => 'require|checkName:|unique:admin',
        'admin_password' => 'require|length:6,30|checkPass:',
        'admin_new_pass' => 'length:6,30|checkPass:',
        'admin_new_rpass' => 'checkRPass:',
        'group_id' => 'require',
        'login_name' => 'require|checkName:',
        'login_pass' => 'require|length:6,30',
    ];
    
    protected $message = [
        'admin_name.require' => '请填写帐号',
        'admin_pass.require' => '请填写密码',
        'admin_pass.length' => '密码为6-30位字符',
        'admin_new_pass.length' => '密码为6-30位字符',
        'group_id.require' => '请选择权限组',
        'login_name.require' => '请填写帐号',
        'login_pass.require' => '请填写密码',
    ];
    
    protected $scene = [
        'login' => ['login_name', 'login_pass'],
        'add' => ['admin_name', 'admin_pass', 'admin_rpass', 'group_id'],
        'edit' => ['admin_name', 'admin_new_pass', 'admin_new_rpass', 'group_id'],
        'reset' => ['admin_pass','admin_new_pass', 'admin_new_rpass'],
    ];
    
    protected function checkName($value, $rule, $data)
    {
        $result = preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9-_]{2,20}$/u', $value);
        if (!$result) {
            return '登录名为2-20位字符，可由中文、英文、数字及“_”、“-”组成';
        } else {
            return true;
        }
    }
    
    protected function checkPass($value, $rule, $data)
    {
        $result = password_check($value);
        if (!empty($result)) {
            return $result;
        } else {
            return true;
        }
    }
    
    protected function checkRPass($value, $rule, $data)
    {
        if (($this->currentScene == 'add' && $value != $data['admin_password'])
            || ($this->currentScene == 'edit' && $value != $data['admin_new_pass'])) {
            return '两次输入的密码不一致，请重新输入';
        } else {
            return true;
        }
    }
}