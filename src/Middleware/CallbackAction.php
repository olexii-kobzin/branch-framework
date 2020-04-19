<?php
declare(strict_types=1);

namespace Branch\Middleware;

use Branch\Interfaces\Middleware\CallbackActionInterface;
use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class CallbackAction extends Action implements CallbackActionInterface
{
    protected Closure $handler;

    public function setHandler(Closure $handler): void
    {
        $this->handler = $handler;
    }

    public function run(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return call_user_func($this->handler, $request, $response, $args);
    }
}