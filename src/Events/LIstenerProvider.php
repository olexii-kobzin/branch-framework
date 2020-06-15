<?php
declare(strict_types=1);

namespace Branch\Events;

use Branch\Interfaces\Events\ListenerProviderInterface;

class ListenerProvider implements ListenerProviderInterface
{
    protected const COUNTER_DEFAULT = PHP_INT_MAX;

    protected array $listeners = [];

    protected array $listenerLookup = [];

    public function getListenersForEvent(object $event): iterable
    {
        $targetId = $event->getTarget() ? spl_object_hash($event->getTarget()) : '';
        $name = $event->getName();

        if (empty($this->listenerLookup[$targetId][$name])) {
            return [];
        }

        foreach ($this->listenerLookup[$targetId][$name] as $listenerId) {
            yield $this->listeners[$targetId][$name][$listenerId];
        }
    }

    public function addListener(string $name, callable $listener, object $target = null, int $priority = 0): void
    {
        $this->validatePriority($priority);

        $targetId = $target ? spl_object_hash($target) : '';
        $listenerId = microtime(true) . uniqid('', true);

        $this->listeners[$targetId][$name][$listenerId] = $listener;

        $nextPriority = $this->getPriority($targetId, $name, $priority);
        $this->listenerLookup[$targetId][$name][$nextPriority] = $listenerId;

        krsort($this->listenerLookup[$targetId][$name], SORT_NUMERIC);
    }

    protected function getPriority(string $targetId, string $name, int $priority): string
    {
        $counter = self::COUNTER_DEFAULT;

        while (isset($this->listenerLookup[$targetId][$name]["$priority-$counter"])) {
            $counter--;
        }

        return "$priority-$counter";
    }

    protected function validatePriority(int $priority): void
    {
        if ($priority < 0) {
            throw new \LogicException('Priority must be equal or greater than 0');
        }
    }
}