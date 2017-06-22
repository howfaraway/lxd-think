<?php
/**
 * 缓存操作类
 * @author ChenGuangdong
 *
 */
namespace app\common\library;

use think\Cache;
use think\Log;

class CacheHandle
{

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存标识
     * @param mixed  $default 默认值
     * @return mixed
     */
    public static function get($name, $default = false)
    {
        dump(Cache::get($name, $default));exit;
        try {
            return Cache::get($name, $default);
        } catch (\Exception $e) {
            return Cache::store('file')->get($name, $default);
        }
    }

    /**
     * 写入缓存
     * @access public
     * @param string        $name 缓存标识
     * @param mixed         $value  存储数据
     * @param int|null      $expire  有效时间 0为永久
     * @return boolean
     */
    public static function set($name, $value, $expire = null)
    {
        try {
            return Cache::set($name, $value, $expire);
        } catch (\Exception $e) {
            return Cache::store('file')->set($name, $value, $expire);
        }
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public static function rm($name)
    {
        $result = Cache::store('file')->rm($name);
        try {
            return Cache::store('default')->rm($name);
        } catch (\Exception $e) {
            return $result;
        }
    }

    /**
     * KV缓存 读写(如果缓存为空，则尝试写入)
     *
     * @param string $key 缓存名称
     * @param boolean $callback 缓存读取失败时是否使用回调 true代表使用预定义的缓存项 默认不使用回调
     * @return mixed
     */
    public static function rwcache($key, $callback = true)
    {
        $value = self::get($key);dump($key);exit;
        if ($value !== false || $callback === false) {
            return $value;
        }
        $lock = new ProcessLock('cache_' . $key);
        if ($lock->lock()) {
            $value = self::get($key);
            if ($value !== false) {
                return $value;
            }
            $value = self::wcache($key);
            $lock->releaseLock();
        } else {
            $value = self::get($key);
            if ($value !== false) {
                return $value;
            }
            $value = self::wcache($key);
        }

        return $value;
    }

    /**
     * 写入缓存
     * @param string $key
     * @return null
     */
    private static function wcache($key)
    {
        $value = null;
        $method = strtolower($key);
        if (strpos($method, ':') !== false) {
            list($method, $id) = explode(':', $method, 2);
        }
        if (method_exists('\\app\\common\\service\\Cache', $method)) {
            if (isset($id)) {
                $value = service('common/Cache')->$method($id);
            } else {
                $value = service('common/Cache')->$method();
            }
        }
        self::set($key, $value, 0);
        return $value;
    }
    
}