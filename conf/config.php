<?php

const BASE_DIR = "/home2/vijay/lib";

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
    if ($path) {
        require_once $path;
        return;
    }
});

require_once("../vendor/autoload.php");

global $g_routes;
$g_routes = array(
                "routes" => array(
                    "url-root" => "/api/v1",
                    "controller-prefix" => "api",
                    "routes" => array(
                        "/health" => "health"
                    )
                )
            );





