<?php
declare(strict_types=1);

namespace Branch\Container;

use Branch\Interfaces\Container\ContainerInterface;
use Branch\Interfaces\Middleware\MiddlewarePipeInterface;
use Closure;
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

        if ($definition instanceof Closure) {
            $built = call_user_func($definition, $this->container);
        } else if (is_object($definition)) {
            $built = $definition;
        } else if (is_array($definition)) {
            if ($definition['type'] === ContainerInterface::DI_TYPE_SINGLETON) {
                $built = $this->buildObject($definition);
            } else {
                $built = $definition;
            }
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
                $typeName = $type->getName();

                if (!$this->container->has($typeName) && $parameter->isDefaultValueAvailable()) {
                    continue;
                }

                $arguments[] = $this->container->get($type->getName());
            } else {
                throw new LogicException("No type available for \"$name\" }");
            }
        }

        return $arguments;
    }
}