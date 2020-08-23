<?php
declare(strict_types=1);

namespace Branch\Tests\Mocks\Constructor;

class WithDependencies
{
    public WithoutConstructor $dependency;

    public function __construct(WithoutConstructor $dependency)
    {
        $this->dependency = $dependency;
    }   
}