<?php

use Branch\DiContainer as Container;
use Branch\Config;
use Branch\Http\RequestFactory;
use Branch\Http\ResponseFactory;
use Branch\Interfaces\ConfigInterface;
use Branch\Interfaces\MiddlewareHandlerInterface;
use Branch\Interfaces\MiddlewarePipeInterface;
use Branch\Interfaces\RequestFactoryInterface;
use Branch\Interfaces\ResponseFactoryInterface;
use Branch\Interfaces\RouterInterface;
use Branch\Middleware\MiddlewareHandler;
use Branch\Middleware\MiddlewarePipe;
use Branch\Routing\Router;

return [
    ConfigInterface::class => [
        'class' => Config::class,
        'type' => Container::DI_TYPE_SINGLETON,
        // 'parameters' => [],
    ],
    RouterInterface::class => [
        'class' => Router::class,
        'type' => Container::DI_TYPE_SINGLETON,
    ],
    MiddlewarePipeInterface::class => [
        'class' => MiddlewarePipe::class,
        'type' => Container::DI_TYPE_INSTANCE,
    ],
    MiddlewareHandlerInterface::class => [
        'class' => MiddlewareHandler::class,
        'type' => Container::DI_TYPE_INSTANCE,
    ],
    RequestFactoryInterface::class => [
        'class' => RequestFactory::class,
        'type' => Container::DI_TYPE_SINGLETON,
    ],
    ResponseFactoryInterface::class => [
        'class' => ResponseFactory::class,
        'type' => Container::DI_TYPE_SINGLETON,
    ], 
];