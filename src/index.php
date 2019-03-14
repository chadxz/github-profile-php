<?php

use App\Services\Config;
use Slim\App;
use Slim\Http\Response;
use Slim\Http\Request;
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
    $client = new GuzzleHttp\Client();
    return $client;
};

/**
 * GET /
 *
 * Main application index
 */
$app
    ->get('/', function (Request $req, Response $res) {
        $token = $this->session->get('token');
        $name = null;

        if ($token) {
            /** @var Psr\Http\Message\ResponseInterface $response */
            $response = $this->http->get('https://api.github.com/user', [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    "Accept" => "application/json"
                ]
            ]);

            $values = json_decode($response->getBody(), true);
            $name = $values['login'];
        }

        $res->getBody()->write("Hello from slim. Logged in? {$name}");
        return $res;
    })
    ->setName('index');

/**
 * GET /login
 *
 * Initiate Github OAuth flow
 */
$app
    ->get('/login', function (Request $req, Response $res) {
        $base_url = $this->config->get('BASE_URL');
        $login_url = $this->config->get('GITHUB_AUTH_URL');

        $query = http_build_query([
            "client_id" => $this->config->get('GITHUB_CLIENT_ID'),
            "redirect_uri" => "{$base_url}/github/auth-callback",
            "state" => bin2hex(random_bytes(20))
        ]);

        return $res->withRedirect("{$login_url}?$query");
    })
    ->setName('login');

/**
 * GET /logout
 *
 * Destroy the user's session and redirect back to index
 */
$app
    ->get('/logout', function (Request $req, Response $res) {
        $this->session::destroy();
        return $res->withRedirect($this->router->pathFor('index'));
    })
    ->setName('logout');

/**
 * GET /github/auth-callback
 *
 * Complete the Github OAuth flow and log the user in
 */
$app->get('/github/auth-callback', function (Request $req, Response $res) {
    $base_url = $this->config->get('BASE_URL');
    $token_url = $this->config->get('GITHUB_ACCESS_TOKEN_URL');

    /** @var Psr\Http\Message\ResponseInterface $response */
    $response = $this->http->post($token_url, [
        'json' => [
            "client_id" => $this->config->get('GITHUB_CLIENT_ID'),
            "client_secret" => $this->config->get('GITHUB_CLIENT_SECRET'),
            "code" => $req->getQueryParam('code'),
            "redirect_uri" => "{$base_url}/github/auth-callback",
            "state" => $req->getQueryParam('state')
        ],
        'headers' => [
            'Accept' => 'application/json'
        ]
    ]);

    $values = json_decode($response->getBody(), true);

    $this->session->set('token', $values['access_token']);
    return $res->withRedirect($this->router->pathFor('index'));
});

$app->run();
