<?php
declare(strict_types=1);

use Branch\Interfaces\Events\RepositoryInterface;

trait RepositorySubjectTrait 
{
    public function notifyRepository(RepositoryInterface $repository, string $event): void
    {
        $repository->notify($event, $this);
    }
}