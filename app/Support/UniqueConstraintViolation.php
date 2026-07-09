<?php

namespace App\Support;

use Illuminate\Database\QueryException;

final class UniqueConstraintViolation
{
    public static function matches(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = isset($exception->errorInfo[1]) ? (int) $exception->errorInfo[1] : null;
        $driverMessage = (string) ($exception->errorInfo[2] ?? '');

        if ($sqlState === '23505') {
            return true;
        }

        if ($sqlState === '23000' && $driverCode === 19) {
            return str_starts_with($driverMessage, 'UNIQUE constraint failed');
        }

        return false;
    }
}
