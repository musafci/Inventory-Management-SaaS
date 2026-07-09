<?php

namespace App\Services;

use App\Enums\IdempotencyKeyStatus;
use App\Models\IdempotencyKey;
use App\Support\IdempotencyBeginResult;
use App\Support\UniqueConstraintViolation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyService
{
    public function begin(
        int $organizationId,
        int $userId,
        string $idempotencyKey,
        string $routeFingerprint,
        string $requestHash,
    ): IdempotencyBeginResult {
        $deadline = microtime(true) + (float) config('api.idempotency_wait_seconds', 30);

        while (microtime(true) < $deadline) {
            $result = $this->tryBegin(
                $organizationId,
                $userId,
                $idempotencyKey,
                $routeFingerprint,
                $requestHash,
            );

            if ($result !== null) {
                return $result;
            }

            usleep(50_000);
        }

        throw ValidationException::withMessages([
            'idempotency_key' => ['A request with this idempotency key is still in progress.'],
        ]);
    }

    public function complete(IdempotencyKey $record, Response $response): void
    {
        $record->update([
            'status' => IdempotencyKeyStatus::Completed,
            'response_status_code' => $response->getStatusCode(),
            'response_body' => $response->getContent(),
            'completed_at' => now(),
        ]);
    }

    public function release(IdempotencyKey $record): void
    {
        $record->delete();
    }

    public static function fingerprintRequest(string $method, string $uri, array $payload): string
    {
        $normalized = self::normalizePayload($payload);

        return hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR));
    }

    public static function routeFingerprint(string $method, string $uri): string
    {
        return strtoupper($method).':'.$uri;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private static function normalizePayload(array $payload): array
    {
        ksort($payload);

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = self::normalizePayload($value);
            }
        }

        return $payload;
    }

    private function tryBegin(
        int $organizationId,
        int $userId,
        string $idempotencyKey,
        string $routeFingerprint,
        string $requestHash,
    ): ?IdempotencyBeginResult {
        try {
            return DB::transaction(function () use (
                $organizationId,
                $userId,
                $idempotencyKey,
                $routeFingerprint,
                $requestHash,
            ): ?IdempotencyBeginResult {
                $existing = IdempotencyKey::query()
                    ->where('organization_id', $organizationId)
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    $this->assertMatchingRequest($existing, $routeFingerprint, $requestHash);

                    if ($existing->status === IdempotencyKeyStatus::Completed) {
                        return IdempotencyBeginResult::replay($existing);
                    }

                    return null;
                }

                $record = IdempotencyKey::query()->create([
                    'organization_id' => $organizationId,
                    'user_id' => $userId,
                    'idempotency_key' => $idempotencyKey,
                    'route_fingerprint' => $routeFingerprint,
                    'request_hash' => $requestHash,
                    'status' => IdempotencyKeyStatus::Processing,
                ]);

                return IdempotencyBeginResult::claimed($record);
            });
        } catch (QueryException $exception) {
            if (UniqueConstraintViolation::matches($exception)) {
                return null;
            }

            throw $exception;
        }
    }

    private function assertMatchingRequest(
        IdempotencyKey $existing,
        string $routeFingerprint,
        string $requestHash,
    ): void {
        if ($existing->route_fingerprint !== $routeFingerprint) {
            throw ValidationException::withMessages([
                'idempotency_key' => ['This idempotency key was already used for a different endpoint.'],
            ]);
        }

        if ($existing->request_hash !== $requestHash) {
            throw ValidationException::withMessages([
                'idempotency_key' => ['This idempotency key was already used with a different request body.'],
            ]);
        }
    }
}
