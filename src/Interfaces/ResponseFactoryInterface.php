<?php
namespace Branch\Interfaces;

use Psr\Http\Message\ResponseInterface;

interface ResponseFactoryInterface
{
    public function create(): ResponseInterface;
}