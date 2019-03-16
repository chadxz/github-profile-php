<?php

use App\Controllers\AppController;
use App\Controllers\AuthController;
use App\Services\Config;
use Slim\App;
use Slim\Middleware\Session;

require '../vendor/autoload.php';

$app = new App([
    'Settings' => [
        'displayErrorDetails' => true,
        'addContentLengthHeader' => false
    ]
]);

$app->add(
    new Session([
        'name' => 'github-profile-php',
        'autorefresh' => true,
        'lifetime' => '1 hour'
    ])
);

$container = $app->getContainer();

$container['session'] = function () {
    return new \SlimSession\Helper();
};

$container['config'] = function () {
    $root_dir = dirname(dirname(__FILE__));
    $config = new Config();
    $config::load($root_dir);

    return $config;
};

$container['http'] = function () {
    $client = new GuzzleHttp\Client([
        'headers' => [
            'Accept' => 'application/json'
        ]
    ]);
    return $client;
};

$app->get('/', AppController::class . ':index')->setName('index');
$app->get('/login', AuthController::class . ':login')->setName('login');
$app->get('/logout', AuthController::class . ':logout')->setName('logout');
$app->get('/github/auth-callback', AuthController::class . ':authCallback');
$app->run();
