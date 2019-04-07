<?php /** @noinspection PhpUnusedParameterInspection */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\GithubLoginUrlParams;
use App\Services\GithubServiceException;
use App\Services\GithubTokenRequestParams;
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
        $settings = $this->container['settings'];
        /** @var \SlimSession\Helper $session */
        $session = $this->container['session'];
        /** @var \App\Services\GithubService $github */
        $github = $this->container['github'];
        /** @var \Slim\Router $router */
        $router = $this->container['router'];

        $client_id = $settings['GITHUB_CLIENT_ID'];

        $state = bin2hex(random_bytes(20));
        $session->set('login_state', $state);

        return $res->withRedirect(
            $github::getLoginUrl(
                new GithubLoginUrlParams(
                    $client_id,
                    $router->pathFor('auth-callback'),
                    $state
                )
            )
        );
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
        $settings = $this->container['settings'];
        /** @var \Slim\Router $router */
        $router = $this->container['router'];
        /** @var \SlimSession\Helper $session */
        $session = $this->container['session'];
        /** @var \Slim\Flash\Messages $flash */
        $flash = $this->container['flash'];
        /** @var \Psr\Log\LoggerInterface $log */
        $log = $this->container['log'];
        /** @var \App\Services\GithubService $github */
        $github = $this->container['github'];

        $client_id = $settings['GITHUB_CLIENT_ID'];
        $client_secret = $settings['GITHUB_CLIENT_SECRET'];

        if (!$this->isLoginStateValid($req)) {
            $flash->addMessage('login', 'Login request invalid. Try again.');
            return $res->withRedirect($router->pathFor('index'));
        }

        try {
            $token = $github->getLoginToken(
                new GithubTokenRequestParams(
                    $client_id,
                    $client_secret,
                    $req->getQueryParam('code'),
                    $router->pathFor('auth-callback'),
                    $req->getQueryParam('state')
                )
            );

            $session->set('token', $token);
            $flash->addMessage('login', 'Logged in!');
            return $res->withRedirect($router->pathFor('index'));
        } catch (GithubServiceException $e) {
            $log->error('Error retrieving Github Login token', ['error' => $e]);
            $flash->addMessage('login', 'Something went wrong. Sorry.');
            return $res->withRedirect($router->pathFor('index'));
        }
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

        $session::destroy();
        return $res->withRedirect($router->pathFor('index'));
    }

    /**
     * Pull the 'state' query param off the request and check it against the
     * 'login_state' session variable to ensure they match.
     *
     * @param Request $req
     * @return bool
     */
    private function isLoginStateValid(Request $req): bool {
        /** @var \SlimSession\Helper $session */
        $session = $this->container['session'];

        $state = $req->getQueryParam('state');
        $login_state = $session->get('login_state');

        return $state === $login_state;
    }
}
