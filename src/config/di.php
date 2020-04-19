<?php

use Branch\Interfaces\Container\ContainerInterface;
use Branch\Config;
use Branch\Http\RequestFactory;
use Branch\Http\ResponseFactory;
use Branch\Interfaces\ConfigInterface;
use Branch\Interfaces\Middleware\MiddlewareHandlerInterface;
use Branch\Interfaces\Middleware\MiddlewarePipeInterface;
use Branch\Interfaces\Http\RequestFactoryInterface;
use Branch\Interfaces\Http\ResponseFactoryInterface;
use Branch\Interfaces\Routing\RouteInvokerInterface;
use Branch\Interfaces\Routing\RouterInterface;
use Branch\Middleware\MiddlewareHandler;
use Branch\Middleware\MiddlewarePipe;
use Branch\Routing\RouteInvoker;
use Branch\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

return [
    ConfigInterface::class => [
        'class' => Config::class,
        'type' => ContainerInterface::DI_TYPE_SINGLETON,
        // 'parameters' => [],
    ],
    RequestFactoryInterface::class => [
        'class' => RequestFactory::class,
        'type' => ContainerInterface::DI_TYPE_SINGLETON,
    ],
    ResponseFactoryInterface::class => [
        'class' => ResponseFactory::class,
        'type' => ContainerInterface::DI_TYPE_SINGLETON,
    ],
    ServerRequestInterface::class => function (ContainerInterface $container) {
        $factory = $container->get(RequestFactoryInterface::class);

        return $factory->create();
    },
    ResponseInterface::class => function (ContainerInterface $container) {
        $factory = $container->get(ResponseFactoryInterface::class);

        return $factory->create();
    },
    MiddlewarePipeInterface::class => [
        'class' => MiddlewarePipe::class,
        'type' => ContainerInterface::DI_TYPE_INSTANCE,
    ],
    MiddlewareHandlerInterface::class => [
        'class' => MiddlewareHandler::class,
        'type' => ContainerInterface::DI_TYPE_INSTANCE,
    ],
    RouteInvokerInterface::class => [
        'class' => RouteInvoker::class,
        'type' => ContainerInterface::DI_TYPE_SINGLETON,
    ],
    RouterInterface::class => [
        'class' => Router::class,
        'type' => ContainerInterface::DI_TYPE_SINGLETON,
    ],
];