<?php
namespace Branch\Interfaces\Container;

interface ResolverInterface
{
    public function resolve($definition);

    public function resolveArgs(array $argsConfig, array $predefined = []): array;
}