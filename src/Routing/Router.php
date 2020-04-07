<?php
declare(strict_types=1);

namespace Branch\Routing;

use Branch\Interfaces\Container\ContainerInterface;
use Branch\Interfaces\Routing\RouterInterface;
use Fig\Http\Message\RequestMethodInterface;

class Router implements RouterInterface, RequestMethodInterface
{
    protected string $path = '../routes/index.php';

    protected ContainerInterface $container;

    protected $config;

    protected array $groupStack = [];

    protected array $routes = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->config = require realpath($this->path);
    }

    public function init(): void
    {
        $this->container->invoke($this->config);

        var_dump($this->routes);
    }

    public function group(array $config, $handler): void
    {
        $end = $this->getGroupStackEnd();
        $this->groupStack[] = RouteCollector::mergeConifg($end, $config);

        $this->container->invoke($handler);

        array_pop($this->groupStack);
    }

    public function get(array $config, $handler): void
    {
        $end = $this->getGroupStackEnd();

        $config['handler'] = $handler;

        $this->routes[] = RouteCollector::mergeConifg($end, $config);
    }

    protected function getGroupStackEnd()
    {
        $end = end($this->groupStack);

        return $end ? $end : [];
    }
}