<?php 
namespace app\common\validate;
use think\Validate;

/**
 * 用户验证器
 */
class User extends Validate
{
    //定义验证规则
    protected $rule = [
       'username|用户名' => 'require|alphaNum',
       'password|密码'  => 'require|length:6,20',

    ];

    //定义验证提示
    protected $message = [
       'username.require' => '请输入用户名',
       'username.alphaNum' => '用户名不合法',
       'password.require' => '密码不能为空',
       'password.length'  => '密码长度6-20位',
    ];

    //定义验证场景
    protected $scene = [
        //登录验证
        'checklogin'  =>  ['username', 'password'],
    ];
}