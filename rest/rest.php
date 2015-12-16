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

$app->get('/api/user', function () {
    $app = new \Slim\Slim();
    $request = $app->request->params();
    $userId = $app->request()->params('userId');
    if(empty($userId)) {
        $response = "userId is required field";
        echo json_encode($response);
        return;
    }
    $request['userId'] = $userId;
    $result = api_user::find($request);
    echo json_encode($result);
});


$app->post('/api/user', function () {
    $app = new \Slim\Slim();
    $body = json_decode($app->request->getBody(), true);
    file_put_contents(TMP_DIR . "/request.log", "request --> " . serialize($_REQUEST), FILE_APPEND);
    file_put_contents(TMP_DIR . "/request.log", "SERVER --> " . serialize($_SERVER), FILE_APPEND);
    file_put_contents(TMP_DIR . "/request.log", "BODY SLIM --> " . serialize($app->request->getBody()), FILE_APPEND);
    $result = api_user::create($body);
    echo json_encode($result);
});

$app->run();