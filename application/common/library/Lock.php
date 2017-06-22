<?php
/**
 * 连续操作验证
 * 默认如果两次操作间隔小于10分钟，系统会记录操作次数，超过N次将被锁定，10分钟以后才能提交表单
 * 超过10分钟提交表单，以前记录的数据将被清空，重新从1开始计数
 */
namespace app\common\library;

use app\common\model\Lock as lockModel;

class Lock
{

    const MAX_LOGIN = 8; // 密码连续输入多少次被暂时锁定
    const MAX_COMMIT = 3; // 连续评论多少次被暂时锁定
    const MAX_REG = 3; // 连续注册多少个账号被暂时锁定
    const MAX_FORGET = 6; // 找回密码输入多少次被暂时锁定
    const MAX_ADMIN = 8; // 后台用户输入多少次被暂时锁定
    const MAX_SUPPLIER = 8; // 供应商后台用户输入多少次被暂时锁定
    
    /**
     * 是否启用验证
     *
     * @var unknown_type
     */
    private static $ifopen = true;

    /**
     * 记录ID
     *
     * @var unknown_type
     */
    private static $processid = array();

    /**
     * 锁ID
     *
     * @var unknown_type
     */
    private static $lockid = array();

    /**
     * 初始化，未启用内存保存时默认使用lock表存储
     *
     * @param unknown_type $type            
     */
    private static function init($type)
    {
        if (! isset(self::$processid[$type])) {
            $ip = request()->ip(0, true);
            dump($ip);
            $ip2 = request()->ip(0, true);
            dump($ip2);
            //$ip2 = sprintf('%u', ip2long($ip2));
            exit;
            self::$processid[$type] = str_pad($ip, 10, '0') . self::parsekey($type);
            self::$lockid[$type] = str_pad($ip, 11, '0') . self::parsekey($type);
        }
    }

    /**
     * 判断是否已锁
     *
     * @param unknown_type $type            
     * @return unknown
     */
    public static function islock($type = null)
    {
        if (! self::$ifopen) {
            return false;
        }
        self::init($type);
        return self::get(self::$lockid[$type]);
    }

    /**
     * 添加锁
     *
     * @param unknown_type $type            
     * @param unknown_type $ttl            
     */
    private static function addlock($type = null, $ttl = 600)
    {
        if (! self::$ifopen) {
            return;
        }
        self::init($type);
        self::set(self::$lockid[$type], '', $ttl);
    }

    /**
     * 删除锁
     *
     * @param unknown_type $type            
     */
    public static function dellock($type = null)
    {
        if (! self::$ifopen || ! isset(self::$lockid[$type])) {
            return;
        }
        self::rm(self::$lockid[$type]);
    }

    /**
     * 添加记录
     *
     * @param unknown_type $type            
     * @param unknown_type $ttl            
     */
    public static function addprocess($type = null, $ttl = 600)
    {
        if (! self::$ifopen) {
            return;
        }
        self::init($type);
        $tims = self::parsetimes($type);
        $t = self::get(self::$processid[$type]);
        if ($t >= $tims - 1) {
            self::addlock($type, $ttl);
            self::rm(self::$processid[$type]);
        } else {
            self::set(self::$processid[$type], '', $ttl);
        }
    }

    /**
     * 删除记录
     *
     * @param unknown_type $type            
     */
    public static function delprocess($type = null)
    {
        if (! self::$ifopen || ! isset(self::$processid[$type])) {
            return;
        }
        self::rm(self::$processid[$type]);
    }

    /**
     * 清空
     */
    public static function clear($type = '')
    {
        if (! self::$ifopen) {
            return;
        }
        if (empty($type)) {
            return;
        }
        self::dellock($type);
        self::delprocess($type);
    }

    public static function parsekey($type)
    {
        return str_replace(array(
            'login',
            'commit',
            'reg',
            'forget',
            'admin',
            'supplier'
        ), array(
            1,
            2,
            3,
            4,
            5,
            6
        ), $type);
    }

    /**
     * 设置最多尝试次数
     *
     * @param unknown_type $type            
     * @return unknown
     */
    public static function parsetimes($type)
    {
        return str_replace(array(
            'login',
            'commit',
            'reg',
            'forget',
            'admin',
            'supplier'
        ), array(
            self::MAX_LOGIN,
            self::MAX_COMMIT,
            self::MAX_REG,
            self::MAX_FORGET,
            self::MAX_ADMIN,
            self::MAX_SUPPLIER
        ), $type);
    }

    /**
     * 设置值
     *
     * @param mixed $key           
     * @param string $type            
     * @param int $ttl            
     * @return bool
     */
    private static function set($key, $type = '', $ttl = NULL)
    {
        if ($ttl === null) {
            $ttl = config('session.expire');
        }
        $info = lockModel::get($key);
        if ($info) {
            $info->lock_value += 1;
            $info->expiretime = request()->time() + $ttl;
            $info->save();
        } else {
            lockModel::create([
                'lock_id' => $key,
                'lock_value' => 1,
                'expiretime' => request()->time() + $ttl
            ]);
        }
    }

    /**
     * 取得值
     *
     * @param mixed $key            
     * @param mixed $type            
     * @return bool
     */
    public static function get($key, $type = '')
    {
        $info = lockModel::get($key);
        if ($info && ($info->expiretime < request()->time())) {
            self::rm($key);
            return null;
        } else {
            return !empty($info->lock_value) ? $info->lock_value : null;
        }
    }

    /**
     * 删除值
     *
     * @param mixed $key            
     * @param mixed $type            
     * @return bool
     */
    private static function rm($key, $type = '')
    {
        return lockModel::destroy($key);
    }
}