<?php
namespace Branch\Interfaces\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

interface MiddlewarePipeInterface
{
    public function pipe(MiddlewareInterface $middleware): void;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
}