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
        $this->builder = new Builder($this);

        $config = require realpath($this->configPath);

        $defaultConfig = require realpath($this->configPathDefault);

        $this->config = array_merge($defaultConfig, $config);

        $this->register(ContainerInterface::class, $this);
    }

    public function register(string $id, $config): void
    {
        if ($this->configHas($id)) {
            throw new LogicException("Item \"$id\" is already registered");
        }
        $this->config[$id] = $config;
    }

    public function configHas(string $id): bool
    {
        return isset($this->config[$id]);
    }

    public function has($id)
    {
        return isset($this->definitions[$id]);
    }

    public function get($id)
    {
        if ($this->has($id)) {
            return $this->definitions[$id];
        }

        if (!$this->configHas($id)) {
            throw new OutOfBoundsException("Item \"$id\" is not registered");
        }

        $config = $this->config[$id];

        $built = $this->builder->build($config);

        if (!$this->isTransient($config)) {
            $this->definitions[$id] = $built;
        }

        return $built;
    }

    public function buildObject(string $class, array $parameters = []): object
    {
        return $this->builder->buildObject([
            'class' => $class,
            'parameters' => $parameters,
        ]);
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
                $callable[0] = $this->builder->build($callable[0]);
            }
            $reflection = new ReflectionMethod($callable[0], $callable[1]);
        }

        if (!$reflection) {
            throw new LogicException('Unknown callable reflection');
        }

        return $reflection;
    }

    protected function isTransient($config)
    {
        return is_array($config) 
            && $config['type'] === self::DI_TYPE_TRANSIENT;
    }
}