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
use Branch\Interfaces\Routing\RouterInterface;
use Branch\Middleware\MiddlewareHandler;
use Branch\Middleware\MiddlewarePipe;
use Branch\Routing\Router;

return [
    ConfigInterface::class => [
        'class' => Config::class,
        'type' => ContainerInterface::DI_TYPE_SINGLETON,
        // 'parameters' => [],
    ],
    RouterInterface::class => [
        'class' => Router::class,
        'type' => ContainerInterface::DI_TYPE_SINGLETON,
    ],
    MiddlewarePipeInterface::class => [
        'class' => MiddlewarePipe::class,
        'type' => ContainerInterface::DI_TYPE_INSTANCE,
    ],
    MiddlewareHandlerInterface::class => [
        'class' => MiddlewareHandler::class,
        'type' => ContainerInterface::DI_TYPE_INSTANCE,
    ],
    RequestFactoryInterface::class => [
        'class' => RequestFactory::class,
        'type' => ContainerInterface::DI_TYPE_SINGLETON,
    ],
    ResponseFactoryInterface::class => [
        'class' => ResponseFactory::class,
        'type' => ContainerInterface::DI_TYPE_SINGLETON,
    ], 
];