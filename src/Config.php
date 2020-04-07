<?php
declare(strict_types=1);

namespace Branch;

use Branch\Interfaces\ConfigInterface;
use Adbar\Dot;

class Config implements ConfigInterface
{
    protected string $path = '../config/config.php';

    protected array $config = [];
    
    protected Dot $dot;

    public function __construct()
    {
        $this->config = require realpath($this->path);

        $this->dot = new Dot($this->config);
    }

    public function getAll(): array
    {
        return $this->dot->all();
    }

    public function get(string $key)
    {
        return $this->dot->get($key);
    }

    public function set(string $key, $value): void
    {
        $this->dot->has($key)
            ? $this->dot->set($key, $value)
            : $this->dot->add($key, $value);
    }
}