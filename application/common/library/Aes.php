<?php
/**
 * Created by PhpStorm.
 * User: ChenGuangdong
 * Date: 2016/12/8
 * Time: 13:58
 */

namespace app\common\library;


class Aes
{

    /**
     * 加密方法
     * @param string $str
     * @return string
     */
    public static function encrypt($str, $screct_key)
    {
        //AES, 128 ECB模式加密数据
        $str = trim($str);
        $str = self::addPKCS7Padding($str);

        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB), MCRYPT_RAND);
        $encrypt_str = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $screct_key, $str, MCRYPT_MODE_ECB, $iv);
        return base64_encode($encrypt_str);
    }

    /**
     * 解密方法
     * @param string $str
     * @return string
     */
    public static function decrypt($str, $screct_key)
    {
        //AES, 128 ECB模式加密数据
        $str = base64_decode($str);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB), MCRYPT_RAND);
        $encrypt_str =  mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $screct_key, $str, MCRYPT_MODE_ECB, $iv);
        $encrypt_str = trim($encrypt_str);
        $encrypt_str = self::stripPKSC7Padding($encrypt_str);

        return $encrypt_str;
    }

    /**
     * 填充算法
     * @param string $source
     * @return string
     */
    public static function addPKCS7Padding($source)
    {
        $source = trim($source);
        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $pad = $block - (strlen($source) % $block);
        if ($pad <= $block) {
            $char = chr($pad);
            $source .= str_repeat($char, $pad);
        }
        return $source;
    }

    /**
     * 移去填充算法
     * @param string $source
     * @return string
     */
    public static function stripPKSC7Padding($source)
    {
        $source = trim($source);
        $char = substr($source, -1);
        $pad = ord($char);
        if ($pad > strlen($source) || strspn($source, chr($pad), strlen($source) - $pad) != $pad) {
            return $source;
        }
        $source = substr($source, 0, - $pad);
        return $source;
    }
}