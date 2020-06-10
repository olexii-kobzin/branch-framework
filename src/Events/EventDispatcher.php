<?php
declare(strict_types=1);

namespace Branch\Events;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class EventDispatcher implements EventDispatcherInterface
{

    protected ListenerProviderInterface $provider;

    public function __construct(ListenerProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    public function dispatch(object $event)
    {
        foreach ($this->provider->getListenersForEvent($event) as $listener) {
            $listener($event);
            
            if ($event->isPropagationStopped()) {
                break;
            }
        }

        return $event;
    }
}