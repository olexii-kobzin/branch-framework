<?php
declare(strict_types=1);
namespace Branch\Interfaces\Middleware;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ActionInterface extends RequestHandlerInterface
{
    public function run(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface;

    public function setArgs(array $args): void;
}