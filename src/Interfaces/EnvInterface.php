<?php
namespace Branch\Interfaces;

interface EnvInterface
{
    public const ENV_DEV = 'dev';
    
    public const ENV_PROD = 'prod';

    public function get(): array;
}