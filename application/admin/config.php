<?php
return [
    // 系统登录会话key
    'sys_key'   => 'sys_key',
    // 是否记录系统日志
    'sys_log'   => true,
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl'  => APP_PATH . 'common' . DS . 'tpl' . DS . 'admin_dispatch_jump.tpl',
    'dispatch_error_tmpl'    => APP_PATH . 'common' . DS . 'tpl' . DS . 'admin_dispatch_jump.tpl',
    // 视图输出字符串内容替换
    'view_replace_str'      =>[
        '__PUBLIC__'    =>'/public/',
        '__RESOURCE__'  => '/public/static/',
        '__ROOT__'      => '/',
        '__TIMESTAMP__' => '?20170214'
    ],
];