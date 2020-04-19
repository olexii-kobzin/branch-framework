<?php
namespace Branch\Interfaces\Middleware;

use Closure;

interface CallbackActionInterface
{
    public function setHandler(Closure $handler): void;
}