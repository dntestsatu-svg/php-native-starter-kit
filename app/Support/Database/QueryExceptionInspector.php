<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Support\Database;

use Illuminate\Database\QueryException;

final class QueryExceptionInspector
{
    public static function isUniqueConstraintViolation(QueryException $exception): bool
    {
        if ((string) $exception->getCode() === '23000') {
            return true;
        }

        $errorInfo = $exception->errorInfo;

        if (!is_array($errorInfo)) {
            return false;
        }

        $sqlState = (string) ($errorInfo[0] ?? '');

        if ($sqlState === '23000') {
            return true;
        }

        $driverErrorCode = (int) ($errorInfo[1] ?? 0);

        return in_array($driverErrorCode, [19, 1062, 1557, 2067], true);
    }
}
