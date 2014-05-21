<?php

use Aura\Router\Router;
use DI\ContainerBuilder;

require_once __DIR__ . '/../vendor/autoload.php';

// Container
$builder = new ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/../app/config.php');
$container = $builder->build();

// Routing
/** @var Router $router */
$router = $container->get(Router::class);
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestParameters = $router->match($url, $_SERVER)->params;
$controller = $requestParameters['controller'];

// Create the controller
$controllerObject = $container->get($controller[0]);

// Dispatch to the controller
$container->call([$controllerObject, $controller[1]], $requestParameters);
