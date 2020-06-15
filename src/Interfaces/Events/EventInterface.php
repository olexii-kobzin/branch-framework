<?php
namespace Branch\Interfaces\Events;

use Psr\EventDispatcher\StoppableEventInterface;

interface EventInterface extends StoppableEventInterface
{
    public function stopPropagation(): void;

    public function getStopped(): ?bool;

    public function getTarget(): ?object;

    public function getName(): string;  

    public function getPayload();

    public function setPayload($payload): void;
}