<?php
declare(strict_types=1);

namespace Branch\Container;

use Branch\Interfaces\Container\ContainerInterface;
use LogicException;
use ReflectionClass;

class Builder
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function buildDefinition($definition)
    {
        $built = null;

        if (is_object($definition)) {
            $built = $definition;
        } else if (is_callable($definition)) {
            $built = $definition($this);
        } else if (is_array($definition) && $definition['type'] === ContainerInterface::DI_TYPE_SINGLETON) {
            $built = $this->buildObject($definition);
        }

        return $built;
    }

    public function buildObject(array $definition, array $default = []): object
    {
        $reflectionClass = new ReflectionClass($definition['class']);
        // TODO: check for fallback to parent constructor
        $constructor = $reflectionClass->getConstructor();
        if (!$constructor) {
            return $reflectionClass->newInstance();
        }

        $arguments = $this->buildArguments(
            $constructor->getParameters(),
            isset($definition['parameters']) ? array_merge($definition['parameters'], $default) : $default
        );
        
        return $reflectionClass->newInstanceArgs($arguments);
    }

    public function buildArguments(array $parameters, array $default = []): array
    {
        $arguments = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            if (isset($default[$name])) {
                $arguments[] = $default[$name];
                continue;
            }

            $type = $parameter->getType();

            if ($type) {
                $arguments[] = $this->container->get($type->getName());
            } else if (!$type && !$parameter->isDefaultValueAvailable()) {
                throw new LogicException("No type available for \"$name\" }");
            }
        }

        return $arguments;
    }
}