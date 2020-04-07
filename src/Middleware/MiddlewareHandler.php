<?php
declare(strict_types=1);

namespace Branch\Middleware;

use Branch\Interfaces\MiddlewareHandlerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class MiddlewareHandler implements MiddlewareHandlerInterface
{
    protected array $pipe;

    protected RequestHandlerInterface $fallbackHandler;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!count($this->pipe)) {
            return $this->fallbackHandler->handle($request);
        }

        $middleware = array_shift($this->pipe);
        $handler = clone $this;

        return $middleware->process($request, $handler);
    }

    public function setPipe(array $pipe): void
    {
        $this->pipe = $pipe;
    }

    public function setFallbackHandler(RequestHandlerInterface $fallbackHandler): void
    {
        $this->fallbackHandler = $fallbackHandler;
    }
}