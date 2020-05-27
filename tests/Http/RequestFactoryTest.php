<?php
declare(strict_types=1);

use Branch\Http\RequestFactory;
use Branch\Tests\BaseTestCase;
use Psr\Http\Message\ServerRequestInterface;

class RequestFactoryTest extends BaseTestCase
{
    public function testServerRequestIsCreated(): void
    {
        $factory = new RequestFactory();

        $request = $factory->create();

        $this->assertInstanceOf(ServerRequestInterface::class, $request);
    }
}