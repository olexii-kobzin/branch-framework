<?php
declare(strict_types=1);

namespace Branch\Routing;

use Branch\App;
use Branch\Interfaces\Routing\RouteConfigBuilderInterface;
use Branch\Interfaces\Routing\RouteInvokerInterface;
use Branch\Interfaces\Routing\RouterInterface;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;

class Router implements RouterInterface, RequestMethodInterface, StatusCodeInterface
{
    protected App $app;

    protected RouteInvokerInterface $invoker;

    protected RouteConfigBuilderInterface $configBuilder;

    protected ServerRequestInterface $request;

    protected ResponseInterface $response;

    protected EmitterInterface $emitter;

    protected \Closure $routesConfig;

    protected string $path;

    protected array $groupStack = [];

    protected array $routes = [];

    public function __construct(
        App $app,
        RouteInvokerInterface $invoker,
        RouteConfigBuilderInterface $configBuilder,
        ServerRequestInterface $request,
        ResponseInterface $response,
        EmitterInterface $emitter
    )
    {
        $this->app = $app;
        $this->invoker = $invoker;
        $this->configBuilder = $configBuilder;
        $this->request = $request;
        $this->response = $response;
        $this->emitter = $emitter;
        $this->path = $this->request->getUri()->getPath();
        $this->routesConfig = $this->app->get('_branch.routing.routes');
    }  

    public function init(): bool
    {
        $this->app->invoke($this->routesConfig);

        [$route, $args] = $this->matchRoute();
        $this->updateActionConfigInfo($route);

        $response = $this->invoker->invoke($route, $args);

        return $this->emitter->emit($response);
    }

    public function group(array $config, \Closure $handler): void
    {
        $end = $this->getGroupStackEnd();

        $this->groupStack[] = $this->configBuilder->getGroupConfig($end, $config);

        $this->app->invoke($handler);

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

        $this->routes[] = $this->configBuilder->getRouteConfig($end, $config);
    }

    public function getPathByName(string $name, array $params = []): string
    {
        $route = $this->findRouteByName($name);

        if (!$route) {
            throw new \UnexpectedValueException("Route with name {$name} was not found");
        }

        $path = preg_replace_callback($route['pattern'], function (array $matches) use ($params) {
            $path = reset($matches);
            $paramMatches = array_filter($matches, fn($k): bool => is_string($k), ARRAY_FILTER_USE_KEY);
            $pathParams = array_filter($params, fn($k): bool => isset($paramMatches[$k]), ARRAY_FILTER_USE_KEY);
            $extraParams = array_diff_key($params, $pathParams);

            if (count($paramMatches) !== count($pathParams)) {
                throw new \UnexpectedValueException('Wrong route parameters provided');
            }

            $path = array_reduce(array_keys($paramMatches), function($carry, $key) use ($paramMatches, $pathParams) {
                return str_replace($paramMatches[$key], $pathParams[$key], $carry);
            }, $path);

            $query = !empty($extraParams) ? '?' . http_build_query($extraParams) : '';

            return $path . $query;
        }, $route['path']);

        return $path;
    }

    protected function findRouteByName(string $alias): ?array
    {
        foreach ($this->routes as $route) {
            if ($alias === ($route['name'] ?? null)) {
               return $route;
            }
        }

        return null;
    }

    protected function updateActionConfigInfo($matchedRoute)
    {
        $this->app->set('_branch.routing.action', array_filter(
            $matchedRoute, 
            fn($k): bool => !in_array($k, ['handler']),
            ARRAY_FILTER_USE_KEY
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
        $args = [];

        foreach ($this->routes as $route) {
            $matchedParams = [];
            if (preg_match($route['pattern'], $this->path, $matchedParams)) {
                $match = $route; 
                $args = $this->filterMatchedParams($matchedParams);
                break;
            }
        }

        if (!$match) {
            // TODO: Create Http exceptions
            throw new \Exception("Route {$this->path} not found", 404);
        }

        return [$match, $args];
    }

    protected function filterMatchedParams($matchedParams)
    {
        return array_filter($matchedParams, 'is_string', ARRAY_FILTER_USE_KEY);
    }
    
}