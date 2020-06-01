<?php
namespace Branch\Interfaces\Container;

interface InvokerInterface
{
    public function invoke(callable $callable, array $args = []);
}