<?php
declare(strict_types=1);

namespace Branch\Routing;

use Branch\Interfaces\ConfigInterface;
use Exception;
use Branch\Interfaces\Container\ContainerInterface;
use Branch\Interfaces\Routing\RouteInvokerInterface;
use Branch\Interfaces\Routing\RouterInterface;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;

class Router implements RouterInterface, RequestMethodInterface, StatusCodeInterface
{
    protected string $configPath = '../routes/index.php';

    protected ContainerInterface $container;

    protected RouteInvokerInterface $invoker;

    protected ServerRequestInterface $request;

    protected ResponseInterface $response;

    protected EmitterInterface $emitter;

    protected ConfigInterface $config;

    protected string $path = '';

    protected $routerConfig;

    protected array $groupStack = [];

    protected array $routes = [];

    protected array $args = [];

    public function __construct(
        ContainerInterface $container,
        RouteInvokerInterface $invoker,
        ServerRequestInterface $request,
        ResponseInterface $response,
        EmitterInterface $emitter,
        ConfigInterface $config
    )
    {
        $this->container = $container;
        $this->invoker = $invoker;
        $this->request = $request;
        $this->response = $response;
        $this->emitter = $emitter;
        $this->config = $config;
        $this->path = $this->request->getUri()->getPath();
        $this->routerConfig = require realpath($this->configPath);
    }  

    public function init(): void
    {
        $this->container->invoke($this->routerConfig);

        $matchedRoute = $this->matchRoute();

        $this->updateActionConfigInfo($matchedRoute);

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

        $config = array_merge($config, [
            'methods' => $methods,
            'handler' => $handler,
        ]);

        $this->routes[] = RouteCollectorHelper::getRouteConfig($end, $config);
    }

    protected function updateActionConfigInfo($matchedRoute)
    {
        $this->config->set('sys.action', array_filter(
            $matchedRoute, 
            fn($v, $k) => !in_array($k, ['handler']),
            ARRAY_FILTER_USE_BOTH
        ));
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