<?php
namespace Branch\Interfaces\Routing;

use Psr\Http\Message\ResponseInterface;

interface RouteInvokerInterface
{
    public function invoke(array $config, array $args): ResponseInterface;
}