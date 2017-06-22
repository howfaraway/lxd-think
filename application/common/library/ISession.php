<?php
namespace app\common\library;

use think\Session;

class ISession extends Session
{
    protected static $flashActive = false;
    
    public static function init_vars() {
        !isset($_SESSION) && parent::init();
        if (! empty($_SESSION['__think_vars'])) {
            $current_time = request()->time();

            foreach ($_SESSION['__think_vars'] as $key => &$value) {
                if ($value === 'new') {
                    $_SESSION['__think_vars'][$key] = 'old';
                } elseif ($value < $current_time) {
                    unset($_SESSION[$key], $_SESSION['__think_vars'][$key]);
                }
            }
            
            if (empty($_SESSION['__think_vars'])) {
                unset($_SESSION['__think_vars']);
            }
        }
        self::$flashActive = true;
    }

    /**
     * 标记闪存消息
     *
     * @param mixed $name Session名称
     * @param bool $jump 跳转页面闪存
     * @return bool
     */
    public static function markAsFlash($name, $jump)
    {
        !self::$flashActive && self::init_vars();
        if (!self::has($name)) {
            return false;
        }
        $_SESSION['__think_vars'][$name] = $jump ? 'new' : 'old';
        return true;
    }

    /**
     * 设置闪存消息
     *
     * @param string $name session名称
     * @param mixed $value session值
     * @param bool $jump 跳转页面闪存
     * @return void
     */
    public static function setFlashdata($name, $value = '', $jump = true)
    {
        !self::$flashActive && self::init_vars();
        self::set($name, $value);
        self::markAsFlash($name, $jump);
    }

    /**
     * 获取闪存消息
     *
     * @param string $name session名称
     * @return mixed
     */
    public static function getFlashdata($name = null)
    {
        !self::$flashActive && self::init_vars();
        if (isset($name)) {
            return (isset($_SESSION['__think_vars'], $_SESSION['__think_vars'][$name]) && self::has($name) && ! is_int($_SESSION['__think_vars'][$name])) ? self::get($name) : null;
        }
        
        $flashdata = [];
        
        if (! empty($_SESSION['__think_vars'])) {
            foreach ($_SESSION['__think_vars'] as $key => &$value) {
                is_int($value) or $flashdata[$key] = self::get($key);
            }
        }
        
        return $flashdata;
    }
}