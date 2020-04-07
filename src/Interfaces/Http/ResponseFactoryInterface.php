<?php
namespace Branch\Interfaces\Http;

use Psr\Http\Message\ResponseInterface;

interface ResponseFactoryInterface
{
    public function create(): ResponseInterface;
}