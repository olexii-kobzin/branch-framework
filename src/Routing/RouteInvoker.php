<?php
declare(strict_types=1);
namespace Branch\Routing;

use Branch\Interfaces\Container\ContainerInterface;
use Branch\Interfaces\Middleware\ActionInterface;
use Branch\Interfaces\Middleware\CallbackActionInterface;
use Branch\Interfaces\Middleware\MiddlewarePipeInterface;
use Branch\Interfaces\Routing\RouteInvokerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouteInvoker implements RouteInvokerInterface
{
    protected ContainerInterface $container;

    protected CallbackActionInterface $callbackAction;

    protected ServerRequestInterface $request;

    protected MiddlewarePipeInterface $pipe;

    protected string $path;

    protected array $defaultMiddleware = [];

    protected array $middleware = [];

    public function __construct(
        ContainerInterface $container,
        CallbackActionInterface $callbackAction,
        ServerRequestInterface $request,
        MiddlewarePipeInterface $pipe
    )
    {
        $this->container = $container;
        $this->callbackAction = $callbackAction;
        $this->request = $request;
        $this->pipe = $pipe;
        $this->defaultMiddleware = $this->container->get('_branch.routing.defaultMiddleware');
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
            if (is_integer($key)) {
                $this->middleware[$config] = $this->container->make($config);
            } else if (is_string($key)) {
                $this->middleware[$key] = $this->container->make($key, $config);
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

    protected function buildChain(): void
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
        $action = $this->container->make($action);

        return $action;
    }
}