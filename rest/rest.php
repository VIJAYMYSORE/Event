<?php
require_once( '../conf/config.php');


$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$host = $_SERVER['SERVER_ADDR'];
 //var_dump($_SERVER);
 //var_dump($_REQUEST);
 //var_dump($_POST);
$entityBody = file_get_contents('php://input');
global $g_body;
$g_body = json_decode($entityBody);
//global $HTTP_POST_VARS;
//var_dump($HTTP_POST_VARS);

$handlerMap = array(
    "/api/health|GET"=>"api_health|find",
    "/api/user|POST"=>"api_user|create"
);
// temp hack to fix the query parameter problem
$request = explode("?", $uri);
$uri = $request[0];
$request = explode("|",$handlerMap[$uri."|".$method]);

$response = call_user_func(array($request[0], $request[1]));
echo json_encode($response);
// var_dump($response);

//require_once $_SERVER['DocRoot'] . "/WebServices/{$request[0]}.php";

//eval("\$requestDispatcher = new {$request[0]}();");

//eval("\$requestDispatcher->{$request[1]}();");
