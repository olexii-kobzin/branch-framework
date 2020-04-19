<?php
declare(strict_types=1);
namespace Branch\Routing;

use Branch\Interfaces\Container\ContainerInterface;
use Branch\Interfaces\Middleware\ActionInterface;
use Branch\Interfaces\Middleware\MiddlewarePipeInterface;
use Branch\Interfaces\Routing\RouteInvokerInterface;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouteInvoker implements RouteInvokerInterface
{
    protected ContainerInterface $container;

    protected ServerRequestInterface $request;

    protected ResponseInterface $response;

    protected MiddlewarePipeInterface $pipe;

    protected array $middleware;

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
    }

    public function invoke(array $config, array $args): ResponseInterface
    {
        $this->path = $config['path'];

        $this->buildMiddleware($config['middleware'] ?? []);

        $handler = $this->buildHandler($config['handler']);
        $handler->setArgs($args);

        $this->buildChain();

        return $this->pipe->process($this->request, $handler);
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

    protected function buildChain(): void
    {
        foreach ($this->middleware as $middleware) {
            $this->pipe->pipe($middleware);
        }
    }

    protected function buildHandler(callable $handler)
    {
        return new class($this->response, $handler) implements ActionInterface {
            
            private ResponseInterface $response;

            private $handler;

            private array $args = [];

            public function __construct(ResponseInterface $response, callable $handler)
            {
                $this->response = $response;
                $this->handler = $handler;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->run($request, $this->response, $this->args);
            }

            public function run(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
            {
                return call_user_func($this->handler, $request, $response, $args);
            }

            public function setArgs(array $args): void
            {
                $this->args = $args;
            }
        };
    }

    // protected function invokeAction(callable $handler, array $middleware)
    // {

    // }
}