# A simple application built with PHP-DI

This is an example of an application built with PHP-DI.

It is intended to be as simple as possible, so it's obviously not secure, super-clean or optimized.


## Dependencies

Of course we will use Composer to get our dependencies, so here is our `composer.json`:

```json
{
    "autoload": {
        "psr-4": {"TheWebApp\\": "src/TheWebApp/"}
    },
    "require": {
        "mnapoli/php-di": "dev-feature/CallFunction",
        "aura/router": "dev-develop-2"
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

As you can see, we will use Aura's router. You can also see both dependencies are unstable,
that's really just a detail here don't mind that please :)

Also, I have registered the `TheWebApp` namespace for autoloading: it will be used later.


## The front controller - index.php

**The front controller** is a very big name, but it's actually dead simple: it's
the entry point of the application, it's the `index.php`.

So we'll start to write our application by creating an `index.php` file and putting it in a `web` directory:

```
composer.json
web/
    index.php
```

In that file, the first thing we do is require Composer's autoloader so that we don't have to care
about autoloading:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';
```

Next, we are going to build the container. Why so early you might ask? It's because we are going to
use it just below!

```php
$builder = new \DI\ContainerBuilder();
$container = $builder->build();
```

The next thing we need is the router. And the container will provide it to us:

```php
$router = $container->get('Aura\Router\Router');
```

The configuration of the router, mainly for the routes, will be shown later.

Now that we have our router, we must use it to "match" the current URL and call the matching controller:

```php
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestParameters = $router->match($url, $_SERVER)->params;
$controller = $requestParameters['controller'];
$action = $requestParameters['action'];
```

`$controller` will be the class name : `controller_class_name`.

`$action` will be the method name of the class to be called : `controller_class_method_name`.

Here is a simple (and stupid) way to create the controller object:

```php
$controllerObject = new $controller();
```

And call the action method:

```php
$controllerObject->$action();
```

**Super important:** this way of creating and calling the controller is bad.
This is an example of a bad way to do it, just don't follow it.
The "right" way will be shown in just a few paragraphs!


## Application configuration

Let's configure the router. We will do it in a configuration file for PHP-DI's container.
Let's add a `app/config.php` file containing our routing configuration:

```php
<?php

use Aura\Router\Router;
use Aura\Router\RouterFactory;
use Interop\Container\ContainerInterface;

return [

    // Routes are defined in routes.php
    'routes' => require __DIR__ . '/routes.php',

    // The router configuration
    Router::class => DI\factory(function (ContainerInterface $c) {
        $factory = new RouterFactory();
        $router = $factory->newInstance();

        $routes = $c->get('routes');
        $router->setRoutes($routes);

        return $router;
    }),

];
```

And load it in PHP-DI (`index.php`):

```php
$builder = new \DI\ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/../app/config.php');
$container = $builder->build();
```

And as you can see the routes are defined in a separate file (it's a bit cleaner): `app/routes.php`.

The router is configured using a closure, i.e. a factory function. When PHP-DI needs to create the router,
it will call that closure and return its result. By using a PHP closure (instead of YAML files or whatever),
we have the ability to use any PHP code. This is pretty obvious here, we can add the routes (in a hacky way)
manually from the array.


## Routes

Let's add a route for the home page in `app/routes.php`:

```php
<?php

return [
    'home' => [
        'pattern' => '/',
        'controller' => 'TheWebApp\Controller\HomeController', 
        'action' => 'homepage',
    ],
];
```

The `home` route leads to the method `homepage` of the `HomeController` class (which we need to write).


## Controller

Let's write our controller class in `src/TheWebApp/Controller/`. As you have seen previously
in `composer.json`, Composer's autoloader should be able to autoload it fine.

```php
namespace TheWebApp\Controller;

class HomeController
{
    public function homepage()
    {
        echo 'Hello world';
    }
}
```


## Test

So here is the layout of our application now:

```
app/
    config.php
    routes.php
src/
    TheWebApp/
        Controller/
            HomeController.php
web/
    index.php
```

Pretty clear and simple.

Let's fire a webserver and try out our new application:

```
$ php -S localhost:8000 -t web
```

Now when we visit http://localhost:8000/ we see `Hello world!`.


## Dependency injection in controllers

Remember how we created and called our controllers:

```php
$controllerObject = new $controller();
$controllerObject->$method();
```

This is bad!

For example, let's imagine we have written a `HelloWorldService`:

```php
namespace TheWebApp\Service;

class HelloWorldService
{
    public function greet($name)
    {
        return 'Hello ' . $name . '!';
    }
}
```

And imagine we want to use this service inside the controller.

Of course, we will want to use dependency injection, so we want to inject the service into the controller.
Given the controller is created manually (using `new`) in `index.php`, we can't inject dependencies per controller.

To make this possible, we will use PHP-DI to **create** the controller:

```php
$controllerObject = $container->make($controller);
```

That way, PHP-DI will automatically inject the dependencies inside the controller!

Here is our controller now:

```php
class HomeController
{
    private $helloWorldService;

    public function __construct(HelloWorldService $helloWorldService)
    {
        $this->helloWorldService = $helloWorldService;
    }

    public function homepage()
    {
        echo $this->helloWorldService->greet('world');
    }
}
```

## Dependency injection in controller actions

Another new interesting feature of PHP-DI is its ability to *call a function* while automatically
resolving the function's parameters. And that will be exactly what we need for controller actions.

Let's add another (very similar) route:

```php
return [
    'home' => [
        'pattern' => '/',
        'controller' => 'TheWebApp\Controller\HomeController', 
        'action' => 'homepage',
    ],
    'greeting' => [
        'pattern' => '/{name}',
        'controller' => 'TheWebApp\Controller\HomeController', 
        'action' => 'homepage',
    ],
];
```

The new route can take a `name` parameter.

Let's change our `homepage()` action:

```php
    public function homepage($name = 'world')
    {
        echo $this->helloWorldService->greet($name);
    }
```

When the action is called without a `$name` parameter, the page will show "Hello world!".

However if I visit for example http://localhost:8000/matthieu the page will show "Hello matthieu!".

To make this possible, we need to call the action (`homepage()`) with the route parameters.
We can use `Container::call()` in PHP-DI to do this:

```php
$container->call([$controller, $method], $parameters);
```

FYI the `$parameters` are obtained from the router when matching a route:

```php
$route = $router->match($url, $_SERVER);
$parameters = $route->params;
```

The very interesting feature of `Container::call()` is that it can inject any dependency of the container,
for example:

```php
    public function homepage(LoggerInterface $logger, $name = 'world')
    {
        $logger->info('We are greeting ' . $name);

        echo $this->helloWorldService->greet($name);
    }
```


## Conclusion

It doesn't take much to build a minimalistic and very decoupled application.

Thanks to `Container::make()` and `Container::call()`, we can use dependency injection both:

- at the controller's creation
- when the controller action is called

In the second case, we can not only inject dependencies, but also parameters (e.g. request parameters).
