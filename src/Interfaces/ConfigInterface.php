<?php 
namespace Branch\Interfaces;

interface ConfigInterface
{
    public function getAll(): array;

    public function get(string $key);

    public function set(string $key, $value): void;
}