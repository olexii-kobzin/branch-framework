<?php
declare(strict_types=1);

namespace Branch\Container;

use Adbar\Dot;
use Branch\Interfaces\Container\ContainerInterface;
use Branch\Interfaces\Container\DefinitionInfoInterface;
use Branch\Interfaces\Container\InvokerInterface;
use Branch\Interfaces\Container\ResolverInterface;
use Branch\Container\DefinitionInfo;
use Branch\Container\Invoker;
use Branch\Container\Resolver;

class Container implements ContainerInterface
{
    protected Dot $definitions;

    protected Dot $entriesResolved;

    protected array $entriesBeingResolved = [];

    protected ResolverInterface $resolver;

    protected InvokerInterface $invoker;

    protected DefinitionInfoInterface $definitionInfo;

    public function __construct()
    {
        $this->definitions = new Dot();
        $this->entriesResolved = new Dot();

        $this->definitionInfo = new DefinitionInfo();
        $this->resolver = new Resolver($this, $this->definitionInfo);
        $this->invoker = new Invoker($this->resolver);
    }

    public function has($id)
    {
        return $this->definitions->has($id)
            || $this->entriesResolved->has($id);
    }

    public function get($id, bool $resolve = true)
    {
        if (!$this->has($id)) {
            throw new \OutOfRangeException("Definition '{$id}' was not found");
        }

        if (!$resolve) {
            return $this->definitions->get($id);
        }

        if ($this->isResolved($id)) {
            $resolved = $this->entriesResolved->get($id);
        } else {
            $definition = $this->definitions->get($id);
            $resolved = $this->isResolvableDefinition($definition) 
                ? $this->resolveDefinition($id, $definition)
                : $definition;

            if (!$this->definitionInfo->isTransient($definition)) {
                $this->entriesResolved->set($id, $resolved);
            }
        }

        return $resolved;
    }

    public function set(string $id, $definition, bool $replace = false): void
    {
        if (!$replace && $this->has($id)) {
            throw new \OutOfRangeException("Definition '{$id}' already present");
        }

        $this->definitions->set($id, $definition);
    }

    public function setMultiple(array $definitions, bool $replace = false): void
    {
        foreach ($definitions as $id => $definition) {
            $this->set($id, $definition, $replace);
        }
    }

    public function make(string $class, array $args = []): object
    {
        return $this->resolveDefinition($class, [
            'definition' => $class,
            'args' => $args,
        ]);
    }

    public function invoke(callable $callable, array $args = [])
    {
        return $this->invoker->invoke($callable, $args);
    }

    protected function isResolved(string $id): bool
    {
        return $this->entriesResolved->has($id);
    }

    protected function resolveDefinition(string $id, $definition)
    {
        if (isset($this->entriesBeingResolved[$id])) {
            throw new \LogicException("Circular dependency detected while trying to resolve '{$id}'");
        }

        $this->entriesBeingResolved[$id] = true;

        try {
            $value = $this->resolver->resolve($definition);
        } finally {
            unset($this->entriesBeingResolved[$id]);
        }

        return $value;
    }

    protected function isResolvableDefinition($definition)
    {
        return $this->definitionInfo->isClass($definition)
            || $this->definitionInfo->isClosure($definition)
            || $this->definitionInfo->isInstance($definition)
            || $this->definitionInfo->isResolvableArray($definition);
    }
}