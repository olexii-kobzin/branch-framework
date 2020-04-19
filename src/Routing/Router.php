<?php
declare(strict_types=1);

namespace Branch\Routing;

use Exception;
use Branch\Interfaces\Container\ContainerInterface;
use Branch\Interfaces\Routing\RouteInvokerInterface;
use Branch\Interfaces\Routing\RouterInterface;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Fig\Http\Message\StatusCodeInterfacel;

class Router implements RouterInterface, RequestMethodInterface, StatusCodeInterface
{
    protected string $configPath = '../routes/index.php';

    protected ContainerInterface $container;

    protected RouteInvokerInterface $invoker;

    protected ServerRequestInterface $request;

    protected ResponseInterface $response;

    protected EmitterInterface $emitter;

    protected string $path = '';

    protected $config;

    protected array $groupStack = [];

    protected array $routes = [];

    protected array $args = [];

    public function __construct(
        ContainerInterface $container,
        RouteInvokerInterface $invoker,
        ServerRequestInterface $request,
        ResponseInterface $response,
        EmitterInterface $emitter
    )
    {
        $this->container = $container;
        $this->invoker = $invoker;
        $this->request = $request;
        $this->response = $response;
        $this->emitter = $emitter;
        $this->path = $this->request->getUri()->getPath();
        $this->config = require realpath($this->configPath);
    }  

    public function init(): void
    {
        $this->container->invoke($this->config);

        $matchedRoute = $this->matchRoute();

        $this->validateMethod($matchedRoute['methods']);

        $response = $this->invoker->invoke($matchedRoute, $this->args);

        $this->emitter->emit($response);
    }

    public function group(array $config, $handler): void
    {
        $end = $this->getGroupStackEnd();

        $this->groupStack[] = RouteCollectorHelper::getGroupConfig($end, $config);

        $this->container->invoke($handler);

        array_pop($this->groupStack);
    }

    public function get(array $config, $handler): void
    {
        $this->map([self::METHOD_GET], $config, $handler);
    }

    public function post(array $config, $handler): void
    {
        $this->map([self::METHOD_POST], $config, $handler);
    }

    public function put(array $config, $handler): void
    {
        $this->map([self::METHOD_PUT], $config, $handler);
    }

    public function patch(array $config, $handler): void
    {
        $this->map([self::METHOD_PATCH], $config, $handler);
    }

    public function delete(array $config, $handler): void
    {
        $this->map([self::METHOD_DELETE], $config, $handler);
    }

    public function options(array $config, $handler): void
    {
        $this->map([self::METHOD_OPTIONS], $config, $handler);
    }

    public function any(array $config, $handler): void
    {
        $this->map([], $config, $handler);
    }

    public function map(array $methods, array $config, $handler): void
    {
        $end = $this->getGroupStackEnd();

        $config['handler'] = $handler;
        $config['methods'] = $methods;

        $this->routes[] = RouteCollectorHelper::getRouteConfig($end, $config);
    }

    protected function validateMethod(array $methods): void
    {
        $requestMethod = $this->request->getMethod();
        
        if ($methods && !in_array($requestMethod, $methods)) {
            // TODO: add http exception
            throw new Exception('Method not allowed', StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED);
        }
    }

    protected function getGroupStackEnd(): array
    {
        $end = end($this->groupStack);

        return $end ? $end : [];
    }

    protected function matchRoute(): array
    {
        $match = [];

        foreach ($this->routes as $route) {
            $matchedParams = [];
            if (preg_match($route['pattern'], trim($this->path, '/'), $matchedParams)) {
                $match = $route; 
                $this->args = $this->filterMatchedParams($matchedParams);
                break;
            }
        }

        if (!$match) {
            // TODO: Create Http exceptions
            throw new Exception("Route {$this->path} not found", 404);
        }

        return $match;
    }

    protected function filterMatchedParams($matchedParams)
    {
        return array_filter($matchedParams, 'is_string', ARRAY_FILTER_USE_KEY);
    }
    
}