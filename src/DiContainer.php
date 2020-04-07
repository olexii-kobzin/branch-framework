<?php
declare(strict_types=1);

namespace Branch;

use Branch\Interfaces\ConfigInterface;
use Branch\Interfaces\ContainerInterface;
use Branch\Interfaces\RouterInterface;
use Branch\Routing\Router;
use Closure;
use LogicException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

class DiContainer implements ContainerInterface
{
    protected string $path = '../diconfig/diconfig.php';
    
    protected string $pathDefault = __DIR__ . '/diconfig/default.php';

    protected array $container = [];

    public function __construct()
    {
        $config = require realpath($this->path);
        $defaultConfig = require realpath($this->pathDefault);

        $this->container = array_merge($defaultConfig, $config);
    }

    public function register(string $id, $component): void
    {
        $this->buildComponent($id, $component);
    }

    public function has($id)
    {
        return isset($this->container[$id]);
    }

    public function get($id, array $default = [])
    {
        if (!$this->has($id)) {
            throw new LogicException("Component { $id } is not registered in the Application");
        }

        $component = $this->container[$id];

        if (is_object($component)) {
            return $component;
        }

        return $this->buildObject($component, $default);
    }

    public function invoke(callable $callable): void
    {
        $reflection = $this->prepareInvoke($callable);

        $arguments = $this->buildArguments($reflection->getParameters());

        $reflection->invokeArgs($arguments);
    }

    public function build(): void
    {
        $this->register(ContainerInterface::class, $this);

        foreach ($this->container as $key => $component) {
            $this->buildComponent($key, $component);
        }
    }

    protected function buildComponent(string $key, $component)
    {
        if (is_object($component)) {
            $this->container[$key] = $component;
        } else if (is_callable($component)) {
            $this->container[$key] = $component($this);
        } else if (is_array($component) && $component['type'] === static::DI_TYPE_SINGLETON) {
            $this->container[$key] = $this->buildObject($component);
        }
    }

    protected function buildObject(array $componentConfig, array $default = [])
    {
        $reflectionClass = new ReflectionClass($componentConfig['class']);
        // TODO: check for fallback to parent constructor
        $constructor = $reflectionClass->getConstructor();
        if (!$constructor) {
            return $reflectionClass->newInstance();
        }

        $arguments = $this->buildArguments(
            $constructor->getParameters(),
            isset($componentConfig['parameters']) ? array_merge($componentConfig['parameters'], $default) : $default
        );
        
        return $reflectionClass->newInstanceArgs($arguments);
    }

    protected function buildArguments(array $parameters, array $default = [])
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
                $arguments[] = $this->get($type->getName());
            } else if (!$type && !$parameter->isDefaultValueAvailable()) {
                throw new LogicException("No type available for { $name }");
            }
        }

        return $arguments;
    }

    protected function prepareInvoke(callable $callable)
    {
        $reflection = null;

        if ($callable instanceof Closure) {
            $reflection = new ReflectionFunction($callable);
        } else if (is_object($callable)) {
            $reflection = new ReflectionMethod($callable, '__invoke');
        } else if (is_array($callable)) {
            $reflection = new ReflectionMethod($callable[0], $callable[1]);
        }

        if (!$reflection) {
            throw new LogicException('Unknown callable reflection');
        }

        return $reflection;
    }
}