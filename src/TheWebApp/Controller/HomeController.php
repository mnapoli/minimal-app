<?php

namespace TheWebApp\Controller;

use TheWebApp\Service\HelloWorldService;

class HomeController
{
    /**
     * @var HelloWorldService
     */
    private $helloWorldService;

    public function __construct(HelloWorldService $helloWorldService)
    {
        $this->helloWorldService = $helloWorldService;
    }

    public function homepage($name = 'world')
    {
        echo $this->helloWorldService->greet($name);
    }
}
