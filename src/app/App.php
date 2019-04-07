<?php

declare(strict_types=1);

namespace App;

use App\Controllers\AppController;
use App\Controllers\AuthController;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Middleware\Session;

class App {
    public static function build(array $settings): \Slim\App {
        $app = new \Slim\App([
            'settings' => array_merge(
                [
                    'displayErrorDetails' => true,
                    'addContentLengthHeader' => false
                ],
                $settings
            )
        ]);

        $app->add(
            new Session([
                'name' => 'github-profile-php',
                'autorefresh' => true,
                'lifetime' => '1 hour'
            ])
        );

        $container = $app->getContainer();

        $container['log'] = function () {
            $log = new \Monolog\Logger('app');
            $stdoutHandler = new \Monolog\Handler\StreamHandler('php://stdout');
            $log->pushHandler($stdoutHandler);
            return $log;
        };

        $container['session'] = function () {
            return new \SlimSession\Helper();
        };

        $container['http'] = function () {
            return new \GuzzleHttp\Client([
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);
        };

        $container['github'] = function (Container $container) {
            /** @var \GuzzleHttp\Client $http */
            $http = $container['http'];

            return new \App\Services\GithubService($http);
        };

        $container['flash'] = function () {
            return new \Slim\Flash\Messages();
        };

        $container['view'] = function (Container $container) {
            $template_dir = realpath(__DIR__ . '/Views') ?: '';
            $view = new \Slim\Views\Twig($template_dir, [
                'strict_variables' => true
            ]);

            /** @var \Slim\Router $router */
            $router = $container['router'];
            /** @var \Slim\Flash\Messages $flash */
            $flash = $container['flash'];

            $uri = Uri::createFromEnvironment(
                new \Slim\Http\Environment($_SERVER)
            );
            $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));
            $view->addExtension(new \Knlv\Slim\Views\TwigMessages($flash));
            return $view;
        };

        /**
         * Middleware to set the router's base path from the url
         * so that router->pathFor gives back a fully qualified url.
         * This is required when passing a redirectUrl to Github.
         */
        $app->add(function (Request $req, Response $res, callable $next) use (
            $app
        ) {
            /** @var \Slim\Router $router */
            $router = $app->getContainer()->get('router');

            $uri = Uri::createFromEnvironment(
                new \Slim\Http\Environment($_SERVER)
            );
            $router->setBasePath($uri->getBaseUrl());

            return $next($req, $res);
        });

        $app->get('/', AppController::class . ':index')->setName('index');
        $app->get('/login', AuthController::class . ':login')->setName('login');
        $app
            ->get('/logout', AuthController::class . ':logout')
            ->setName('logout');

        $app
            ->get(
                '/github/auth-callback',
                AuthController::class . ':authCallback'
            )
            ->setName('auth-callback');

        return $app;
    }
}
