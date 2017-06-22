<?php
/**
 * Created by PhpStorm.
 * User: ChenGuangdong
 * Date: 2016/12/8
 * Time: 13:18
 */

namespace app\common\library;


class Rsa
{
    /**
     * Decode a string with URL-safe Base64.
     *
     * @param string $input A Base64 encoded string
     *
     * @return string A decoded string
     */
    public static function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Encode a string with URL-safe Base64.
     *
     * @param string $input The string you want encoded
     *
     * @return string The base64 encode of what you passed in
     */
    public static function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * 返回对应的私钥(内部类调用)
     */
    private static function getPrivateKey($key)
    {
        if (is_file($key)) {
            $key = file_get_contents($key);
        }
        if (!is_string($key)) {
            return null;
        }
        return openssl_pkey_get_private($key);
    }

    /**
     * 返回对应的公钥(内部类调用)
     */
    private static function getPublicKey($key)
    {
        if (is_file($key)) {
            $key = file_get_contents($key);
        }
        if (!is_string($key)) {
            return null;
        }
        return openssl_pkey_get_public($key);
    }

    /**
     * 私钥加密
     */
    public static function privateEncrypt($data, $key, $padding = OPENSSL_PKCS1_PADDING)
    {
        if (!is_string($data)) {
            return null;
        }
        return openssl_private_encrypt($data, $encrypted, self::getPrivateKey($key), $padding) ? $encrypted : null;
    }

    /**
     * 私钥解密
     */
    public static function privateDecrypt($encrypted, $key, $padding = OPENSSL_PKCS1_PADDING)
    {
        if (!is_string($encrypted)) {
            return null;
        }
        return openssl_private_decrypt($encrypted, $decrypted, self::getPrivateKey($key), $padding) ? $decrypted : null;
    }

    /**
     * 公钥加密
     */
    public static function publicEncrypt($data, $key, $padding = OPENSSL_PKCS1_PADDING)
    {
        if (!is_string($data)) {
            return null;
        }
        return openssl_public_encrypt($data, $encrypted, self::getPublicKey($key), $padding) ? $encrypted : null;
    }

    /**
     * 公钥解密
     */
    public static function publicDecrypt($encrypted, $key, $padding = OPENSSL_PKCS1_PADDING)
    {
        if (!is_string($encrypted)) {
            return null;
        }
        return openssl_public_decrypt($encrypted, $decrypted, self::getPublicKey($key), $padding) ? $decrypted : null;
    }

    /**
     * 签名
     */
    public static function sign($data, $key, $alg = OPENSSL_ALGO_SHA1)
    {
        if (!is_string($data)) {
            return null;
        }
        return openssl_sign($data, $encrypted, self::getPrivateKey($key), $alg) ? $encrypted : null;
    }

    /**
     * 签名验证
     */
    public static function verify($data, $sign, $key, $alg = OPENSSL_ALGO_SHA1)
    {
        if (!is_string($data)) {
            return null;
        }
        return openssl_verify($data, $sign, self::getPublicKey($key), $alg) == 1 ? true : false;
    }
}