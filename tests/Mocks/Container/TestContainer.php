<?php
declare(strict_types=1);

namespace Branch\Tests\Mocks\Container;

use Branch\Container\Container;
use Branch\Interfaces\Container\DefinitionInfoInterface;
use Branch\Interfaces\Container\InvokerInterface;
use Branch\Interfaces\Container\ResolverInterface;

class TestContainer extends Container
{
    public function setResolver(ResolverInterface $resolver): void
    {
        $this->resolver = $resolver;
    }

    public function setInvoker(InvokerInterface $invoker): void
    {
        $this->invoker = $invoker;
    }
    
    public function setDefiniionInfo(DefinitionInfoInterface $definitionInfo): void
    {
        $this->definitionInfo = $definitionInfo;
    }
}