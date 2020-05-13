<?php
namespace Branch\Interfaces\Routing;

interface RouterInterface
{
    public function init(): bool;

    public function group(array $config, \Closure $handler): void;

    public function get(array $config, $handler): void;

    public function post(array $config, $handler): void;

    public function put(array $config, $handler): void;

    public function patch(array $config, $handler): void;

    public function delete(array $config, $handler): void;

    public function options(array $config, $handler): void;

    public function any(array $config, $handler): void;

    public function map(array $methods, array $config, $handler): void;

    public function getPathByName(string $name, array $params = []): ?string;
}