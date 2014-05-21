<?php

use TheWebApp\Controller\HomeController;

return [
    'home' => [
        'pattern' => '/',
        'controller' => [HomeController::class, 'homepage'],
    ],
    'hello' => [
        'pattern' => '/{name}',
        'controller' => [HomeController::class, 'homepage'],
    ],
];
