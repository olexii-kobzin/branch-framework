<?php
declare(strict_types=1);

namespace Branch\Tests\Mocks\Constructor;

class WithParamsNoType 
{
    public $constructorParam1;

    public function __construct($param1)
    {
        $this->constructorParam1 = $param1;
    }
    
}