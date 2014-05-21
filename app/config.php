<?php

use Aura\Router\Router;
use Aura\Router\RouterFactory;
use Interop\Container\ContainerInterface;

return [

    'routes' => require __DIR__ . '/../app/routes.php',

    Router::class => DI\factory(function (ContainerInterface $c) {
        $factory = new RouterFactory();
        $router = $factory->newInstance();

        // Add the routes from the array config (Aura router doesn't seem to accept routes as array)
        $routes = $c->get('routes');
        foreach ($routes as $routeName => $route) {
            $router->add($routeName, $route['pattern'])
                ->addValues(['controller' => $route['controller']]);
        }

        return $router;
    }),

];
