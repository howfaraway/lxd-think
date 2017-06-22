<?php
/**
 * 日志操作类
 * @author ChenGuangdong
 *
 */
namespace app\common\library;

class LogHandle
{
    /**
     * 系统日志
     * @param string $role      操作角色admin、supplier、customer分别代表平台、供应商、客户
     * @param string $content   操作
     * @param int $status       1成功0失败null不出现成功失败提示
     * @param string $admin_name
     * @param integer $admin_id
     */
    public static function systemLog($role, $content, $status = 1, $admin_name = '', $admin_id = 0)
    {
        switch ($role) {
            case 'admin':
                return self::adminLog($content, $status, $admin_name, $admin_id);
                break;
            case 'supplier':
                return self::suppliersLog($content, $status, $admin_name, $admin_id);
                break;
            case 'cron':
                break;
            case 'customer':
                break;
            case 'system':
                break;
        }
        return true;
    }
    /**
     * 记录平台后台系统日志
     *
     * @param string $content 操作
     * @param int $status 1成功0失败null不出现成功失败提示
     * @param string $admin_name
     * @param integer $admin_id
     */
    public static function adminLog($content, $status = 1, $admin_name = '', $admin_id = 0)
    {
        if (! config('sys_log') || ! is_string($content)) {
            return false;
        }
        if ($admin_name == '') {
            $admin = decrypt(cookie(config('sys_key')), config('secure_key'));
            $admin = json_decode($admin, true);
            $admin_name = $admin['name'];
            $admin_id = $admin['id'];
        }
        $data = array();
        if (is_null($status)) {
            $status = null;
        } else {
            $status = $status ? '' : '失败';
        }
        $ip = request()->ip();
        $data['content'] = $content . $status;
        $data['admin_name'] = $admin_name;
        $data['admin_id'] = $admin_id;
        $data['ip'] = $ip;
        $data['url'] = request()->path();
        return \app\common\model\AdminLog::create($data);
    }
    
    /**
     * 记录供应商后台系统日志
     *
     * @param string $content 操作
     * @param int $status 1成功0失败null不出现成功失败提示
     * @param string $admin_name
     * @param integer $admin_id
     */
    public static function suppliersLog($content, $status = 1, $username = '', $suppliers_users_id = 0)
    {
        if (! config('sys_log') || ! is_string($content)) {
            return false;
        }
        $user = decrypt(cookie(config('sys_key')), config('secure_key'));
        $user = json_decode($user, true);
        if ($username == '') {
            $username = $user['name'];
            $suppliers_users_id = $user['id'];
        }
        $data = array();
        if (is_null($status)) {
            $status = null;
        } else {
            $status = $status ? '' : '失败';
        }
        $data['suppliers_id'] = $user['suppliers_id'];
        $data['content'] = $content . $status;
        $data['username'] = $username;
        $data['suppliers_users_id'] = $suppliers_users_id;
        $data['ip'] = request()->ip();
        $data['url'] = request()->path();
        return \app\common\model\SuppliersLog::create($data);
    }
}