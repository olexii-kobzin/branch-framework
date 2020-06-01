<?php
declare(strict_types=1);

namespace Branch\Events;

use Branch\Interfaces\Events\RepositoryInterface;
use Branch\Interfaces\Events\SubjectInterface;

class Repository implements RepositoryInterface
{
    use SubjectTrait;

    public function notify(string $event, SubjectInterface $subject): void
    {
        if (!isset($this->observers[$event])) {
            return;
        }

        foreach ($this->observers[$event] as $observer) {
            call_user_func($observer, $subject);
        }
    }
}