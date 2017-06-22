<?php
namespace app\common\library;

class FileUpload
{
    
    // 要配置的内容
    private $path = "./upload";

    private $allowtype = [
        'jpg',
        'jpeg',
        'gif',
        'png'
    ];

    private $maxsize = 2097152;

    private $israndname = true;

    private $originName;

    private $tmpFileName;

    private $fileType;

    private $fileSize;

    private $imageInfo = null;

    private $newFileName;

    private $errorNum = 0;

    private $errorMess = "";

    /**
     * 改变后的图片宽度
     */
    private $thumb_width = 0;

    /**
     * 改变后的图片高度
     */
    private $thumb_height = 0;

    /**
     * 生成扩缩略图后缀
     */
    private $thumb_ext = false;

    /**
     * 是否允许填充空白，默认允许
     */
    private $filling = true;

    private $is_img = true;

    private $watermark = false;

    private $watermark_char = 'haitaole.com';

    private $watermark_place = 0;

    /**
     * 用于设置成员属性($path, $allowtype, $maxsize, $israndname)
     * 可以通过连贯操作一次设置多个属性值
     * 
     * @param string $key 成员属性（不区分大小写）
     * @param string $val 为成员属性设置的值
     * @return object 返回自己对象$this, 可以用于连贯操作
     */
    function set($key, $val)
    {
        $key = strtolower($key);
        if (array_key_exists($key, get_class_vars(get_class($this)))) {
            $this->setOption($key, $val);
        }
        return $this;
    }

    /**
     * 调用该方法上传文件
     * Enter description here .
     * ..
     * 
     * @param string $fileField 上传文件的表单名称
     */
    function upload($fileField)
    {
        $return = true;
        if (! $this->checkFilePath()) {
            $this->errorMess = $this->getError();
            return false;
        }
        if (empty($_FILES[$fileField])) {
            $this->setOption('errorNum', 4);
            return false;
        }
        // 将文件上传的信息取出赋给变量
        $name = $_FILES[$fileField]['name'];
        $tmp_name = $_FILES[$fileField]['tmp_name'];
        $size = $_FILES[$fileField]['size'];
        $error = $_FILES[$fileField]['error'];
        if (is_array($name)) {
            $errors = array();
            // 多个文件上传则循环处理，这个循环只有检查上传文件的作用，并没有真正上传
            for ($i = 0; $i < count($name); $i ++) {
                if ($this->setFiles($name[$i], $tmp_name[$i], $size[$i], $error[$i])) {
                    if (! $this->checkFileSize() || ! $this->checkFileType() || ! $this->checkImage()) {
                        $errors[] = $this->getError();
                        $return = false;
                    }
                } else {
                    $errors[] = $this->getError();
                    $return = false;
                }
                // 如果有问题，则重新初始化属性
                if (! $return) {
                    $this->setFiles();
                }
            }
            if ($return) {
                // 存放所有上传后文件名的变量数组
                $fileNames = array();
                // 如果上传的多个文件都是合法的，则通过循环向服务器上传文件
                for ($i = 0; $i < count($name); $i ++) {
                    if ($this->setFiles($name[$i], $tmp_name[$i], $size[$i], $error[$i])) {
                        $this->setNewFileName();
                        if (! $this->copyFile()) {
                            $errors[] = $this->getError();
                            $return = false;
                        }
                        $fileNames[] = $this->newFileName;
                    }
                }
                $this->newFileName = $fileNames;
            }
            $this->errorMess = $errors;
            return $return;
        } else {
            // 设置文件信息
            if ($this->setFiles($name, $tmp_name, $size, $error)) {
                if ($this->checkFileSize() && $this->checkFileType() && $this->checkImage()) {
                    $this->setNewFileName();
                    if ($this->copyFile()) {
                        return true;
                    } else {
                        $return = false;
                    }
                } else {
                    $return = false;
                }
            } else {
                $return = false;
            }
        }
        
        if (! $return) {
            $this->errorMess = $this->getError();
        }
        
        return $return;
    }
    
    // 获取上传后的文件名称
    public function getFileName()
    {
        return $this->newFileName;
    }
    
    // 获取上传后的文件名称
    public function getOriginName()
    {
        return $this->originName;
    }
    
    // 上传失败后，调用该方法则返回，上传出错信息
    public function getErrorMsg()
    {
        return $this->errorMess;
    }
    
    // 设置上传出错信息
    public function getError()
    {
        $str = "上传文件<font color='red'>{$this->originName}</font>时出错：";
        switch ($this->errorNum) {
            case 4:
                $str .= "没有文件被上传";
                break;
            case 3:
                $str .= "文件只有部分被上传";
                break;
            case 2:
                $str .= "上传文件的大小超过了HTML表单中MAX_FILE_SIZE选项指定的值";
                break;
            case 1:
                $str .= "上传的文件超过了php.ini中upload_max_filesize选项限制的值";
                break;
            case - 1:
                $str .= "未允许的类型";
                break;
            case - 2:
                $str .= "文件过大， 上传的文件不能超过2M";
                break;
            case - 3:
                $str .= "上传失败";
                break;
            case - 4:
                $str .= "建立存放上传文件目录失败，请重新指定上传目录";
                break;
            case - 5:
                $str .= "必须指定上传文件的路径";
                break;
            case - 6:
                $str .= "添加水印失败";
                break;
            default:
                $str .= "未知错误";
        }
        return $str . "<br>";
    }
    
    // 设置和$_FILES有关的内容
    private function setFiles($name = "", $tmp_name = "", $size = 0, $error = 0)
    {
        $this->setOption('errorNum', $error);
        if (! is_uploaded_file($tmp_name) || $error) {
            return false;
        }
        $this->setOption('originName', $name);
        $this->setOption('tmpFileName', $tmp_name);
        $aryStr = explode(".", $name);
        $this->setOption("fileType", strtolower($aryStr[count($aryStr) - 1]));
        $this->setOption("fileSize", $size);
        return true;
    }
    
    // 为单个成员属性设置值
    private function setOption($key, $val)
    {
        $this->$key = $val;
    }
    
    // 设置上传后的文件名称
    private function setNewFileName()
    {
        if ($this->israndname) {
            $this->setOption('newFileName', $this->proRandName());
        } else {
            $this->setOption('newFileName', $this->originName);
        }
    }
    
    // 检查上传的文件是否是合法的类型
    private function checkFileType()
    {
        if (in_array(strtolower($this->fileType), $this->allowtype)) {
            return true;
        } else {
            $this->setOption('errorNum', - 1);
            return false;
        }
    }
    
    // 检查上传的文件是否是允许的大小
    private function checkFileSize()
    {
        if ($this->fileSize > $this->maxsize) {
            $this->setOption('errorNum', - 5);
            return false;
        } else {
            return true;
        }
    }
    
    // 检查是否为有效图片
    private function checkImage()
    {
        if ($this->is_img && ! $this->imageInfo = @getimagesize($this->tmpFileName)) {
            $this->setOption('errorNum', - 1);
            return false;
        } else {
            return true;
        }
    }
    
    // 检查是否有存放上传文件的目录
    private function checkFilePath()
    {
        if (empty($this->path)) {
            $this->setOption('errorNum', - 5);
            return false;
        }
        if (! file_exists($this->path) || ! is_writable($this->path)) {
            if (! @mkdir($this->path, 0755, true)) {
                $this->setOption('errorNum', - 4);
                return false;
            }
        }
        return true;
    }
    
    // 设置随机文件名
    private function proRandName()
    {
        $fileName = date('YmdHis') . rand(100, 999);
        return $fileName . '.' . $this->fileType;
    }
    
    // 复制上传文件到指定的位置
    private function copyFile()
    {
        if ($this->errorNum) {
            return false;
        }
        // 是否需要生成缩略图
        $ifresize = false;
        if ($this->thumb_width && $this->thumb_height && $this->thumb_ext) {
            $thumb_width = explode(',', $this->thumb_width);
            $thumb_height = explode(',', $this->thumb_height);
            $thumb_ext = explode(',', $this->thumb_ext);
            if (count($thumb_width) == count($thumb_height) && count($thumb_height) == count($thumb_ext)) {
                $ifresize = true;
            }
        }
        
        // 计算缩略图的尺寸
        if ($ifresize) {
            for ($i = 0; $i < count($thumb_width); $i ++) {
                $imgscaleto = ($thumb_width[$i] == $thumb_height[$i]);
                if ($this->imageInfo[0] < $thumb_width[$i])
                    $thumb_width[$i] = $this->imageInfo[0];
                if ($this->imageInfo[1] < $thumb_height[$i])
                    $thumb_height[$i] = $this->imageInfo[1];
                $thumb_wh = $thumb_width[$i] / $thumb_height[$i];
                $src_wh = $this->imageInfo[0] / $this->imageInfo[1];
                if ($thumb_wh <= $src_wh) {
                    $thumb_height[$i] = $thumb_width[$i] * ($this->imageInfo[1] / $this->imageInfo[0]);
                } else {
                    $thumb_width[$i] = $thumb_height[$i] * ($this->imageInfo[0] / $this->imageInfo[1]);
                }
                if ($imgscaleto) {
                    $scale[$i] = $src_wh > 1 ? $thumb_width[$i] : $thumb_height[$i];
                } else {
                    $scale[$i] = 0;
                }
            }
        }
        
        $path = rtrim($this->path, '/') . '/';
        $path .= $this->newFileName;
        if (@move_uploaded_file($this->tmpFileName, $path)) {
            // 添加水印
            if ($this->watermark === true) {
                $result = $this->addCharWatermark($path, '', $this->watermark_char, $this->watermark_place);
                if (!$result) {
                    @unlink($path);
                    $this->setOption('errorNum', - 6);
                    return false;
                }
            }
            // 产生缩略图
            if ($ifresize) {
                $resizeImage = new ResizeImage();
                $save_path = rtrim($this->path, '/');
                for ($i = 0; $i < count($thumb_width); $i ++) {
                    $resizeImage->newImg($path, $thumb_width[$i], $thumb_height[$i], $scale[$i], $thumb_ext[$i] . '.', $save_path, $this->filling);
                    if ($i == 0) {
                        $resize_image = explode('/', $resizeImage->relative_dstimg);
                        $this->thumb_image = $resize_image[count($resize_image) - 1];
                    }
                }
            }
            return true;
        } else {
            $this->setOption('errorNum', - 3);
            return false;
        }
    }
    
    /**
     * 为图片增加水印
     * @param    $filename            原始图片文件名，包含完整路径
     * @param    $targetFile            指定添加了水印之后的文件路径（包含生成的文件的名称）  如果为空，将覆盖原始图片$filename
     * @param    $char                水印文字
     * @param   $watermarkPlace     文字位置（1：左上方， 2：右上方，3：左下方，4：右下方，其他：图中间）
     * @return    如果成功则返回文件路径，否则返回false
     */
    function addCharWatermark($filename, $targetFile = '', $char = 'haitaole', $watermarkPlace = 0)
    {
        // 是否安装了GD
        $gd = $this->getGDVersion();
        if ($gd == 0) {
            return false;
        }
        
        // 文件是否存在
        if (!file_exists($filename)) {
            return false;
        }
        
        // 根据文件类型获得原始图片的操作句柄
        $sourceInfo = @getimagesize($filename);
        $sourceHandle = $this->imgResource($filename, $sourceInfo[2]);
        if (!$sourceHandle) {
            return false;
        }
        
        // 根据系统设置获得水印的位置
        $fontSize = 25;
        $font = dirname(__FILE__) . '/fonts/simsun.ttc';//字体
        $box = imagettfbbox($fontSize, 0, $font, $char);
        $logow = max($box[2], $box[4]) - min($box[0], $box[6]);
        $logoh = max($box[1], $box[3]) - min($box[5], $box[7]);
        switch ($watermarkPlace) {
            case '1':
                $x = 0;
                $y = 10;
                break;
            case '2':
                $x = $sourceInfo[0] - $logow;
                $y = 10;
                break;
            case '3':
                $x = 0;
                $y = $sourceInfo[1] - $logoh + 10;
                break;
            case '4':
                $x = $sourceInfo[0] - $logow;
                $y = $sourceInfo[1] - $logoh + 10;
                break;
            default:
                $x = ($sourceInfo[0] / 2) - ($logow / 2);
                $y = ($sourceInfo[1] / 2) - ($logoh / 2) + 10;
                break;
        }
    
        //打上文字
        $black = imagecolorallocate($sourceHandle, 0xa1, 0x5f, 0xa6);//字体颜色
        imagefttext($sourceHandle, $fontSize, 0, $x, $y, $black, $font, $char);
        
        $target = empty($targetFile) ? $filename : $targetFile;
        
        switch ($sourceInfo[2]) {
            case 'image/gif':
            case 1:
                imagegif ($sourceHandle, $target);
                break;
            case 'image/pjpeg':
            case 'image/jpeg':
            case 2:
                imagejpeg($sourceHandle, $target);
                break;
            case 'image/x-png':
            case 'image/png':
            case 3:
                imagepng($sourceHandle, $target);
                break;
            default:
                return false;
                break;
        }
        
        imagedestroy($sourceHandle);
        
        if (!file_exists($target)) {
            return false;
        }
        
        return $target;
    }
    
    /**
     * 获取GD库版本
     * 0 表示没有 GD 库，1 表示 GD 1.x，2 表示 GD 2.x
     */
    private function getGDVersion()
    {
        $version = -1;
        
        if ($version >= 0) {
            return $version;
        }
        
        if (!extension_loaded('gd')) {
            $version = 0;
        } else {
            // 尝试使用gd_info函数
            if (PHP_VERSION >= '4.3') {
                if (function_exists('gd_info')) {
                    $ver_info = gd_info();
                    preg_match('/\d/', $ver_info['GD Version'], $match);
                    $version = $match[0];
                } else {
                    if (function_exists('imagecreatetruecolor')) {
                        $version = 2;
                    } elseif (function_exists('imagecreate')) {
                        $version = 1;
                    }
                }
            } else {
                if (preg_match('/phpinfo/', ini_get('disable_functions'))) {
                    /* 如果phpinfo被禁用，无法确定gd版本 */
                    $version = 1;
                } else {
                    // 使用phpinfo函数
                    ob_start();
                    phpinfo(8);
                    $info = ob_get_contents();
                    ob_end_clean();
                    $info = stristr($info, 'gd version');
                    preg_match('/\d/', $info, $match);
                    $version = $match[0];
                }
            }
        }
        
        return $version;
    }
    
    /**
     * 根据来源文件的文件类型创建一个图像操作的标识符
     * @param  string  $imgFile    图片文件的路径
     * @param  string  $mimeType    图片文件的文件类型
     * @return  resource    如果成功则返回图像操作标志符，反之则返回错误代码
     */
    public function imgResource($imgFile, $mimeType)
    {
        ini_set('memory_limit', '768M');
        switch ($mimeType) {
            case 1:
            case 'image/gif':
                $res = @imagecreatefromgif ($imgFile);
                break;
            case 2:
            case 'image/pjpeg':
            case 'image/jpeg':
                $res = @imagecreatefromjpeg($imgFile);
                break;
            case 3:
            case 'image/x-png':
            case 'image/png':
                $res = @imagecreatefrompng($imgFile);
                break;
            default:
                return false;
        }
        
        return $res;
    }
    
    /**
     * 删除指定文件
     * @param  string  $filename
     */
    public function deleteFile($filename)
    {
        if (!file_exists($filename)) {
            return true;
        }
        
        unlink($filename);
        return true;
    }
    
    /**
     * 图片复制
     */
    public function imageCopy($orig_img, $dest_img)
    {
        $orig_img = trim($orig_img);
        $dest_img = trim($dest_img);
        
        if (!$orig_img || !$dest_img) {
            return false;
        }
        
        if (!is_file($orig_img)) {
            return false;
        }
        
        //检查并创建目标文件的路径
        $dest_dir = dirname($dest_img);
        $this->dirsCheckAndCreate($dest_dir);
        
        if (!is_dir($dest_dir)) {
            return false;
        }
        
        return copy($orig_img, $dest_img);
    }
    
    /**
     * 判断一个目录是否存在， 不存在则递归生成
     */
    public function dirsCheckAndCreate($dir)
    {
        if (!$dir) {
            return false;
        }
        
        $dir_arr = explode('/', $dir);
        $dir_str = '';
        foreach ($dir_arr as $tmp_dir) {
            if (!$tmp_dir) {
                continue;
            }
            $dir_str .= $dir_arr.'/';
            $this->dirCheckAndCreate($dir);
        }
        
        return $dir_str;
    }
    
    /**
     * 判断一个目录是存在，不存在则生成
     */
    private function dirCheckAndCreate($dir)
    {
        if (!$dir) {
            return false;
        }
        
        if (!is_dir($dir)) {
            return @mkdir($dir, 0775, true);
        }
        
        return true;
    }
    
}