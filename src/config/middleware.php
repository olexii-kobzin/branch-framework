<?php
declare(strict_types=1);

use Branch\Middleware\ErrorMiddleware;
use Branch\Middleware\MethodValidationMiddleware;

return [
    ErrorMiddleware::class,
    MethodValidationMiddleware::class,
];