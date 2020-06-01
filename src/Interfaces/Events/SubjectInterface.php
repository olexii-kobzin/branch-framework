<?php
namespace Branch\Interfaces\Events;

interface SubjectInterface
{
    public function attach(callable $callable, string $event): string;

    public function detach(string $event, string $id): void;

    public function notify(string $event): void;
}