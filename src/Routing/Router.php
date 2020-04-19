<?php
declare(strict_types=1);

namespace Branch\Routing;

use Exception;
use Branch\Interfaces\Container\ContainerInterface;
use Branch\Interfaces\Routing\RouteInvokerInterface;
use Branch\Interfaces\Routing\RouterInterface;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Router implements RouterInterface, RequestMethodInterface
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
        $end = $this->getGroupStackEnd();

        $config['handler'] = $handler;

        $this->routes[] = RouteCollectorHelper::getRouteConfig($end, $config);
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