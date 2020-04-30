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
use Closure;
use Exception;
use InvalidArgumentException;

class RouteInvoker implements RouteInvokerInterface
{
    protected App $app;

    protected ServerRequestInterface $request;

    protected MiddlewarePipeInterface $pipe;

    protected array $defaultMiddleware = [];

    protected array $middleware = [];

    protected string $path;

    public function __construct(
        App $app,
        ServerRequestInterface $request,
        MiddlewarePipeInterface $pipe
    )
    {
        $this->app = $app;
        $this->request = $request;
        $this->pipe = $pipe;
        $this->defaultMiddleware = $this->app->get('_branch.routing.defaultMiddleware');
    }

    public function invoke(array $config, array $args): ResponseInterface
    {
        $this->path = $config['path'];

        $this->buildMiddleware(array_merge(
            $this->defaultMiddleware,
            $config['middleware'] ?? []
        ));

        $action = $this->buildHandler($config['handler']);
        $action->setArgs($args);

        $this->buildChain();

        return $this->pipe->process($this->request, $action);
    }

    protected function buildMiddleware(array $middleware): void
    {
        foreach ($middleware as $key => $config) {
            if (is_numeric($key)) {
                $this->middleware[] = $this->app->make($config);
            } else if (is_string($key)) {
                $this->middleware[] = $this->app->make($key, $config);
            } else {
                throw new Exception("Can't recognize middleware with key {$key} for path {$this->path}");
            }
        }
    }

    protected function buildHandler($handler): ActionInterface
    {
        $action = null;

        if ($handler instanceof Closure) {
            $action = $this->buildCallback($handler);
        } else if (is_string($handler)) {
            $action = $this->buildAction($handler);
        } else {
            throw new InvalidArgumentException('Handler type is not recognized');
        }

        return $action;
    }

    protected function buildChain(): void
    {
        foreach ($this->middleware as $middleware) {
            $this->pipe->pipe($middleware);
        }
    }

    protected function buildCallback(callable $handler)
    {
        $callbackAction = $this->app->get(CallbackActionInterface::class);
        $callbackAction->setHandler($handler);

        return $callbackAction;
    }

    protected function buildAction(string $action)
    {
        $action = $this->app->make($action);

        return $action;
    }
}