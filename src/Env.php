<?php
declare(strict_types=1);

namespace Branch;

use Branch\Interfaces\EnvInterface;

class Env implements EnvInterface
{
    private string $envPath = '';

    public function __construct(string $envPath)
    {
        $this->envPath = $envPath;
    }
    
    public function get(): array
    {
        $lines = file($this->envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        return $this->parseEnv($lines);
    }
    
    private function parseEnv($lines): array
    {
        $env = [];

        foreach ($lines as $line) {
            $parts = explode('=', $line);
            $env[$parts[0]] = trim($parts[1]) ? trim($parts[1]) :  null;
        }

        return $env;
    }
}