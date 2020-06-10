<?php
declare(strict_types=1);

namespace Branch\Events;

use Branch\Interfaces\Events\ListenerProviderInterface;

class ListenerProvider implements ListenerProviderInterface
{
    protected array $listeners = [];

    protected array $listenerLookup = [];

    public function getListenersForEvent(object $event): iterable
    {
        $name = $event->getName();
        $targetHash = $event->getTarget() ? spl_object_hash($event->getTarget()) : '';

        if (empty($this->listenerLookup[$name][$targetHash])) {
            return [];
        }

        foreach ($this->listenerLookup[$name][$targetHash] as $listenerHash) {
            yield $this->listeners[$name][$targetHash][$listenerHash];
        }
    }

    public function addListener(string $name, callable $listener, object $target = null, int $priority = 0): void
    {
        $this->validatePriority($priority);

        $targetHash = $target ? spl_object_hash($target) : '';
        $listenerHash = microtime() . uniqid(true);

        $this->listeners[$name][$targetHash][$listenerHash] = $listener;

        $nextPriority = $this->getPriority($name, $targetHash, $priority);
        $this->listenerLookup[$name][$targetHash][$nextPriority] = $listenerHash;

        ksort($this->listenerLookup[$name][$targetHash], SORT_NUMERIC);
    }

    protected function getPriority(string $name, string $targetHash, int $priority): string
    {
        $counter = 1;

        while (isset($this->listenerLookup[$name][$targetHash]["$priority.$counter"])) {
            $counter++;
        }

        return "$priority.$counter";
    }

    protected function validatePriority(int $priority): void
    {
        if ($priority < 0) {
            throw new \LogicException('Priority must be equal or greater than 0');
        }
    }
}