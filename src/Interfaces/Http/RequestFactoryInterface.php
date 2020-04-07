<?php
namespace Branch\Interfaces\Http;

use Psr\Http\Message\ServerRequestInterface;

interface RequestFactoryInterface
{
    public function create(): ServerRequestInterface;
}