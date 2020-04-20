<?php
namespace Branch\Interfaces\Container;

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    public const DI_TYPE_TRANSIENT = 'transient';
    public const DI_TYPE_SINGLETON = 'singleton';

    public function register(string $name, $component): void;

    public function configHas(string $id): bool;

    public function invoke(callable $callable): void ;

    public function buildObject(string $class, array $parameters = []): object;
}