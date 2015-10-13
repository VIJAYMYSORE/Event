<?php

const BASE_DIR = "/home2/vijay/public_html/api/events/Event";

require_once("../vendor/autoload.php");
/**
 * This autoloader uses the include path to load classes.
 * It will work with underscore separated classes and namespaces.
 * All class file paths are expected to match the case of the class name.
 * Legacy support is maintained for class file paths that have been lower cased.
 * Legacy support is maintained for the old class map table.
 */
spl_autoload_register(function ($class_name) {
    $class_path = str_replace(array('_', '\\'), DIRECTORY_SEPARATOR, $class_name) . '.php';
    set_include_path(BASE_DIR);
    $path = stream_resolve_include_path($class_path);
    $path = BASE_DIR . "/" . $class_path;
    if ($path) {
        require_once $path;
        return;
    }
});

require_once("../vendor/autoload.php");



//The database connection timeout in seconds.
define('DATABASE_CONNECTION_TIMEOUT', 15);
define('DB_MAIN_RW', 'main_rw');
global $gDBConnectInfo;
$gDBConnectInfo = array(
    DB_MAIN_RW          => array('host'=>'localhost',    'port'=>'3306',    'db'=>'vijay_event',    'user'=>'vijay_event',    'pwd'=>'Event123',    'timeout'=>200, 'persistant'=>0)
);




