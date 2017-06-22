<?php
namespace app\common\behavior;

use think\Config;
/**
*
* 这里绑定的行为是应用初始化，所以我们可以在此定义很多应用需要的变量等等情况，甚至是一些路由的定义
* @date: 2017年6月22日 下午4:00:32
* @author: 6005001708
*
*/
class AppInit {

    public function run(&$param)
    {
        // 站点初始化
        $this->initialization();
        // 补充配置
        $this->config();
        // 系统配置
        $this->setting();
    }
    
    /**
     * 初始化，定义一些常量
     */
    private function initialization()
    {

        //定义常量
        define('NOW_TIME', $_SERVER['REQUEST_TIME']);
        define('NOW_DATE', date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']));
        define('TABLE_PREFIX', Config::get('database.prefix'));
        
        // +----------------------------------------------------------------------
        // | 自定义常量
        // +----------------------------------------------------------------------
        // 目录
        define('RESOURCE_PATH', ROOT_PATH . 'static' . DIRECTORY_SEPARATOR);
        define('UPLOAD_PATH', ROOT_PATH . 'upload' . DIRECTORY_SEPARATOR);
        define('DOWNLOAD_PATH', ROOT_PATH . 'download' . DIRECTORY_SEPARATOR);
        define('TEMPFILES_PATH', UPLOAD_PATH . 'temp' . DIRECTORY_SEPARATOR);
        define('LOCK_PATH', RUNTIME_PATH . 'lock' . DIRECTORY_SEPARATOR);
        // 资源URL配置
        define('PUBLIC_URL', '/public/');
        define('IMAGE_URL', PUBLIC_URL . 'static/images/');
        define('CSS_URL', PUBLIC_URL . 'static/css/');
        define('JS_URL', PUBLIC_URL . 'static/js/');
        define('UPLOAD_URL', 'http://' . config('image_domain') . '/');
       
    }
    
    /**
     * 配置一些公用的类型数据
     */
    private function config()
    {
        Config::set([
          
            // 银行代码
            'bank_code_array' => ['0801020000' => '中国工商银行', '0801030000' => '中国农业银行', '0801050000' => '中国建设银行', '0803080000' => '招商银行',
                '0801040000' => '中国银行','0803010000' => '中国交通银行', '0801000000' => '中国邮政', '0803050000' => '中国民生银行', '0803030000' => '中国光大银行',
                '0803040000' => '华夏银行','0803100000' => '浦发银行', '0803090000' => '兴业银行','0803020000' => '中信银行','0804031000' => '北京银行',
                '0803060000' => '广东发展银行', '0865012900' => '上海农商', '0804105840' => '平安银行', '0000000000' => '银联'
            ],
           
        ]);
    }

    private function setting()
    {
        $setting = rwcache('setting', true);
        if (!empty($setting)) {
            Config::set($setting, 'setting');
        }
    }
    
}