<?php
declare(strict_types=1);

namespace Branch\Routing;

use Branch\Interfaces\ContainerInterface;
use Branch\Interfaces\RouterInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Fig\Http\Message\RequestMethodInterface;

class Router implements RouterInterface, RequestMethodInterface
{
    protected string $path = '../routes/index.php';

    protected ContainerInterface $container;

    protected RequestInterface $request;

    protected ResponseInterface $response;

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
        $this->groupStack[] = static::mergeConifg($end, $config);

        $this->container->invoke($handler);

        array_pop($this->groupStack);
    }

    public function get(array $config, $handler): void
    {
        $end = $this->getGroupStackEnd();

        $config['handler'] = $handler;

        $this->routes[] = static::mergeConifg($end, $config);
    }

    protected function getGroupStackEnd()
    {
        $end = end($this->groupStack);

        return $end ? $end : [];
    }

    protected static function mergeConifg($end, $config): array
    {
        $config = array_merge($config, [
            'path' => static::mergePath($end, $config),
        ]);

        return array_merge_recursive(array_filter(
            $end, fn($key) => !in_array($key, ['path']), ARRAY_FILTER_USE_KEY
        ), $config);
    }

    protected static function mergePath($old, $new)
    {
        $old = $old['path'] ?? '';

        $path = isset($new['path']) ? trim($old, '/').'/'.trim($new['path'], '/') : $old;

        return trim($path, '/');
    }
}