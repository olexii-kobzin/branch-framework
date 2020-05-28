<?php
declare(strict_types=1);

namespace Branch\Tests\Mocks\Constructor;

class WithParams
{
    public string $string;

    public int $int;

    public function __construct(string $string, int $int = 3)
    {
        $this->string = $string;
        $this->int = $int;
    }
    
}