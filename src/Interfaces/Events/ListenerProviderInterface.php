<?php
namespace Branch\Interfaces\Events;

use Psr\EventDispatcher\ListenerProviderInterface as PsrListenerProviderInterface;

interface ListenerProviderInterface extends PsrListenerProviderInterface
{
    public function addListener(string $name, callable $listener, object $target = null, int $priority = 0): void;
}