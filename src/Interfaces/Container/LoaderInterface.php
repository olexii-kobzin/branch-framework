<?php
namespace Branch\Interfaces\Container;

interface LoaderInterface
{
    public function loadConfigs(): void;

    public function loadRoutes(): void;
}