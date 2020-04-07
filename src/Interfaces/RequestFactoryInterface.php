<?php
namespace Branch\Interfaces;

use Psr\Http\Message\ServerRequestInterface;

interface RequestFactoryInterface
{
    public function create(): ServerRequestInterface;
}