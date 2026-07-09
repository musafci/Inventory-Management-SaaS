<?php

namespace App\Support;

use App\Models\IdempotencyKey;

final class IdempotencyBeginResult
{
    private function __construct(
        public readonly bool $isReplay,
        public readonly ?IdempotencyKey $record,
        public readonly ?int $responseStatusCode,
        public readonly ?string $responseBody,
    ) {}

    public static function claimed(IdempotencyKey $record): self
    {
        return new self(
            isReplay: false,
            record: $record,
            responseStatusCode: null,
            responseBody: null,
        );
    }

    public static function replay(IdempotencyKey $record): self
    {
        return new self(
            isReplay: true,
            record: $record,
            responseStatusCode: $record->response_status_code,
            responseBody: $record->response_body,
        );
    }
}
