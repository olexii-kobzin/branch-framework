<?php
namespace Branch\Events;

trait SubjectTrait
{
    protected array $observers = [];

    public function attach(callable $callable, string $event): string
    {
        $id = $this->getObserverId($callable, $event);

        $this->observers[$event][$id] = \Closure::fromCallable($callable);

        return $id;
    }

    public function detach(string $event, string $id): void
    {
        if (isset($this->observers[$event][$id])) {
            unset($this->observers[$event][$id]);
        }
    }

    public function notify(string $event): void
    {
        if (!isset($this->observers[$event])) {
            return;
        }

        foreach ($this->observers[$event] as $observer) {
            call_user_func($observer, $this);
        }
    }

    protected function getObserverId(callable $callable, string $event): string
    {
        $hash = spl_object_hash((object) $callable);
        
        return hash(
            'sha256',
            $hash . $event . microtime() . uniqid()
        );
    }
}