<?php /** @noinspection PhpUnusedParameterInspection */

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class AppController {
    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container) {
        $this->container = $container;
    }

    /**
     * GET /
     *
     * Main application index
     *
     * @param Request $req
     * @param Response $res
     * @return ResponseInterface
     * @throws \App\Services\GithubServiceException
     */
    public function index(Request $req, Response $res): ResponseInterface {
        /** @var \SlimSession\Helper $session */
        $session = $this->container['session'];

        $token = $session->get('token');

        return $token !== null
            ? $this->renderProfilePage($res)
            : $this->renderLoginPage($res);
    }

    /**
     * @param Response $res
     * @return ResponseInterface
     */
    private function renderLoginPage(Response $res): ResponseInterface {
        /** @var \Slim\Views\Twig $view */
        $view = $this->container['view'];

        return $view->render($res, 'login.twig');
    }

    /**
     * @param Response $res
     * @return ResponseInterface
     * @throws \App\Services\GithubServiceException
     */
    private function renderProfilePage(Response $res): ResponseInterface {
        /** @var \SlimSession\Helper $session */
        $session = $this->container['session'];
        /** @var \Slim\Views\Twig $view */
        $view = $this->container['view'];
        /** @var \App\Services\GithubService $github */
        $github = $this->container['github'];

        $token = $session->get('token');
        $profile = $github->getProfile($token);

        return $view->render($res, 'profile.twig', ['profile' => $profile]);
    }
}
