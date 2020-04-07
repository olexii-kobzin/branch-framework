<?php
namespace Branch\Interfaces\Middleware;

use Psr\Http\Server\RequestHandlerInterface;

interface MiddlewareHandlerInterface extends RequestHandlerInterface
{
    public function setPipe(array $pipe): void;

    public function setFallbackHandler(RequestHandlerInterface $fallbackHandler): void;
}