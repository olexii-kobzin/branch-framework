<?php
namespace Branch\Interfaces;

interface RouterInterface
{
    public function init(): void;

    public function group(array $config, $handler): void;

    public function get(array $config, $handler): void;
}