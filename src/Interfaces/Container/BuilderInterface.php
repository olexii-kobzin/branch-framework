<?php
namespace Branch\Interfaces\Container;

interface BuilderInterface
{
    public function buildDefinition($definition);

    public function buildObject(array $definition, array $default = []): object;

    public function buildArguments(array $parameters, array $default = []): array;
}