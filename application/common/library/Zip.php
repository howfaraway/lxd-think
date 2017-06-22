<?php
/**
 * Zip操作类
 * @author LinXiangDi
 *
 */
namespace app\common\library;

class Zip
{
    /**
     * Zip data in string form
     *
     * @var string
     */
    public $zipdata = '';
    
    /**
     * Zip data for a directory in string form
     *
     * @var string
     */
    public $directory = '';
    
    /**
     * Number of files/folder in zip file
     *
     * @var int
     */
    public $entries = 0;
    
    /**
     * Number of files in zip
     *
     * @var int
     */
    public $file_num = 0;
    
    /**
     * relative offset of local header
     *
     * @var int
     */
    public $offset = 0;
    
    /**
     * Reference to time at init
     *
     * @var int
     */
    public $now;
    
    /**
     * The level of compression
     *
     * Ranges from 0 to 9, with 9 being the highest level.
     *
     * @var    int
     */
    public $compression_level = 2;
    
    /**
     * Initialize zip compression class
     *
     * @return    void
     */
    public function __construct()
    {
        $this->now = time();
        //log_message('info', 'Zip Compression Class Initialized');
    }
    // --------------------------------------------------------------------
    
    /**
     * Add Directory
     *
     * Lets you add a virtual directory into which you can place files.
     *
     * @param    mixed    $directory    the directory name. Can be string or array
     * @return    void
     */
    public function add_dir($directory)
    {
        foreach ((array) $directory as $dir)
        {
            if ( ! preg_match('|.+/$|', $dir))
            {
                $dir .= '/';
            }
    
            $dir_time = $this->_get_mod_time($dir);
            $this->_add_dir($dir, $dir_time['file_mtime'], $dir_time['file_mdate']);
        }
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Get file/directory modification time
     *
     * If this is a newly created file/dir, we will set the time to 'now'
     *
     * @param    string    $dir    path to file
     * @return    array    filemtime/filemdate
     */
    protected function _get_mod_time($dir)
    {
        // filemtime() may return false, but raises an error for non-existing files
        $date = file_exists($dir) ? getdate(filemtime($dir)) : getdate($this->now);
    
        return array(
            'file_mtime' => ($date['hours'] << 11) + ($date['minutes'] << 5) + $date['seconds'] / 2,
            'file_mdate' => (($date['year'] - 1980) << 9) + ($date['mon'] << 5) + $date['mday']
        );
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Add Directory
     *
     * @param    string    $dir    the directory name
     * @param    int    $file_mtime
     * @param    int    $file_mdate
     * @return    void
     */
    protected function _add_dir($dir, $file_mtime, $file_mdate)
    {
        $dir = str_replace('\\', '/', $dir);
    
        $this->zipdata .=
        "\x50\x4b\x03\x04\x0a\x00\x00\x00\x00\x00"
            .pack('v', $file_mtime)
            .pack('v', $file_mdate)
            .pack('V', 0) // crc32
            .pack('V', 0) // compressed filesize
            .pack('V', 0) // uncompressed filesize
            .pack('v', strlen($dir)) // length of pathname
            .pack('v', 0) // extra field length
            .$dir
            // below is "data descriptor" segment
        .pack('V', 0) // crc32
        .pack('V', 0) // compressed filesize
        .pack('V', 0); // uncompressed filesize
    
        $this->directory .=
        "\x50\x4b\x01\x02\x00\x00\x0a\x00\x00\x00\x00\x00"
            .pack('v', $file_mtime)
            .pack('v', $file_mdate)
            .pack('V',0) // crc32
            .pack('V',0) // compressed filesize
            .pack('V',0) // uncompressed filesize
            .pack('v', strlen($dir)) // length of pathname
            .pack('v', 0) // extra field length
            .pack('v', 0) // file comment length
            .pack('v', 0) // disk number start
            .pack('v', 0) // internal file attributes
            .pack('V', 16) // external file attributes - 'directory' bit set
            .pack('V', $this->offset) // relative offset of local header
            .$dir;
    
        $this->offset = strlen($this->zipdata);
        $this->entries++;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Add Data to Zip
     *
     * Lets you add files to the archive. If the path is included
     * in the filename it will be placed within a directory. Make
     * sure you use add_dir() first to create the folder.
     *
     * @param    mixed    $filepath    A single filepath or an array of file => data pairs
     * @param    string    $data        Single file contents
     * @return    void
     */
    public function add_data($filepath, $data = NULL)
    {
        if (is_array($filepath))
        {
            foreach ($filepath as $path => $data)
            {
                $file_data = $this->_get_mod_time($path);
                $this->_add_data($path, $data, $file_data['file_mtime'], $file_data['file_mdate']);
            }
        }
        else
        {
            $file_data = $this->_get_mod_time($filepath);
            $this->_add_data($filepath, $data, $file_data['file_mtime'], $file_data['file_mdate']);
        }
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Add Data to Zip
     *
     * @param    string    $filepath    the file name/path
     * @param    string    $data    the data to be encoded
     * @param    int    $file_mtime
     * @param    int    $file_mdate
     * @return    void
     */
    protected function _add_data($filepath, $data, $file_mtime, $file_mdate)
    {
        $filepath = str_replace('\\', '/', $filepath);
        $uncompressed_size = strlen($data);
        $crc32  = crc32($data);
        $gzdata = substr(gzcompress($data, $this->compression_level), 2, -4);
        $compressed_size = strlen($gzdata);
    
        $this->zipdata .=
        "\x50\x4b\x03\x04\x14\x00\x00\x00\x08\x00"
            .pack('v', $file_mtime)
            .pack('v', $file_mdate)
            .pack('V', $crc32)
            .pack('V', $compressed_size)
            .pack('V', $uncompressed_size)
            .pack('v', strlen($filepath)) // length of filename
            .pack('v', 0) // extra field length
            .$filepath
            .$gzdata; // "file data" segment
    
        $this->directory .=
        "\x50\x4b\x01\x02\x00\x00\x14\x00\x00\x00\x08\x00"
            .pack('v', $file_mtime)
            .pack('v', $file_mdate)
            .pack('V', $crc32)
            .pack('V', $compressed_size)
            .pack('V', $uncompressed_size)
            .pack('v', strlen($filepath)) // length of filename
            .pack('v', 0) // extra field length
            .pack('v', 0) // file comment length
            .pack('v', 0) // disk number start
            .pack('v', 0) // internal file attributes
            .pack('V', 32) // external file attributes - 'archive' bit set
            .pack('V', $this->offset) // relative offset of local header
            .$filepath;
    
        $this->offset = strlen($this->zipdata);
        $this->entries++;
        $this->file_num++;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Read the contents of a file and add it to the zip
     *
     * @param    string    $path
     * @param    bool    $archive_filepath
     * @return    bool
     */
    public function read_file($path, $archive_filepath = FALSE)
    {
        if (file_exists($path) && FALSE !== ($data = file_get_contents($path)))
        {
            if (is_string($archive_filepath))
            {
                $name = str_replace('\\', '/', $archive_filepath);
            }
            else
            {
                $name = str_replace('\\', '/', $path);
    
                if ($archive_filepath === FALSE)
                {
                    $name = preg_replace('|.*/(.+)|', '\\1', $name);
                }
            }
    
            $this->add_data($name, $data);
            return TRUE;
        }
    
        return FALSE;
    }
    
    // ------------------------------------------------------------------------
    
    /**
     * Read a directory and add it to the zip.
     *
     * This function recursively reads a folder and everything it contains (including
     * sub-folders) and creates a zip based on it. Whatever directory structure
     * is in the original file path will be recreated in the zip file.
     *
     * @param    string    $path    path to source directory
     * @param    bool    $preserve_filepath
     * @param    string    $root_path
     * @return    bool
     */
    public function read_dir($path, $preserve_filepath = TRUE, $root_path = NULL)
    {
        $path = rtrim($path, '/\\').DIRECTORY_SEPARATOR;
        if ( ! $fp = @opendir($path))
        {
            return FALSE;
        }
    
        // Set the original directory root for child dir's to use as relative
        if ($root_path === NULL)
        {
            $root_path = dirname($path).DIRECTORY_SEPARATOR;
            $root_path = str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $root_path);
        }
    
        while (FALSE !== ($file = readdir($fp)))
        {
            if ($file[0] === '.' || $file[0] === '..')
            {
                continue;
            }
    
            if (is_dir($path.$file))
            {
                $this->read_dir($path.$file.DIRECTORY_SEPARATOR, $preserve_filepath, $root_path);
                $name = str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $path);
                if ($preserve_filepath === FALSE)
                {
                    $name = str_replace($root_path, '', $name);
                }
    
                $this->add_data($name.$file.DIRECTORY_SEPARATOR, NULL);
            }
            elseif (FALSE !== ($data = file_get_contents($path.$file)))
            {
                $name = str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $path);
                if ($preserve_filepath === FALSE)
                {
                    $name = str_replace($root_path, '', $name);
                }
    
                $this->add_data($name.$file, $data);
            }
        }
    
        closedir($fp);
        return TRUE;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Get the Zip file
     *
     * @return    string    (binary encoded)
     */
    public function get_zip()
    {
        // Is there any data to return?
        if ($this->entries === 0)
        {
            return FALSE;
        }
    
        return $this->zipdata
        .$this->directory."\x50\x4b\x05\x06\x00\x00\x00\x00"
            .pack('v', $this->entries) // total # of entries "on this disk"
            .pack('v', $this->entries) // total # of entries overall
            .pack('V', strlen($this->directory)) // size of central dir
            .pack('V', strlen($this->zipdata)) // offset to start of central dir
            ."\x00\x00"; // .zip file comment length
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Write File to the specified directory
     *
     * Lets you write a file
     *
     * @param    string    $filepath    the file name
     * @return    bool
     */
    public function archive($filepath)
    {
        if ( ! ($fp = @fopen($filepath, 'w+b')))
        {
            return FALSE;
        }
    
        flock($fp, LOCK_EX);
    
        for ($result = $written = 0, $data = $this->get_zip(), $length = strlen($data); $written < $length; $written += $result)
        {
            if (($result = fwrite($fp, substr($data, $written))) === FALSE)
            {
                break;
            }
        }
    
        flock($fp, LOCK_UN);
        fclose($fp);
    
        return is_int($result);
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Download
     *
     * @param    string    $filename    the file name
     * @return    void
     */
    public function download($filename = 'backup.zip')
    {
        if ( ! preg_match('|.+?\.zip$|', $filename))
        {
            $filename .= '.zip';
        }
    
        //get_instance()->load->helper('download');
        $get_zip = $this->get_zip();
        $zip_content =& $get_zip;
    
        $this->force_download($filename, $zip_content);
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Initialize Data
     *
     * Lets you clear current zip data. Useful if you need to create
     * multiple zips with different data.
     *
     * @return    CI_Zip
     */
    public function clear_data()
    {
        $this->zipdata = '';
        $this->directory = '';
        $this->entries = 0;
        $this->file_num = 0;
        $this->offset = 0;
        return $this;
    }
    
    function force_download($filename = '', $data = '', $set_mime = FALSE)
    {
        if ($filename === '' OR $data === '')
        {
            return;
        }
        elseif ($data === NULL)
        {
            if (@is_file($filename) && ($filesize = @filesize($filename)) !== FALSE)
            {
                $filepath = $filename;
                $filename = explode('/', str_replace(DIRECTORY_SEPARATOR, '/', $filename));
                $filename = end($filename);
            }
            else
            {
                return;
            }
        }
        else
        {
            $filesize = strlen($data);
        }
    
        // Set the default MIME type to send
        $mime = 'application/zip';
    
        $x = explode('.', $filename);
        $extension = end($x);
    
    
    
        /* It was reported that browsers on Android 2.1 (and possibly older as well)
         * need to have the filename extension upper-cased in order to be able to
         * download it.
         *
         * Reference: http://digiblog.de/2011/04/19/android-and-the-download-file-headers/
        */
        if (count($x) !== 1 && isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Android\s(1|2\.[01])/', $_SERVER['HTTP_USER_AGENT']))
        {
            $x[count($x) - 1] = strtoupper($extension);
            $filename = implode('.', $x);
        }
    
        if ($data === NULL && !isset($filepath) && ($fp = @fopen($filepath, 'rb')) === FALSE)
        {
            return;
        }
    
        // Clean output buffer
        if (ob_get_level() !== 0 && @ob_end_clean() === FALSE)
        {
            @ob_clean();
        }
    
        // Generate the server headers
        header('Content-Type: '.$mime);
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Expires: 0');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.$filesize);
    
        // Internet Explorer-specific headers
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE)
        {
            header('Cache-Control: no-cache, no-store, must-revalidate');
        }
    
        header('Pragma: no-cache');
    
        // If we have raw data - just dump it
        if ($data !== NULL)
        {
            print($data);
        }
    
        // Flush 1MB chunks of data
        if (isset($fp)) {
            while ( ! feof($fp) && ($data = fread($fp, 1048576)) !== FALSE)
            {
                echo $data;
            }
            fclose($fp);
        }
    
    }
    
    /**
     * 根据目录压缩文件并下载
     * @param string $dir
     * @return boolean
     */
    public function zipAndDownload($dir)
    {
        $dir = trim($dir);
        if (empty($dir)) {
            return false;
        }
        $res = $this->read_dir($dir, FALSE);
        if (!$res) {
            return false;
        }
    
        $newName = strrchr($dir, '/');
        if ($newName) {
            $downloadName = substr($newName, 1);
        } else {
            $downloadName = 'zip' . date("YmdHis", time());
        }
    
        $this->download($downloadName . '.zip');
    }
    
}