<?php
namespace Branch\Interfaces\Container;

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    public function setDefiniionInfo(DefinitionInfoInterface $definitionInfo): void;

    public function setResolver(ResolverInterface $resolver): void;

    public function setInvoker(InvokerInterface $invoker): void;

    public function set(string $id, $definition, bool $replace = true): void;

    public function setMultiple(array $definitions, bool $replace = true): void;

    public function make(string $class, array $options = []): object;

    public function invoke(callable $callable, array $args = []);
}