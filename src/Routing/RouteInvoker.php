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
use Closure;
use Exception;
use InvalidArgumentException;

class RouteInvoker implements RouteInvokerInterface
{
    protected ContainerInterface $container;

    protected ServerRequestInterface $request;

    protected ResponseInterface $response;

    protected MiddlewarePipeInterface $pipe;

    protected string $defaultConfigPath = __DIR__ . '/../config/middleware.php';

    protected string $configPath = '../config/middleware.php';

    protected array $defaultMiddlewareConfig = [];

    protected array $middleware = [];

    protected string $path;

    public function __construct(
        ContainerInterface $container,
        ServerRequestInterface $request,
        ResponseInterface $response, 
        MiddlewarePipeInterface $pipe
    )
    {
        $this->container = $container;
        $this->request = $request;
        $this->response = $response;
        $this->pipe = $pipe;

        $config = require realpath($this->configPath);
        $defaultConfig = require realpath($this->defaultConfigPath);
        $this->defaultMiddlewareConfig = array_merge($defaultConfig, $config);
    }

    public function invoke(array $config, array $args): ResponseInterface
    {
        $this->path = $config['path'];

        $this->buildMiddleware(array_merge(
            $this->defaultMiddlewareConfig,
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
                $this->middleware[] = $this->container->buildObject($config);
            } else if (is_string($key)) {
                $this->middleware[] = $this->container->buildObject($key, $config['parameters']);
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
        $callbackAction = $this->container->get(CallbackActionInterface::class);
        $callbackAction->setHandler($handler);

        return $callbackAction;
    }

    protected function buildAction(string $action)
    {
        $action = $this->container->buildObject($action);

        return $action;
    }
}