<?php
declare(strict_types=1);

use Branch\Http\ResponseFactory;
use Branch\Tests\BaseTestCase;
use Psr\Http\Message\ResponseInterface;

class ResponseFactoryTest extends BaseTestCase
{
    public function testResponseIsCreated(): void
    {
        $factory = new ResponseFactory();

        $response = $factory->create();

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}