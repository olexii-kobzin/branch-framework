<?php
namespace Branch\Events;

use Branch\Interfaces\Events\RepositoryInterface;

interface RepositorySubjectInterface
{
    public function notifyRepository(RepositoryInterface $repository, string $event): void;
}