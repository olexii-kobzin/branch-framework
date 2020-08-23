<?php
declare(strict_types=1);
namespace Branch\Routing;

use Branch\App;
use Branch\Interfaces\Middleware\ActionInterface;
use Branch\Interfaces\Middleware\CallbackActionInterface;
use Branch\Interfaces\Middleware\MiddlewarePipeInterface;
use Branch\Interfaces\Routing\RouteInvokerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouteInvoker implements RouteInvokerInterface
{
    protected App $app;

    protected CallbackActionInterface $callbackAction;

    protected ServerRequestInterface $request;

    protected MiddlewarePipeInterface $pipe;

    protected string $path;

    protected array $middleware = [];

    public function __construct(
        App $app,
        CallbackActionInterface $callbackAction,
        ServerRequestInterface $request,
        MiddlewarePipeInterface $pipe
    )
    {
        $this->app = $app;
        $this->callbackAction = $callbackAction;
        $this->request = $request;
        $this->pipe = $pipe;
    }

    public function invoke(array $config, array $args = []): ResponseInterface
    {
        $this->path = $config['path'];

        $this->initMiddleware(($config['middleware'] ?? []));
        $action = $this->buildHandler($config['handler']);
        $action->setArgs($args);

        $this->buildPipe();

        return $this->pipe->process($this->request, $action);
    }

    protected function initMiddleware(array $middleware): void
    {
        $defaultMiddleware = call_user_func(
            $this->app->get('middleware', false),
            $this->app->get('env'),
            $this->app->get('settings')
        );

        $this->buildMiddleware(array_merge($defaultMiddleware, $middleware));
    }

    protected function buildMiddleware(array $middleware): void
    {
        foreach ($middleware as $key => $config) {
            if (is_integer($key)) {
                $this->middleware[$config] = $this->app->make($config);
            } else if (is_string($key)) {
                $this->middleware[$key] = $this->app->make($key, $config);
            }
        }
    }

    protected function buildHandler($handler): ActionInterface
    {
        $action = null;

        if ($handler instanceof \Closure) {
            $action = $this->buildCallback($handler);
        } else if (is_string($handler)) {
            $action = $this->buildAction($handler);
        } else {
            throw new \InvalidArgumentException('Handler type is not recognized');
        }

        return $action;
    }

    protected function buildPipe(): void
    {
        foreach ($this->middleware as $middleware) {
            $this->pipe->pipe($middleware);
        }
    }

    protected function buildCallback(\Closure $handler): CallbackActionInterface
    {
        $this->callbackAction->setHandler($handler);

        return $this->callbackAction;
    }

    protected function buildAction(string $action): ActionInterface
    {
        $action = $this->app->make($action);

        return $action;
    }
}