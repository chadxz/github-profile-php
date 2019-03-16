<?php /** @noinspection PhpUnusedParameterInspection */

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class AuthController {
    /**
     * @var \Slim\Container
     */
    private $container;

    /**
     * @param \Slim\Container $container
     */
    public function __construct($container) {
        $this->container = $container;
    }

    /**
     * GET /login
     *
     * Initiate Github OAuth flow
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     * @throws \Exception
     */
    public function login(Request $req, Response $res): Response {
        /** @var \App\Services\Config $config */
        $config = $this->container['config'];
        /** @var \SlimSession\Helper $session */
        $session = $this->container['session'];

        $base_url = $config::get('BASE_URL');
        $login_url = $config::get('GITHUB_AUTH_URL');
        $client_id = $config::get('GITHUB_CLIENT_ID');

        $state = bin2hex(random_bytes(20));
        $session->set('login_state', $state);

        $query = http_build_query([
            "client_id" => $client_id,
            "redirect_uri" => "{$base_url}/github/auth-callback",
            "state" => $state
        ]);

        return $res->withRedirect("{$login_url}?$query");
    }

    /**
     * GET /logout
     *
     * Destroy the user's session and redirect back to index
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function logout(Request $req, Response $res): Response {
        /** @var \Slim\Router $router */
        $router = $this->container['router'];
        /** @var \SlimSession\Helper $session */
        $session = $this->container['session'];
        /** @var \Slim\Flash\Messages $flash */
        $flash = $this->container['flash'];

        $session::destroy();
        $flash->addMessage('login', 'Logged out.');
        return $res->withRedirect($router->pathFor('index'));
    }

    /**
     * GET /github/auth-callback
     *
     * Complete the Github OAuth flow and log the user in
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     * @throws \Exception
     */
    public function authCallback(Request $req, Response $res): Response {
        /** @var \App\Services\Config $config */
        $config = $this->container['config'];
        /** @var \GuzzleHttp\Client $http */
        $http = $this->container['http'];
        /** @var \Slim\Router $router */
        $router = $this->container['router'];
        /** @var \SlimSession\Helper $session */
        $session = $this->container['session'];
        /** @var \Slim\Flash\Messages $flash */
        $flash = $this->container['flash'];

        $base_url = $config::get('BASE_URL');
        $token_url = $config::get('GITHUB_ACCESS_TOKEN_URL');
        $client_id = $config::get('GITHUB_CLIENT_ID');
        $client_secret = $config::get('GITHUB_CLIENT_SECRET');

        $state = $req->getQueryParam('state');
        $login_state = $session->get('login_state');

        if ($state !== $login_state) {
            $flash->addMessage('login', 'Login request invalid. Try again.');
            return $res->withRedirect($router->pathFor('index'));
        }

        /** @var ResponseInterface $response */
        $response = $http->post($token_url, [
            'json' => [
                "client_id" => $client_id,
                "client_secret" => $client_secret,
                "code" => $req->getQueryParam('code'),
                "redirect_uri" => "{$base_url}/github/auth-callback",
                "state" => $state
            ]
        ]);

        $values = json_decode($response->getBody(), true);

        $session->set('token', $values['access_token']);
        $flash->addMessage('login', 'Logged in!');
        return $res->withRedirect($router->pathFor('index'));
    }
}
