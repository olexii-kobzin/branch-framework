<?php
declare(strict_types=1);

namespace Branch\Container;

use Branch\Interfaces\Container\ContainerInterface;
use LogicException;
use OutOfBoundsException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use Closure;

class Container implements ContainerInterface
{
    protected string $configPath = '../config/di.php';
    
    protected string $configPathDefault = __DIR__ . '/../config/di.php';

    protected array $config = [];

    protected array $definitions = [];

    protected Builder $builder;

    public function __construct()
    {
        $config = require realpath($this->configPath);

        $defaultConfig = require realpath($this->configPathDefault);

        $this->config = array_merge($defaultConfig, $config);

        $this->builder = new Builder($this);
    }

    public function build(): void
    {
        $this->register(ContainerInterface::class, $this);

        foreach ($this->config as $id => $config) {
            $this->definitions[$id] = $this->builder->buildDefinition($config);
        }
    }

    public function register(string $id, $definition): void
    {
        if ($this->has($id)) {
            throw new LogicException("Definition \"$id\" is already registered");
        }
        $this->definitions[$id] = $this->builder->buildDefinition($definition);
    }

    public function has($id)
    {
        return isset($this->definitions[$id]);
    }

    public function get($id, array $default = [])
    {
        if (!$this->has($id)) {
            throw new OutOfBoundsException("Definition \"$id\" is not registered");
        }

        $definition = $this->definitions[$id];

        if (is_object($definition)) {
            return $definition;
        }

        return $this->builder->buildObject($definition, $default);
    }

    public function buildObject(string $class, array $default = []): object
    {
        return $this->builder->buildObject(['class' => $class], $default);
    }

    // TODO: move invoke to Invoker when sufficient amount of functionality
    public function invoke(callable $callable): void
    {
        $reflection = $this->prepareInvoke($callable);

        $arguments = $this->builder->buildArguments($reflection->getParameters());

        $reflection->invokeArgs($arguments);
    }

    public function prepareInvoke(callable $callable): ReflectionFunctionAbstract
    {
        $reflection = null;

        if ($callable instanceof Closure) {
            $reflection = new ReflectionFunction($callable);
        } else if (is_object($callable)) {
            $reflection = new ReflectionMethod($callable, '__invoke');
        } else if (is_array($callable)) {
            if (is_string($callable[0])) {
                $callable[0] = $this->buildObject($callable[0]);
            }
            $reflection = new ReflectionMethod($callable[0], $callable[1]);
        }

        if (!$reflection) {
            throw new LogicException('Unknown callable reflection');
        }

        return $reflection;
    }

}