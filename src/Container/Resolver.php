<?php
declare(strict_types=1);

namespace Branch\Container;

use Branch\App;
use Branch\Container\DefinitionHelper;
use Branch\Interfaces\Container\ResolverInterface;
use ReflectionClass;
use LogicException;

class Resolver implements ResolverInterface
{
    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function resolve($definition)
    {
        $resolved = null;

        if (DefinitionHelper::isClosureDefinition($definition)) {
            $resolved = call_user_func($definition, $this->app);
        } elseif (DefinitionHelper::isInstanceDefinition($definition)) {
            $resolved = $definition;
        } elseif (DefinitionHelper::isArrayObjectDefinition($definition)) {
            $resolved = $this->resolveObject($definition);
        } elseif (DefinitionHelper::isStringObjectDefinition($definition)) {
            $resolved = $this->resolveObject(['class' => $definition]);
        } else {
            $resolved = $definition;
        }
        
        return $resolved;
    }

    public function resolveObject(array $config): object
    {
        $reflectionClass = new ReflectionClass($config['class']);
        // TODO: check for fallback to parent constructor
        $constructor = $reflectionClass->getConstructor();
        if (!$constructor) {
            return $reflectionClass->newInstance();
        }

        $arguments = $this->resolveArgs(
            $constructor->getParameters(),
            $config['args'] ?? []
        );
        
        return $reflectionClass->newInstanceArgs($arguments);
    }

    public function resolveArgs(array $parameters, array $predefined = []): array
    {
        $arguments = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            if (isset($predefined[$name])) {
                $arguments[] = $predefined[$name];
                continue;
            }

            $type = $parameter->getType();

            if ($type) {
                $typeName = $type->getName();

                if (!$this->app->has($typeName) && $parameter->isDefaultValueAvailable()) {
                    continue;
                }
                
                $arguments[] = $this->app->get($type->getName());
            } else {
                throw new LogicException("No type available for \"$name\" }");
            }
        }

        return $arguments;
    }
}