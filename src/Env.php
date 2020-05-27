<?php
declare(strict_types=1);

namespace Branch;

use Branch\Interfaces\EnvInterface;

class Env implements EnvInterface
{
    protected string $path = '';

    public function __construct(string $path)
    {
        $this->path = $path;
    }
    
    public function get(): array
    {
        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        return $this->parseEnv($lines);
    }
    
    protected function parseEnv(array $lines): array
    {
        $env = [];

        foreach ($lines as $line) {
            $parts = explode('=', $line);
            $env[$parts[0]] = trim($parts[1]) ? trim($parts[1]) :  null;
        }

        return $env;
    }
}