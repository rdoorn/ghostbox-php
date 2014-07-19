<?php


# Define where the PHP scripts are
define('PHPCORE_DIR', $_SERVER['DOCUMENT_ROOT']?$_SERVER['DOCUMENT_ROOT'].'/../php':'/storage/shared/www/ghostbox.org/www/php');

require_once(PHPCORE_DIR.'/config/config.php');
require_once(PHPCORE_DIR.'/includes/error_handler.php');
require_once(PHPCORE_DIR.'/includes/general_functions.php');

# start session before loading classes
session_start();

# Autoload classes
spl_autoload_register(function ($class) {
    $path = str_replace('\\', '/', $class);
    // default classes
    debug(2, "Autoloading ".PHPCORE_DIR."/classes/{$path}.class.php");
    if (file_exists(PHPCORE_DIR."/classes/{$path}.class.php")) {
        include_once PHPCORE_DIR."/classes/{$path}.class.php";
    }

    // plugins
    debug(2, "Autoloading ".PHPCORE_DIR.'/plugins/'.dirname($path).'/config.php');
    if (file_exists(PHPCORE_DIR.'/plugins/'.dirname($path).'/config.php')) { 
        include_once PHPCORE_DIR.'/plugins/'.dirname($path).'/config.php'; 
    }
    debug(2, "Autoloading ".PHPCORE_DIR."/plugins/{$path}.class.php");
    if (file_exists(PHPCORE_DIR."/plugins/{$path}.class.php")) {
        include_once PHPCORE_DIR."/plugins/{$path}.class.php";
    }
});

# Check requirements
version_compare(PHP_VERSION, "5.5", "<") and
  exit("Icarus requires PHP 5.5 or newer (you're using " . PHP_VERSION  . ")");

# Security measures
header("X-Frame-Options: SAMEORIGIN");

# load plugins
$hook = new hooks();

$hook->load_plugins(PHPCORE_DIR.'/plugins');


?>
