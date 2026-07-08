<?php

namespace App\Providers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Exceptions\OAuthServerException;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::enablePasswordGrant();

        Response::macro('api', function (mixed $data = null, array $meta = [], int $status = HttpResponse::HTTP_OK) {
            $payload = ['data' => $data];

            if ($meta !== []) {
                $payload['meta'] = $meta;
            }

            return response()->json($payload, $status);
        });

        Response::macro('apiError', function (string $message, array $errors = [], int $status = HttpResponse::HTTP_BAD_REQUEST) {
            return response()->json([
                'message' => $message,
                'errors' => $errors,
            ], $status);
        });
    }

    public static function registerApiExceptionRendering(Exceptions $exceptions): void
    {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $exception->errors(),
            ], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage() ?: 'Unauthenticated.',
                'errors' => [],
            ], HttpResponse::HTTP_UNAUTHORIZED);
        });

        $exceptions->render(function (OAuthServerException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $payload = json_decode($exception->getResponse()->getContent(), true) ?? [];

            return response()->json([
                'message' => $payload['message'] ?? 'Authentication failed.',
                'errors' => [],
            ], $exception->getResponse()->getStatusCode());
        });
    }
}
