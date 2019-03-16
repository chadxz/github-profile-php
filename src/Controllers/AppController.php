<?php /** @noinspection PhpUnusedParameterInspection */

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
     */
    public function index(Request $req, Response $res): ResponseInterface {
        /** @var \SlimSession\Helper $session */
        $session = $this->container['session'];
        /** @var \GuzzleHttp\Client $http */
        $http = $this->container['http'];
        /** @var \Slim\Views\Twig $view */
        $view = $this->container['view'];

        $token = $session->get('token');
        $name = null;

        if ($token !== null) {
            /** @var ResponseInterface $response */
            $response = $http->get('https://api.github.com/user', [
                'headers' => [
                    'Authorization' => "Bearer {$token}"
                ]
            ]);

            $values = json_decode($response->getBody(), true);
            $name = $values['login'];
        }

        return $view->render($res, 'index.twig', [ 'name' => $name ]);
    }
}
