<?php
declare(strict_types=1);

namespace Branch\Container;

use Branch\Container\Resolver;
use LogicException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use Closure;

class Invoker
{
    protected Resolver $resolver;

    public function __construct(Resolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function invoke(callable $callable, array $args = [])
    {
        $reflection = $this->prepareInvoke($callable);

        $arguments = $this->resolver->resolveArgs($reflection->getParameters(), $args);

        $reflection->invokeArgs($arguments);
    }

    protected function prepareInvoke(callable $callable): ReflectionFunctionAbstract
    {
        $reflection = null;

        if ($callable instanceof Closure) {
            $reflection = new ReflectionFunction($callable);
        } else if (is_object($callable)) {
            $reflection = new ReflectionMethod($callable, '__invoke');
        } else if (is_array($callable)) {
            [$config, $method] = $callable;
            $object = $this->resolver->resolve($config);

            $reflection = new ReflectionMethod($object, $method);
        }

        if (!$reflection) {
            throw new LogicException('Unknown callable reflection');
        }

        return $reflection;
    }
}