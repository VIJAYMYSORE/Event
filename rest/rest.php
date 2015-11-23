<?php
require_once( '../conf/config.php');
/*
$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$host = $_SERVER['SERVER_ADDR'];
$entityBody = file_get_contents('php://input');
global $g_body;
$g_body = json_decode($entityBody);

$handlerMap = array(
    "/api/health|GET"=>"api_health|find",
    "/api/user|POST"=>"api_user|create",
    "/api/user|GET"=>"api_user|find"


);
// temp hack to fix the query parameter problem
$request = explode("?", $uri);
$uri = $request[0];
$request = explode("|",$handlerMap[$uri."|".$method]);

$response = call_user_func(array($request[0], $request[1]));
echo json_encode($response);
*/

$app = new \Slim\Slim();
$app->get('/api/health', function () {
    $result = api_health::find();
    echo json_encode($result);
});
$app->get('/api/user/:userId', function ($userId) {
    $app = new \Slim\Slim();
    $request = $app->request->params();
    $request['userId'] = $userId;
    $result = api_user::find($request);
    echo json_encode($result);
});

$app->post('/api/user', function () {
    $app = new \Slim\Slim();
    $body = json_decode($app->request->getBody(), true);
    $result = api_user::create($body);
    echo json_encode($result);
});
$app->run();