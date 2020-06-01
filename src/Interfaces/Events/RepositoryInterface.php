<?php
namespace Branch\Interfaces\Events;

use Branch\Events\RepositorySubjectInterface;

interface RepositoryInterface extends SubjectInterface
{
    public function notify(string $event, RepositorySubjectInterface $subject): void;
}