<?php
declare(strict_types=1);

namespace Branch\Container;

use Branch\Interfaces\Container\ContainerInterface;
use Branch\Container\DefinitionHelper;
use Adbar\Dot;
use LogicException;
use OutOfBoundsException;
use Exception;

class Container implements ContainerInterface
{
    protected Dot $definitions;

    protected Dot $entriesResolved;

    protected array $entriesBeingResolved = [];

    protected Resolver $resolver;

    protected Invoker $invoker;

    public function __construct()
    {
        $this->definitions = new Dot();
        $this->entriesResolved = new Dot();

        // TODO: move to App::boostrap after definitions load
        $this->resolver = new Resolver($this);
        $this->invoker = new Invoker($this->resolver);
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

            if (!DefinitionHelper::isTransient($this->definitions->get($id))) {
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
            throw new LogicException("Definition '$id' already present");
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