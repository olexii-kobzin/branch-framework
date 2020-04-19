<?php
declare(strict_types=1);

namespace Branch\Middleware;

use Branch\Interfaces\Middleware\MiddlewareHandlerInterface;
use Branch\Interfaces\Middleware\MiddlewarePipeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewarePipe implements MiddlewarePipeInterface
{
    protected array $pipe = [];

    protected MiddlewareHandlerInterface $middlewareHandler;

    public function __construct(MiddlewareHandlerInterface $middlewareHandler)
    {
        $this->middlewareHandler = $middlewareHandler;
    }
    
    public function pipe(MiddlewareInterface $middleware): void
    {
        $this->pipe[] = $middleware;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->middlewareHandler->setPipe($this->pipe);
        $this->middlewareHandler->setFallbackHandler($handler);

        return $this->middlewareHandler->handle($request);
    }
}