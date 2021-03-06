<?php
/**
 * Wildflower bootstrap file
 * 
 * This file should be included in app/bootsrap.php. It connect WF
 * with your application.
 *
 * @package wildflower
 */

// Wildflower plugin paths
define('WILDFLOWER_PLUGIN', APP . 'plugins' . DS . 'wildflower');
define('SETTINGS_CACHE_FILE', TMP . 'settings' . DS . 'cache'); // @depracated

// Include Wildflower config
require_once(dirname(__FILE__) . DS . 'core.php');

/**
 * Wrapper for application encoding respecting htmlspecialchars
 * 
 * @param string $string
 * @return string Text safe to display in HTML
 * @package wildflower
 */
function hsc($string) {
	return htmlspecialchars($string, ENT_QUOTES, Configure::read('App.encoding'));
}

App::import ('Vendor', 'FirePHP', array('file' => 'FirePHP.class.php'));
/**
 * FirePHP debug
 *
 * @param mixed Variables to output to FireBug console
 */
function fb() {
    $instance = FirePHP::getInstance(true);
    $args = func_get_args();
    return call_user_func_array(array($instance,'fb'),$args);
    return true;
}

function __autoload($className) {
    /** Wildfower callbacks auto-loading */
    $fileName = Inflector::underscore($className) . '_callback.php';
    $filePath = APP . 'controllers' . DS . 'wildflower_callbacks' . DS . $fileName;

    if (file_exists($filePath)) {
        require_once($filePath);
    }
}

class WildflowerCallback {}
