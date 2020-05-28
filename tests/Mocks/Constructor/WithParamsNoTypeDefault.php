<?php
declare(strict_types=1);

namespace Branch\Tests\Mocks\Constructor;

class WithParamsNoTypeDefault
{
    public $constructorParam1;

    public function __construct($param1 = 'test')
    {
        $this->constructorParam1 = $param1;
    }
    
}