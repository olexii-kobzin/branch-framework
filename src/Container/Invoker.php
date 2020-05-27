<?php
declare(strict_types=1);

namespace Branch\Container;

use Branch\Interfaces\Container\InvokerInterface;
use Branch\Interfaces\Container\ResolverInterface;

class Invoker implements InvokerInterface
{
    protected ResolverInterface $resolver;

    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    public function invoke(callable $callable, array $args = [])
    {
        [$object, $reflection] = $this->resolveCallable($callable);

        $arguments = $this->resolver->resolveArgs($reflection->getParameters(), $args);

        return $object 
            ? $reflection->invokeArgs($object, $arguments)
            : $reflection->invokeArgs($arguments);
    }

    protected function resolveCallable(callable $callable): array
    {
        $object = null;
        $reflection = null;

        if ($callable instanceof \Closure) {
            $reflection = new \ReflectionFunction($callable);
        } else if (is_object($callable)) {
            $object = $callable;
            $reflection = new \ReflectionMethod($object, '__invoke');
        } else if (is_array($callable)) {
            [$definition, $method] = $callable;
            $object = $this->resolver->resolve($definition);
            $reflection = new \ReflectionMethod($object, $method);
        } 

        if (!$reflection) {
            throw new \LogicException('Unknown callable reflection');
        }

        return [$object, $reflection];
    }
}