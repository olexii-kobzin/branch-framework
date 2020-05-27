<?php
namespace Branch\Interfaces\Container;

interface ResolverInterface
{
    public function resolve($definition);

    public function resolveObject(array $config): object;

    public function resolveArgs(array $argsConfig, array $predefined = []): array;
}