<?php

use app\common\library\ISession;
use org\net\IpLocation;
use app\common\library\CacheHandle;
use think\Config;
use app\common\library\PurifierSecurity;
use think\Loader;
use app\common\library\ResizeImage;

/**
 * 实例化Service
 * @param string $name Service名称
 * @return object
 */
function service($name = '')
{
    return Loader::model($name, 'service');
}

/**
 * 实例化Logic
 * @param string $name Logic名称
 * @return object
 */
function logic($name = '')
{
    return Loader::model($name, 'logic');
}

/**
 * 重写Url生成
 * @param string        $url 路由地址
 * @param string|array  $vars 变量
 * @param bool|string   $suffix 生成的URL后缀
 * @param bool|string   $domain 域名
 * @return string
 */
function url($url = '', $vars = '', $suffix = true, $domain = false)
{
    if (is_array($vars) && Config::get('url_common_param')) {
        foreach ($vars as &$var) {
            if (is_string($var) && $var != '') {
                $var = urlencode($var);
            }
        }
    }
    return \think\Url::build($url, $vars, $suffix, $domain);
}

/**
 * 日志初始化
 * @param array $config
 */
function log_init($config)
{
    \think\Log::init($config);
    if (!Config::get('app_debug')) {
        \think\Log::record('[ ROUTE ] ' . var_export(request()->dispatch(), true), 'info');
        \think\Log::record('[ HEADER ] ' . var_export(request()->header(), true), 'info');
        \think\Log::record('[ PARAM ] ' . var_export(request()->param(), true), 'info');
    }
}

/**
 * 价格格式化
 * @param float $price
 * @param number $decimals
 * @return string
 */
function mround($price, $decimals = 2)
{
    if (!is_numeric($price)) {
        return $price;
    }
    $price = round($price, $decimals);
    return number_format($price, $decimals, '.', '');
}

/**
 * 格式化价格
 * @param float $price
 * @param number $decimals
 * @return string
 */
function format_price($price, $decimals = 2)
{
    return '<span style="font-family:Arial;">&yen;&nbsp;</span>' . mround($price, $decimals);
}

/**
 * 取得随机数
 *
 * @param int $length   生成随机数的长度
 * @param int $numeric  是否只产生数字随机数 1是0否
 * @return string
 */
function random($length, $numeric = 0)
{
    $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
    $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
    $hash = '';
    $max = strlen($seed) - 1;
    for ($i = 0; $i < $length; $i ++) {
        $hash .= $seed{mt_rand(0, $max)};
    }
    return $hash;
}

/**
 * 加密函数
 *
 * @param string $txt 需要加密的字符串
 * @param string $key 密钥
 * @return string     返回加密结果
 */
function encrypt($txt, $key = '')
{
    if (empty($txt)) {
        return $txt;
    }
    if (empty($key)) {
        $key = config('secure_key');
    }
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
    $ikey = "-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
    $nh1 = rand(0, 64);
    $nh2 = rand(0, 64);
    $nh3 = rand(0, 64);
    $ch1 = $chars{$nh1};
    $ch2 = $chars{$nh2};
    $ch3 = $chars{$nh3};
    $nhnum = $nh1 + $nh2 + $nh3;
    $knum = 0;
    $i = 0;
    while (isset($key{$i}))
        $knum += ord($key{$i ++});
    $mdKey = substr(md5(md5(md5($key . $ch1) . $ch2 . $ikey) . $ch3), $nhnum % 8, $knum % 8 + 16);
    $txt = base64_encode(time() . '_' . $txt);
    $txt = str_replace(['+', '/', '='], ['-', '_', '.'], $txt);
    $tmp = '';
    $j = 0;
    $k = 0;
    $tlen = strlen($txt);
    $klen = strlen($mdKey);
    for ($i = 0; $i < $tlen; $i ++) {
        $k = $k == $klen ? 0 : $k;
        $j = ($nhnum + strpos($chars, $txt{$i}) + ord($mdKey{$k ++})) % 64;
        $tmp .= $chars{$j};
    }
    $tmplen = strlen($tmp);
    $tmp = substr_replace($tmp, $ch3, $nh2 % ++ $tmplen, 0);
    $tmp = substr_replace($tmp, $ch2, $nh1 % ++ $tmplen, 0);
    $tmp = substr_replace($tmp, $ch1, $knum % ++ $tmplen, 0);
    return $tmp;
}

/**
 * 解密函数
 *
 * @param string $txt 需要解密的字符串
 * @param string $key 密匙
 * @return string     字符串类型的返回结果
 */
function decrypt($txt, $key = '', $ttl = 0)
{
    if (empty($txt)) {
        return $txt;
    }
    if (empty($key)) {
        $key = config('secure_key');
    }
    
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
    $ikey = "-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
    $knum = 0;
    $i = 0;
    $tlen = @strlen($txt);
    while (isset($key{$i}))
        $knum += ord($key{$i ++});
    $ch1 = @$txt{$knum % $tlen};
    $nh1 = strpos($chars, $ch1);
    $txt = @substr_replace($txt, '', $knum % $tlen --, 1);
    $ch2 = @$txt{$nh1 % $tlen};
    $nh2 = @strpos($chars, $ch2);
    $txt = @substr_replace($txt, '', $nh1 % $tlen --, 1);
    $ch3 = @$txt{$nh2 % $tlen};
    $nh3 = @strpos($chars, $ch3);
    $txt = @substr_replace($txt, '', $nh2 % $tlen --, 1);
    $nhnum = $nh1 + $nh2 + $nh3;
    $mdKey = substr(md5(md5(md5($key . $ch1) . $ch2 . $ikey) . $ch3), $nhnum % 8, $knum % 8 + 16);
    $tmp = '';
    $j = 0;
    $k = 0;
    $tlen = @strlen($txt);
    $klen = @strlen($mdKey);
    for ($i = 0; $i < $tlen; $i ++) {
        $k = $k == $klen ? 0 : $k;
        $j = strpos($chars, $txt{$i}) - $nhnum - ord($mdKey{$k ++});
        while ($j < 0)
            $j += 64;
        $tmp .= $chars{$j};
    }
    $tmp = str_replace(['-', '_', '.'], ['+', '/', '='], $tmp);
    $tmp = trim(base64_decode($tmp));
    
    if (preg_match("/\d{10}_/s", substr($tmp, 0, 11))) {
        if ($ttl > 0 && (time() - substr($tmp, 0, 11) > $ttl)) {
            $tmp = null;
        } else {
            $tmp = substr($tmp, 11);
        }
    }
    return $tmp;
}

/**
 * 产生验证码
 *
 * @param string $position 位置
 * @return string
 */
function make_captcha($position)
{
    $code = random(6, 1);
    
    $s = sprintf('%04s', base_convert($code, 10, 23));
    $codeUnits = 'A8BCDE23FGHIJK9MNPR7STVXY4615';
    if ($codeUnits) {
        $code = '';
        for ($i = 0; $i < 4; $i ++) {
            $unit = ord($s{$i});
            $code .= ((48 <= $unit) && ($unit <= 57) ? $codeUnits[$unit - 48] : $codeUnits[$unit - 87]);
        }
    }
    cookie('captcha_' . $position, encrypt(strtoupper($code) . "\t" . (time()) . "\t" . $position, Config::get('secure_key')), 3600);
    return $code;
}

function check_captcha($position, $value)
{
    if (empty(cookie('captcha_'.$position))) {
        return false;
    }
    list($checkValue, $checkTime, $checkPosition) = explode("\t", decrypt(cookie('captcha_'.$position), Config::get('secure_key')));
    $result = $checkValue == strtoupper($value) && $checkPosition == $position;
    if ($result == true)
    {
        cookie('captcha_'.$position, null);
    }
    return $result;
}

/**
 * 验证密码
 * @param string $plain        明文
 * @param string $encrypted    密文
 * @return boolean
 */
function validate_password($plain, $encrypted) {
    if (empty($plain) || empty($encrypted)) {
        return false;
    }
    $stack = explode(':', $encrypted);
    if (sizeof($stack) != 2) {
        return false;
    }
    if (md5($stack[1].$plain) != $stack[0]) {
        return false;
    }

    return true;
}

/**
 * 生成密码
 * @param string $plain 明文
 */
function encrypt_password($plain)
{
    $password = '';

    for ($i=0; $i<10; $i++) {
        $password .= mt_rand();
    }

    $salt = substr(md5($password), 0, 2);

    $password = md5($salt . $plain) . ':' . $salt;
    return $password;
}

/**
 * 检查密码强度
 * @param string $password
 * @return number
 */
function password_strength($password)
{
    $score = 0;
    if (preg_match("/[0-9]+/", $password)) {
        $score ++;
    }
    if (preg_match("/[0-9]{3,}/", $password)) {
        $score ++;
    }
    if (preg_match("/[a-z]+/", $password)) {
        $score ++;
    }
    if (preg_match("/[a-z]{3,}/", $password)) {
        $score ++;
    }
    if (preg_match("/[A-Z]+/", $password)) {
        $score ++;
    }
    if (preg_match("/[A-Z]{3,}/", $password)) {
        $score ++;
    }
    if (preg_match("/[_\W]+/", $password)) {
        $score += 2;
    }
    if (preg_match("/[_\W]{3,}/", $password)) {
        $score ++;
    }
    if (strlen($password) >= 10) {
        $score ++;
    }
    return $score;
}

/**
 * 密码检测(后台用)
 * @param string $password
 * @return string
 */
function password_check($password)
{
    if (strlen($password) < 8) {
        return '新密码长度至少8位';
    }
    if (!preg_match("/(?![a-zA-Z0-9]+$)(?![a-zA-Z\W_]+$)(?![0-9\W_]+$).{8,30}/", $password)) {
        return '新密码需含字母、数字、符号三种';
    }
    $string = strtolower($password);
    $array = str_split($string, 1);
    $string_flag = false;
    $num_flag = false ;
    $char_flag = false ;
    foreach ($array as $key => $value) {
        if (!empty($array[$key + 1]) && !empty($array[$key + 2])) {
            $next1_string = $array[$key + 1];
            $next2_string = $array[$key + 2];
            // 相同的字符
            if ($value == $next1_string && $value == $next2_string) {
                $string_flag = true;//新密码连续三个字符不能重复
                return '新密码连续三个字符不能重复，例如111、aaa';
            }
            // 数字连续
            if (is_numeric($value) && is_numeric($next1_string) && is_numeric($next2_string)) {
                if ((($value ==  ($next1_string + 1)) && ($value == ($next2_string + 2)))
                    || (($value ==  ($next1_string - 1)) && ($value == ($next2_string - 2)))) {
                        $num_flag = true;//新密码连续三个数字不可连续
                    }
            }
            $value_ord = ord($value);
            $next1_string_ord = ord($next1_string);
            $next2_string_ord = ord($next2_string);
            // 字母连续
            if ( $value_ord >= 97 && $value_ord <= 122 && $next1_string_ord >= 97
                && $next1_string_ord <= 122 && $next2_string_ord >= 97 && $next2_string_ord <= 122) {
                    if (($value_ord == ($next1_string_ord + 1) && $value_ord == ($next2_string_ord + 2))
                        || ($value_ord == ($next1_string_ord - 1) && $value_ord == ($next2_string_ord - 2))) {
                            $char_flag = true;//新密码连续三个字母不可连续
                        }
                }
        }
    }
    
    if ($num_flag || $char_flag) {
        return '连续三个字段不可连续，例如123、abc';
    }
    return '';
}

/**
 * 闪存消息管理
 * @param string|array $name 消息名称，如果为数组表示进行批量设置
 * @param mixed $value 消息值
 * @param bool $jump 跳转页面保存
 * @return mixed
 */
function flashdata($name, $value = '', $jump = true)
{
    if (is_array($name)) {
        $flashdata = [];
        foreach ($name as $key => $value) {
            $flashdata[] = ISession::setFlashdata($name, $value, $jump);
        }
        return $flashdata;
    } elseif ('' === $value) {
        return ISession::getFlashdata($name);
    } else {
        return ISession::setFlashdata($name, $value, $jump);
    }
}

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function get_client_ip($type = 0, $adv = false)
{
    return IpLocation::instance()->getClientIp($type = 0, $adv = false);
}

/**
 * KV缓存 读写(如果缓存为空，则尝试写入)
 *
 * @param string $key 缓存名称
 * @param boolean $callback 缓存读取失败时是否使用回调 true代表使用cache.model中预定义的缓存项 默认不使用回调
 * @return mixed
 */
function rwcache($key, $callback = true)
{
    return CacheHandle::rwcache($key, $callback);
}

/**
 * HTML转义     html_escape
 * @param mixed $value 需要转义的值
 * @return mixed
 */
function h($value)
{
    if (empty($value)) {
        return $value;
    }
    if (is_array($value)) {
        foreach ($value as $k => $v) {
            $value[$k] = h($v);
        }
        return $value;
    }
    $value = str_replace(['&', "'", '"', '<', '>'], ['&amp;', '&apos;', '&quot;', '&lt;', '&gt;'], $value);
    $value = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4})|[a-zA-Z][a-z0-9]{2,5});)/', '&\\1', $value);
    return $value;
}

/**
 * HTML过滤XSS safe_html
 * @param mixed $value 需要转义的值
 * @return mixed
 */
function sh($value)
{
    if (empty($value)) {
        return $value;
    }
    return PurifierSecurity::xssClean($value);
}

/**
 * 压缩HTML
 * @param string $string
 * @return mixed
 */
function compress_html($string) {
    $string = str_replace("\r\n", '', $string); //清除换行符
    $string = str_replace("\n", '', $string); //清除换行符
    $string = str_replace("\t", '', $string); //清除制表符
    $pattern = array (
        "/> *([^ ]*) *</", //去掉注释标记
        "/[\s]+/",
        "/<!--[\\w\\W\r\\n]*?-->/",
        "/\" /",
        "/ \"/",
        "'/\*[^*]*\*/'"
    );
    $replace = array (
        ">\\1<",
        " ",
        "",
        "\"",
        "\"",
        ""
    );
    return preg_replace($pattern, $replace, $string);
}

/**
 * 获取返回跳转的URL
 * @param string $url 跳转的URL地址或路由地址
 * @param string $params 参数
 * @return string
 */
function referer_url($url = '', $params = '')
{
    if (input('request.referer_url')) {
        return urldecode(input('request.referer_url'));
    }
    if ($url != '') {
        return url($url, $params);
    }
    if (isset($_SERVER["HTTP_REFERER"])) {
        return $_SERVER["HTTP_REFERER"];
    }
    return '';
}

/**
 * 生成缩略图，成功返回缩略图相对路劲，失败返回false
 * @param string $source
 * @param number $thumb_width
 * @param number $thumb_height
 * @param string $ext
 * @return boolean|string
 */
function resize_image($source, $thumb_width, $thumb_height, $ext = '')
{
    $imgscaleto = ($thumb_width == $thumb_height);
    $image_info = @getimagesize($source);
    if (!$image_info) {
        return false;
    }
    if ($image_info[0] < $thumb_width) {
        $thumb_width = $image_info[0];
    }
    if ($image_info[1] < $thumb_height) {
        $thumb_height = $image_info[1];
    }
    $thumb_wh = $thumb_width / $thumb_height;
    $src_wh = $image_info[0] / $image_info[1];
    if ($thumb_wh <= $src_wh) {
        $thumb_height = $thumb_width * ($image_info[1] / $image_info[0]);
    } else {
        $thumb_width = $thumb_height * ($image_info[0] / $image_info[1]);
    }
    if ($imgscaleto) {
        $scale = $src_wh > 1 ? $thumb_width : $thumb_height;
    } else {
        $scale = 0;
    }
    $resize_image = new ResizeImage();
    $save_path = rtrim(dirname($source), '/');
    $resize_image->newImg($source, $thumb_width, $thumb_height, $scale, $ext . '.', $save_path);
    return $resize_image->relative_dstimg;
}

/**
 * 取得商品缩略图的完整URL路径，接收图片名称
 * @param string $file
 * @param string $type
 * @return string
 */
function product_thumb($file, $type = '')
{
    $type_array = explode(',', Config::get('product_image_type'));
    if (!in_array($type, $type_array)) {
        $type = '';
    }
    if (empty($file) || !file_exists(UPLOAD_PATH . $file)) {
        return default_product_image($type);
    }
    
    // 本地存储时，增加判断文件是否存在，用默认图代替
    $ext = $type == '' ? '' : ('_' . $type . '_' . $type);
    $thumb_file = ($type == '' ? $file : str_ireplace('.', $ext . '.', $file));
    if (!file_exists(UPLOAD_PATH . $thumb_file)) {
        $thumb_file = resize_image(UPLOAD_PATH . $file, $type, $type, $ext);
        if (!$thumb_file) {
            return default_product_image($type);
        }
    }
    return UPLOAD_URL . $thumb_file;
}

/**
 * 取得商品默认大小图片
 *
 * @param string $key    图片大小 small tiny
 * @return string
 */
function default_product_image($key)
{
    if (empty($key)) {
        return IMAGE_URL . 'base/product_default.png';
    } else {
        return IMAGE_URL . 'base/product_default_' . $key . '_' . $key . '.png';
    }
}

/**
 * 编辑器内容
 *
 * @param int $id 编辑器id名称，与name同名
 * @param string $value 编辑器内容
 * @param string $width 宽 带px
 * @param string $height 高 带px
 * @param string $style 样式内容
 * @param string $upload_state 上传状态，默认是关闭
 */
function show_editor($id, $value = '', $width = '100%', $height = '480px', $style = 'visibility:hidden;', $upload_state = "false", $media_open = false, $type = 'all')
{
    //是否开启多媒体
    $media = '';
    if ($media_open) {
        $media = ", 'flash', 'media'";
    }
    switch($type) {
        case 'basic':
            $items = "['source', '|', 'fullscreen', 'undo', 'redo', 'cut', 'copy', 'paste', '|', 'about']";
            break;
        case 'simple':
            $items = "['source', '|', 'fullscreen', 'undo', 'redo', 'cut', 'copy', 'paste', '|',
            'fontname', 'fontsize', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline',
            'removeformat', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
            'insertunorderedlist', '|', 'emoticons', 'image', 'link', '|', 'about']";
            break;
        default:
            $items = "['source', '|', 'fullscreen', 'undo', 'redo', 'print', 'cut', 'copy', 'paste',
            'plainpaste', 'wordpaste', '|', 'justifyleft', 'justifycenter', 'justifyright',
            'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
            'superscript', '|', 'selectall', 'clearhtml','quickformat','|',
            'formatblock', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold',
            'italic', 'underline', 'strikethrough', 'lineheight', 'removeformat', '|', 'image'".$media.", 'table', 'hr', 'emoticons', 'link', 'unlink', '|', 'about']";
            break;
    }
    //图片、Flash、视频、文件的本地上传都可开启。默认只有图片，要启用其它的需要修改resource\kindeditor\php下的upload_json.php的相关参数
    echo '<textarea id="'. $id .'" name="'. $id .'" style="width:'. $width .';height:'. $height .';'. $style .'">'.$value.'</textarea>';
    echo '
<script src="__RESOURCE__kindeditor/kindeditor-min.js" charset="utf-8"></script>
<script src="__RESOURCE__kindeditor/lang/zh_CN.js" charset="utf-8"></script>
<script>
    var KE;
    KindEditor.ready(function(K) {
        KE = K.create("textarea[name=\''.$id.'\']", {
                        items : '.$items.',
                        cssPath : "__RESOURCE__kindeditor/themes/default/default.css",
                        allowImageUpload : '.$upload_state.',
                        allowFlashUpload : false,
                        allowMediaUpload : false,
                        allowFileManager : false,
                        filterMode : false,
                        syncType:"form",
                        afterCreate : function() {
                            var self = this;
                            self.sync();
                        },
                        afterChange : function() {
                            var self = this;
                            self.sync();
                        },
                        afterBlur : function() {
                            var self = this;
                            self.sync();
                        }
        });
        KE.appendHtml = function(id,val) {
            this.html(this.html() + val);
            if (this.isCreated) {
                var cmd = this.cmd;
                cmd.range.selectNodeContents(cmd.doc.body).collapse(false);
                cmd.select();
            }
            return this;
        }
    });
</script>';
    return;
}

/**
 * 检查身份证号码
 * @param string $idcard
 * @return boolean
 */
function check_idcard($idcard) {
    $idcard = strtoupper($idcard);
    $length = strlen($idcard);

    if (!preg_match('/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/', $idcard)) {
        return false;
    }
    if ($length == 18) {
        $xs = [7,9,10,5,8,4,2,1,6,3,7,9,10,5,8,4,2];
        $lst = '10X98765432';
        $sum_of_id = 0;
        for ($i = 0; $i < 17; $i++) {
            $sum_of_id += intval($idcard[$i]) * $xs[$i];
        }
        $check_num = $lst[$sum_of_id % 11];
        if ($idcard[17] != $check_num) {
            return false;
        }
    }

    if ($length == 18) {
        $birth_date = array (
            substr($idcard, 6, 4),
            substr($idcard, 10, 2),
            substr($idcard, 12, 2)
        );
    }else{
        $birth_date = array (
            '19'.substr($idcard, 6, 2),
            substr($idcard, 8, 2),
            substr($idcard, 10, 2)
        );
    }
    if ($birth_date[1] > 12 || $birth_date[1] <= 0 || $birth_date[2] > 31 || $birth_date[2] <= 0) {
        return false;
    }
    if ($birth_date[0] < date('Y') - 101) {
        return false;
    } elseif ($birth_date[0] . $birth_date[1] . $birth_date[2] > date('Ymd')) {
        return false;
    } else {
        if (days_in_month($birth_date[0], $birth_date[1]) < $birth_date[2]) {
            return false;
        }
    }
    return true;
}

/**
 * 取得月份天数
 * @param number $year
 * @param number $month
 * @return number
 */
function days_in_month($year, $month) {
    if ($month == 2) {
        if (($year % 4 == 0 && $year % 100 != 0) || $year % 400 == 0) {
           return 29;
        } else {
           return 28;
        }
    } elseif (in_array($month, ['1', '3', '5', '7', '8', '10', '12'])) {
       return 31;
    } else {
       return 30;
    }
}

/**
 * 含中文数组按拼音排序
 * @param  array $arr
 * @return array
 */
function asort_with_chinese($arr) {
    $new_array =[];
    foreach ($arr as $key => $value) {
        $new_array[$key] = iconv('UTF-8', 'GBK', $value);
    }
    asort($new_array);
    $return = [];
    foreach ($new_array as $key => $value) {
        $return[$key] = iconv('GBK', 'UTF-8', $value);
    }

    return $return;
}

/**
 * xml转数组
 * @param string $xml
 * @return array|string
 */
function xml_to_array($xml) {
    $reg = "/<(\\w+)[^>]*?>([\\x00-\\xFF]*?)<\\/\\1>/";
    if (preg_match_all($reg, $xml, $matches)) {
        $count = count($matches[0]);
        $arr = [];
        for($i = 0; $i < $count; $i ++) {
            $key = $matches[1][$i];
            $val = xml_to_array($matches[2][$i]); // 递归
            if (array_key_exists($key, $arr)) {
                if (is_array($arr[$key] )) {
                    if (!array_key_exists(0, $arr[$key])) {
                    $arr[$key] = [$arr[$key]];
                    }
                } else {
                    $arr[$key] = [$arr[$key]];
                }
                $arr[$key][] = $val;
            } else {
                $arr[$key] = $val;
            }
        }
        return $arr;
    } else {
        return $xml;
    }
}

/**
 * @param array $data
 * @param string $item
 * @param int $id
 * @return string
 */
function array_to_xml($data, $item = '', $id = 'id')
{
    $xml = $attr = '';
    foreach ($data as $key => $val) {
        if (is_numeric($key) && !is_array($val)) {
            $id && $attr = " {$id}=\"{$key}\"";
            $key         = $item;
        }
        $xml .= "<{$key}{$attr}>";
        $xml .= (is_array($val) || is_object($val)) ? array_to_xml($val, $item, $id) : $val;
        $xml .= "</{$key}>";
    }
    return $xml;
}

/**
 * 隐藏身份证部分信息
 * @param string $identity_number
 * @return string|mixed
 */
function hide_identity($identity_number)
{
    if (empty($identity_number)) {
        return '';
    }
    if (strlen($identity_number) == 15) {
        return str_replace(substr($identity_number, 6, 6), '******', $identity_number);
    } else {
        return str_replace(substr($identity_number, 6, 8), '********', $identity_number);
    }
}

/**
 * 随机返回N个包含数字或字母的字符
 * @param int $length    需要返回的字符个数
 * @param string $chars    指定字符范围
 */
function get_rand_chars($length, $chars = '')
{
    if (empty($chars)) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    }
    $len = strlen($chars) - 1;

    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, $len)];
    }
    return $str;
}

/**
 * 获取当前IP的位置
 * @return string
 */
function get_ip_address()
{
    $default = [
        'country' => '中国',
        'province' => '福建',
        'city' => '福州',
        'province_areacode' => '350000'
    ];
    $ip = request()->ip();
    if ($ip == '127.0.0.1' || strpos($ip, '192.168') !== false) {
        return $default;
    }
    if (session('client_ip_info')) {
        return session('client_ip_info');
    }
    $ip_content = file_get_contents("http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip={$ip}");
    $json_data = explode("=", $ip_content);
    $json_address = substr($json_data[1], 0, - 1);
    $address = (array) json_decode($json_address);
    if (empty($address['province'])) {
        return $default;
    }
    $province_areacode = \think\Db::name('province')->where('province_name', 'like', '%' . $address['province'] . '%')->value('province_areacode');
    if (empty($province_areacode)) {
        return $default;
    }
    $address['province_areacode'] = $province_areacode;
    session('client_ip_info', $address);
    return $address;
}

/**
 * 获取商品单价
 * @param array|\app\common\model\Products $product
 * @param int   $customer_level
 * @param int   $quantity
 * @param array $whole_sales
 * @return float
 */
function get_customer_level_price($product, $customer_level, $quantity = 1, $whole_sales = null)
{
    if (!empty($product['special_price'])) {
        $price = $product['special_price'];
    } else {
        switch ($customer_level) {
            case 1:
                $price = mround($product['level_1_price']);
                break;
            case 2:
                $price = mround($product['level_2_price']);
                break;
            case 3:
                $price = mround($product['level_3_price']);
                break;
            default:
                $price = mround($product['sales_price']);
                break;
        }
    }
    if ($quantity > 1 && !empty($whole_sales)) {
        foreach ($whole_sales as $value) {
            if ($value['end_quantity'] == 0 || $quantity <= $value['end_quantity']) {
                if (!empty($product['special_price'])) {
                    $price = !empty($value['special_price']) ? mround($value['special_price']) : $price;
                } else {
                    switch ($customer_level) {
                        case 1:
                            $price = !empty($value['level_1_price']) ? mround($value['level_1_price']) : $price;
                            break;
                        case 2:
                            $price = !empty($value['level_2_price']) ? mround($value['level_2_price']) : $price;
                            break;
                        case 3:
                            $price = !empty($value['level_3_price']) ? mround($value['level_3_price']) : $price;
                            break;
                        default:
                            $price = !empty($value['sales_price']) ? mround($value['sales_price']) : $price;
                            break;
                    }
                }
                break;
            }
        }
    }
    return $price;
}

/**
 * 根据价格名称获取商品价格
 * @param array $product
 * @param string $name
 * @param int $quantity
 * @param null $whole_sales
 * @param int $decimals
 * @return int|string
 */
function get_product_price_by_name($product, $name, $quantity = 1, $whole_sales = null, $decimals = 2)
{
    $price = isset($product[$name]) ? mround($product[$name], $decimals) : 0;
    if ($quantity > 1 && !empty($whole_sales)) {
        foreach ($whole_sales as $value) {
            if ($value['end_quantity'] == 0 || $quantity <= $value['end_quantity']) {
                $price = $value[$name] != 0 ? mround($value[$name], $decimals) : $price;
                break;
            }
        }
    }
    return $price;
}

/**
 * 获取文件扩展名
 * @param string $file
 * @return string
 */
function get_extension($file)
{
    return substr(strrchr($file, '.'), 1);
}

/**
 * 循环创建目录
 * @param string $dir 待创建的目录
 * @param string $mode 权限
 * @return boolean
 */
function mk_dir($dir, $mode = 0777)
{
    if (is_dir($dir) || @mkdir($dir, $mode)) {
        return true;
    }
    if (!mk_dir(dirname($dir), $mode)) {
        return false;
    }
    return @mkdir($dir, $mode);
}

/**
 * 检查目录是否存在
 * @param string $dir    要检查的目录
 * @param bool $create   不存在是否创建
 * @param string $mode   权限
 * @return bool
 */
function exist_dir($dir, $create = false, $mode = 0777)
{
    if (empty($dir)) {
        return false;
    }
    if (is_dir($dir) || ($create === true && mk_dir($dir, $mode))) {
        return true;
    }
    return false;
}

/**
 * 删除目录
 * @param string $dir    要删除的目录（尾部带反斜杠则只删除目录下的文件）
 */
function del_dir($dir)
{
    $dh = opendir($dir);
    while (($file = readdir($dh)) !== false) {
        if ($file != "." && $file != "..") {
            $fullpath = rtrim($dir, DS) . DS . $file;
            if (!is_dir($fullpath)) {
                unlink($fullpath);
            } else {
                del_dir(rtrim($fullpath, DS));
            }
        }
    }
    closedir($dh);
    //删除当前文件夹：
    if (substr($dir, -1) != DS) {
        rmdir($dir);
    }
}

/**
 * 复制文件到指定目录
 * @param string $source    源文件路径
 * @param string $target    目标文件路径
 * @return bool
 */
function copy_file($source, $target)
{
    if (!is_file($source) || empty($target)) {
        return false;
    }
    $target_dir = dirname($target);
    if (!exist_dir($target_dir, true)) {
        return false;
    }
    return @copy($source, $target);
}

/**
 * 邮箱、手机账号中间字符串以*隐藏
 * @param string $str
 * @return string
 */
function hide_star($str) {
    if (strpos($str, '@')) {
        $email_array = explode("@", $str);
        $prevfix = (strlen($email_array[0]) <=2) ? $email_array[0] : substr($str, 0, 2); //邮箱前缀
        $rs = $prevfix . '****@' . $email_array[1];
    } else {
        $pattern = '/(1[3458]{1}[0-9])[0-9]{5}([0-9]{3})/i';
        if (preg_match($pattern, $str)) {
            $rs = preg_replace($pattern, '$1*****$2', $str); // substr_replace($name,'****',3,4);
        } else {
            $rs = substr($str, 0, 3) . "***" . substr($str, -1);
        }
    }
    return $rs;
}

/**
 * 获取模块静态资源图片路径
 * @return string
 * @param string $filename   图片名称（含扩展名）
 */
function get_static_image($filename, $module = 'mall')
{
    return '__RESOURCE__' . $module . '/images/' . $filename;
}

/**
 * 计算耗时时间
 * @return string
 * @param string $date  创建时间
 */
function get_used_hours($date)
{
    $show_date = '';
    //现在的时间
    $now_date = strtotime(NOW_DATE);
    $create_date = strtotime($date);
    $second = $now_date - $create_date;
    //超过 一小时显示一小时 
    if($second > 3600){//超过1小时的
        $show_date = floor($second / 3600) .'H';
    }elseif($second > 60){//超过1分钟的
        $show_date = floor($second / 60) .'Min';
    }
    return $show_date;
}

