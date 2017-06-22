<?php
/**
 * Xml操作类
 * @author Administrator
 *
 */
namespace app\common\library;

class Xml
{
    private $xml_content = '';
    
    function xml($token = '')
    {
        $this->xml_content = '';
    }
    
    function writeXmlDeclaration($is_echo = false)
    {
        if ($is_echo) {
            echo "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\" ?>";
        } else {
            $this->xml_content .= "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\" ?>";
        }
    }
    
    function toUtf8($string)
    {
        return iconv("ISO-8859-1", "UTF-8//TRANSLIT", $string);
    }
    
    function writeStartTag($tag ,$is_echo = false)
    {
        if (!$tag) {
            return false;
        }
        
        if ($is_echo) {
            echo $this->toUtf8('<' . $tag . '>');
        } else {
            $this->xml_content .= $this->toUtf8('<' . $tag . '>');
        }
    }
    
    function writeCloseTag($tag, $is_echo = false)
    {
        if (!$tag) {
            return false;
        }
        
        if ($is_echo) {
            echo $this->toUtf8('</' . $tag . '>');
        } else {
            $this->xml_content .= $this->toUtf8('</' . $tag . '>');
        }
    }
    
    // Output the given tag\value pair
    function writeElement($tag, $value , $is_echo = false)
    {
        $this->writeStartTag($tag, $is_echo);
        if ($tag && !is_array($value)) {
            if ($is_echo) {
                echo htmlspecialchars($value);
            } else {
                $this->xml_content .= htmlspecialchars($value);
            }
        } elseif (is_array($value)) {
            foreach ($value as $k => $v) {
                $this->writeElement($k, $v, $is_echo);
            }
        }
        $this->writeCloseTag($tag, $is_echo);
    }
    
    // Function used to output an error and quit.
    function outputError($code, $error)
    {
        $this->writeStartTag("Error");
        $this->writeElement("Code", $code);
        $this->writeElement("Description", $error);
        $this->writeCloseTag("Error");
    }
    
    public function getXmlContent()
    {
        return $this->xml_content;
    }
    
    public function cleanXmlContent()
    {
        $this->xml_content = '';
    }
    
}
?>