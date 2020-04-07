<?php
declare(strict_types=1);

namespace Branch;

use Branch\Interfaces\EnvInterface;

class Env implements EnvInterface
{
    protected string $envPath = '../.env';
    
    public function get(): array
    {
        $lines = $this->readEnv();

        return $this->parseEnv($lines);
    }

    protected function readEnv(): array
    {
        $filePath = realpath($this->envPath);

        return file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
    
    protected function parseEnv($lines): array
    {
        $env = [];

        foreach ($lines as $line) {
            $parts = explode('=', $line);
            $env[$parts[0]] = trim($parts[1] ?? '');
        }

        return $env;
    }
}