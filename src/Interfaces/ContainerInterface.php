<?php
namespace Branch\Interfaces;

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    public const DI_TYPE_INSTANCE = 'instance';
    public const DI_TYPE_SINGLETON = 'singleton';

    public function register(string $name, $component): void;

    public function invoke(callable $callable): void ;

    public function build(): void;
}