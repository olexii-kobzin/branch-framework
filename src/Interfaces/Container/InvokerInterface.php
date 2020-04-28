<?php
declare(strict_types=1);

namespace Branch\Interfaces\Container;

interface InvokerInterface
{
    public function invoke(callable $callable, array $args = []);
}