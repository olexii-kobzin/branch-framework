<?php
declare(strict_types=1);

namespace Branch\Events;

use Branch\Interfaces\Events\EventInterface;

class Event implements EventInterface
{
    protected ?bool $stopped = null;

    protected ?object $target;

    protected string $name;

    protected $payload;

    public function __construct(string $name, ?object $target, $payload)
    {
        $this->target = $target;
        $this->name = $name;
        $this->payload = $payload;
    }

    public function isPropagationStopped(): bool
    {
        return $this->stopped === true;
    }

    public function stopPropagation(): void
    {
        $this->stopped = true;
    }

    public function getStopped(): ?bool
    {
        return $this->stopped;
    }

    public function getTarget(): ?object
    {
        return $this->target;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function setPayload($payload): void
    {
        $this->payload = $payload;
    }
}