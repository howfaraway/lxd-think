<?php
namespace app\common\library;

use think\Config;

class PurifierSecurity
{

    /**
     *
     * @var HTMLPurifier singleton instance of the HTML Purifier object
     */
    protected static $htmlpurifier;

    /**
     * Purifier factory
     */
    public static function factory()
    {
        
        // Create a new configuration object
        $config = \HTMLPurifier_Config::createDefault();
        
        if (! Config::get('purifier.finalize')) {
            // Allow configuration to be modified
            $config->autoFinalize = FALSE;
        }
        
        // Use the same character set as Kohana
        $config->set('Core.Encoding', 'UTF-8');
        
        if (is_array($settings = Config::get('purifier.settings'))) {
            // Load the settings
            $config->loadArray($settings);
        }
        
        // Configure additional options
        $config = self::configure($config);
        
        // Create the purifier instance
        return new \HTMLPurifier($config);
    }

    /**
     *
     *
     * $purifier = Security::htmlpurifier();
     *
     * @return \HTMLPurifier
     */
    public static function htmlpurifier()
    {
        if (! self::$htmlpurifier) {
            // Create the purifier instance
            self::$htmlpurifier = self::factory();
        }
        
        return self::$htmlpurifier;
    }

    /**
     *
     * @param HTMLPurifier_Config configuration object
     * @return \HTMLPurifier_Config
     */
    public static function configure(\HTMLPurifier_Config $config)
    {
        return $config;
    }

    /**
     *
     * @param mixed text to clean, or an array to clean recursively
     * @return mixed
     */
    public static function xssClean($str)
    {
        if (is_array($str)) {
            foreach ($str as $i => $s) {
                // Recursively clean arrays
                $str[$i] = self::xssClean($s);
            }
            
            return $str;
        }
        
        // Load HTML Purifier
        $purifier = self::htmlpurifier();
        
        // Clean the HTML and return it
        return $purifier->purify($str);
    }
}