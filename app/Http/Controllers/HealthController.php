<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
        ];

        $healthy = collect($checks)->every(fn (array $check): bool => $check['ok']);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    /**
     * @return array{ok: bool, message: string}
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['ok' => true, 'message' => 'connected'];
        } catch (\Throwable $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }
    }

    /**
     * @return array{ok: bool, message: string}
     */
    protected function checkRedis(): array
    {
        $usesRedis = in_array('redis', [
            config('cache.default'),
            config('queue.default'),
            config('session.driver'),
        ], true);

        if (! $usesRedis) {
            return ['ok' => true, 'message' => 'not configured'];
        }

        try {
            Redis::connection()->ping();

            return ['ok' => true, 'message' => 'connected'];
        } catch (\Throwable $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }
    }

    /**
     * @return array{ok: bool, message: string}
     */
    protected function checkQueue(): array
    {
        try {
            $connection = config('queue.default');
            Queue::connection($connection)->size();

            return ['ok' => true, 'message' => $connection];
        } catch (\Throwable $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }
    }
}
