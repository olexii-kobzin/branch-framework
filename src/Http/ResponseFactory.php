<?php
declare(strict_types=1);

namespace Branch\Http;

use Branch\Interfaces\Http\ResponseFactoryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

class ResponseFactory implements ResponseFactoryInterface
{
    public function create(): ResponseInterface
    {
        $factory = new Psr17Factory();
        
        return $factory->createResponse();
    }
}