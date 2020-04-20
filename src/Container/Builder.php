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

    public function build($config, array $default = [])
    {
        $built = null;

        if ($config instanceof Closure) {
            $built = call_user_func($config, $this->container);
        } else if (is_object($config)) {
            $built = $config;
        } else if (is_array($config)) {
            $built = $this->buildObject($config, $default);
        }
        
        return $built;
    }

    public function buildObject(array $config, array $default = []): object
    {
        $reflectionClass = new ReflectionClass($config['class']);
        // TODO: check for fallback to parent constructor
        $constructor = $reflectionClass->getConstructor();
        if (!$constructor) {
            return $reflectionClass->newInstance();
        }

        $arguments = $this->buildArguments(
            $constructor->getParameters(),
            isset($config['parameters']) ? array_merge($config['parameters'], $default) : $default
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

                if (!$this->container->configHas($typeName) && $parameter->isDefaultValueAvailable()) {
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