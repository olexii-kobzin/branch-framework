<?php
declare(strict_types=1);

namespace Branch\Http;

use Branch\Interfaces\RequestFactoryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;

class RequestFactory implements RequestFactoryInterface
{
    public function create(): ServerRequestInterface
    {
        $factory = new Psr17Factory();
        $creator = new ServerRequestCreator(
            $factory,
            $factory,
            $factory,
            $factory
        );
        
        return $creator->fromGlobals();
    }
}