<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Services\IdempotencyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnforceIdempotency
{
    public function __construct(
        protected IdempotencyService $idempotencyService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));

        if ($idempotencyKey === '') {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'idempotency_key' => ['The Idempotency-Key header is required.'],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (strlen($idempotencyKey) > 255) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'idempotency_key' => ['The Idempotency-Key header may not be greater than 255 characters.'],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var Organization $organization */
        $organization = app('currentOrganization');
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'errors' => [],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $routeFingerprint = IdempotencyService::routeFingerprint(
            $request->method(),
            $request->path(),
        );

        $requestHash = IdempotencyService::fingerprintRequest(
            $request->method(),
            $request->path(),
            $request->all(),
        );

        $begin = $this->idempotencyService->begin(
            $organization->id,
            $user->id,
            $idempotencyKey,
            $routeFingerprint,
            $requestHash,
        );

        if ($begin->isReplay) {
            return response($begin->responseBody, $begin->responseStatusCode)
                ->header('Content-Type', 'application/json')
                ->header('Idempotency-Replayed', 'true');
        }

        /** @var \App\Models\IdempotencyKey $record */
        $record = $begin->record;

        try {
            $response = $next($request);

            if ($response->isSuccessful()) {
                $this->idempotencyService->complete($record, $response);
            } else {
                $this->idempotencyService->release($record);
            }

            return $response;
        } catch (Throwable $exception) {
            $this->idempotencyService->release($record);

            throw $exception;
        }
    }
}
