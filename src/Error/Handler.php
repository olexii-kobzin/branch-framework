<?php
namespace Branch\Error;

use Branch\Interfaces\EnvInterface;
use Throwable;

class Handler
{
    public function __invoke(Throwable $e)
    {
        $eol = PHP_EOL;
        $code = 500;
        $stringCode = '';

        if (is_integer($e->getCode())) {
            $code = $e->getCode();
        } else {
            $stringCode = "{$e->getCode()}: ";
        }

        $trace = ENV['APP_ENV'] === EnvInterface::ENV_DEV ? $e->getTraceAsString() : '';

        http_response_code($code);

        echo <<<RESPONSE
{$stringCode}{$e->getMessage()} ({$e->getFile()}:{$e->getLine()})
{$eol}{$trace}
RESPONSE;   
    }
}