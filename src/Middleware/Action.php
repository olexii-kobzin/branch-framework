<?php
declare(strict_types=1);

namespace Branch\Middleware;

use Branch\Interfaces\Middleware\ActionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class Action implements ActionInterface
{
    protected ResponseInterface $response;

    protected array $args = [];

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run($request, $this->response, $this->args);
    }

    public function setArgs(array $args): void
    {
        $this->args = $args;
    }
}