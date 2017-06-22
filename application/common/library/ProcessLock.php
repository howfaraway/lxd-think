<?php
/**
 * ProcessLock 进程锁,主要用来进行cache失效时的单进程cache获取，防止过多的SQL请求穿透到数据库
 * 用于解决PHP在并发时候的锁控制
 * 可选择通过redis/文件进行进程间锁定
 * 如果redis为安装则选择文件锁
 * 不同的锁之间并行执行，类似mysql innodb的行级锁
 * Created by PhpStorm.
 * User:
 * Date: 2016/12/1
 * Time: 17:05
 */

namespace app\common\library;


class ProcessLock
{
    //文件锁存放路径
    private $path = null;
    //文件句柄
    private $fp = null;
    //锁粒度,设置越大粒度越小
    private $hashNum = 100;
    //lock key
    private $lock_key;
    //超时时间
    private $timeout;
    //过期时间
    private $expire;
    //等待时间(微秒)
    private $sleep = 100;
    /**
     * redis连接
     * @var \Redis
     */
    private $redis_client = null;
    private $redis_config = [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => '',
        'timeout'    => 0,
        'persistent' => false
    ];

    /**
     * 构造函数
     * 传入锁的存放路径，及cache key的名称，这样可以进行并发
     * @param string $name cache key
     */
    public function __construct($name, $timeout = 2, $expire = 2)
    {
        $this->setLockKey($name);
        $this->initRedis();
        $this->timeout = $timeout * 1000 * 1000 + 1;
        $this->expire = $expire;
    }

    /**
     * 初始化redis连接
     * @return bool
     */
    private function initRedis()
    {
        if (!extension_loaded('redis')) {
            return false;
        }
        if (null !== $this->redis_client) {
            return true;
        }
        //$this->redis_config = array_merge($this->redis_config, config('redis'));
        $this->redis_client = new \Redis();
        $func = $this->redis_config['persistent'] ? 'pconnect' : 'connect';
        $this->redis_client->$func($this->redis_config['host'], $this->redis_config['port'], $this->redis_config['timeout']);
        if ('' != $this->redis_config['password']) {
            $this->redis_client->auth($this->redis_config['password']);
        }
        return true;
    }

    /**
     * 设置lock key
     * @param string $name
     */
    private function setLockKey($name)
    {
        $this->lock_key = "lock_{$name}";
    }

    /**
     * 获取琐
     * @return bool|int
     */
    public function lock()
    {
        if ($this->redis_client instanceof \Redis) {
            return $this->getRedisLock();
        } else {
            return $this->getFileLock();
        }
    }

    /**
     * 释放琐
     * @return bool
     */
    public function releaseLock()
    {
        if ($this->redis_client instanceof \Redis) {
            return $this->releaseRedisLock();
        } else {
            return $this->releaseFileLock();
        }
    }

    /**
     * 获取redis琐
     * @return bool|int
     */
    private function getRedisLock()
    {
        $this->initRedis();
        $expire = $this->expire;
        $expire_at = $expire ? time() + $expire : 0;
        $is_get = (bool)$this->redis_client->setnx($this->lock_key, $expire_at);
        if ($is_get) {
            return $expire ? $expire_at : true;
        }
        $timeout = $this->timeout;
        while ($timeout) {
            $timeout -= $this->sleep;
            usleep($this->sleep);
            $time = time();
            $old_expire = $this->redis_client->get($this->lock_key);
            if ($old_expire >= $time) {
                continue;
            }
            $new_expire = $time + $expire;
            $expire_at = $this->redis_client->getset($this->lock_key, $new_expire);
            if ($old_expire != $expire_at) {
                continue;
            }
            $is_get = $new_expire;
            break;
        }
        return $is_get;
    }

    /**
     * 释放redis琐
     * @return int
     */
    private function releaseRedisLock()
    {
        return $this->redis_client->del($this->lock_key);
    }

    /**
     * crc32
     * crc32封装
     * @param int $string
     * @return int
     */
    private function mycrc32($string)
    {
        $crc = abs (crc32($string));
        if ($crc & 0x80000000) {
            $crc ^= 0xffffffff;
            $crc += 1;
        }
        return $crc;
    }

    /**
     * 获取文件锁
     * @return bool
     */
    private function getFileLock()
    {
        $timeout = $this->timeout;
        while ($timeout) {
            $path = LOCK_PATH;
            if (!exist_dir($path)) {
                return false;
            }
            //$file = $path . ($this->mycrc32($name) % $this->hashNum).'.txt';
            $file = $path . $this->lock_key . '.txt';
            //配置目录权限可写
            $this->fp = fopen($file, 'w+');
            if (false === $this->fp) {
                $timeout -= $this->sleep;
                usleep($this->sleep);
                continue;
            }
            return flock($this->fp, LOCK_EX);
        }
        return false;
    }

    /**
     * 释放文件琐
     * @return bool
     */
    private function releaseFileLock()
    {
        if ($this->fp !== false)
        {
            flock($this->fp, LOCK_UN);
            clearstatcache();
        }
        //进行关闭
        return fclose($this->fp);
    }
}