<?php
require_once( '../conf/config.php');

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