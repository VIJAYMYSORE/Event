<?php
require_once( '../conf/config.php');
// It's probably easy to cache all of this
global $g_routes;

$router = new rest_core(
    $g_routes['url-root'],
    $g_routes['routes'],
    'api',
    'api'
);

// load the routes to be routed. Again, cacheable.
$router->loadRoutes();
// end easy caching part

// This must be done dynamically
$router->routeRequest(null,strtoupper(common_array::get($_REQUEST,'_method',null)));
