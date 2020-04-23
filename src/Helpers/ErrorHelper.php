<?php
declare(strict_types=1);

namespace Branch\Helpers;

use Throwable;

class ErrorHelper
{
    public const HTTP_MIN_CODE = 100;
    public const HTTP_MAX_CODE = 599;

    public static function getHttpCode(Throwable $e)
    {
        $code = $e->getCode();
        $isHttpCode = is_integer($code) && self::isAllowedHttpCode($code);

        return $isHttpCode ? $code : 500;
    }

    protected static function isAllowedHttpCode(int $code)
    {
        return self::HTTP_MIN_CODE <= $code && $code <= self::HTTP_MAX_CODE;
    }
}