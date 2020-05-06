<?php
declare(strict_types=1);

namespace Branch\Container;

use Adbar\Dot;
use Branch\Interfaces\Container\ContainerInterface;
use Branch\Interfaces\Container\DefinitionInfoInterface;
use Branch\Interfaces\Container\InvokerInterface;
use Branch\Interfaces\Container\ResolverInterface;
use OutOfBoundsException;
use Exception;

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

    public function setResolver(ResolverInterface $resolver): void
    {
        $this->resolver = $resolver;
    }

    public function setInvoker(InvokerInterface $invoker): void
    {
        $this->invoker = $invoker;
    }
    
    public function setDefiniionInfo(DefinitionInfoInterface $definitionInfo): void
    {
        $this->definitionInfo = $definitionInfo;
    }

    public function has($id)
    {
        return $this->definitions->has($id);
    }

    public function get($id)
    {
        if (!$this->has($id)) {
            throw new OutOfBoundsException("Definition '$id' was not found");
        }

        if ($this->isDefinitionIdResolvable($id) && !$this->hasResolved($id)) {
            $resolved = $this->resolveDefinition($id, $this->definitions->get($id));

            if (!$this->definitionInfo->isTransient($this->definitions->get($id))) {
                $this->entriesResolved->set($id, $resolved);
            }
        } else {
            $resolved = $this->isDefinitionIdResolvable($id)
                ? $this->entriesResolved->get($id)
                : $this->definitions->get($id);
        }

        return $resolved;
    }

    public function set(string $id, $definition, bool $replace = true): void
    {
        if (!$replace && $this->has($id)) {
            throw new OutOfBoundsException("Definition '$id' already present");
        }

        $this->definitions->set($id, $definition);
    }

    public function setMultiple(array $definitions, bool $replace = true): void
    {
        foreach ($definitions as $id => $definition) {
            $this->set($id, $definition, $replace);
        }
    }

    public function make(string $class, array $args = []): object
    {
        return $this->resolveDefinition($class, [
            'class' => $class,
            'args' => $args,
        ]);
    }

    public function invoke(callable $callable, array $args = [])
    {
        return $this->invoker->invoke($callable, $args);
    }

    protected function hasResolved(string $id): bool
    {
        return $this->entriesResolved->has($id);
    }

    protected function resolveDefinition(string $id, $definition)
    {
        if (isset($this->entriesBeingResolved[$id])) {
            // TODO: replace with specialized exception
            throw new Exception("Circular dependency detected while trying to resolve '$id'");
        }

        $this->entriesBeingResolved[$id] = true;

        try {
            $value = $this->resolver->resolve($definition);
        } finally {
            unset($this->entriesBeingResolved[$id]);
        }

        return $value;
    }

    protected function isDefinitionIdResolvable($id)
    {
        return strpos($id, '.') === false;
    }
}