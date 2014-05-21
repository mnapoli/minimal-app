<?php

namespace TheWebApp\Service;

/**
 * This is a service that says hello.
 */
class HelloWorldService
{
    public function greet($name)
    {
        return 'Hello ' . $name . '!';
    }
}
