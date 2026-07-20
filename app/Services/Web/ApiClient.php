<?php

namespace App\Services\Web;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ApiClient
{
    protected string $token;
    protected string $orgId;

    public function __construct(
        protected ?WebSessionService $webSession = null,
    ) {
        $this->webSession ??= app(WebSessionService::class);
        $this->webSession->refreshIfNeeded();

        $this->token = Session::get('auth_token', '');
        $this->orgId = Session::get('organization_id', '');
    }

    protected function requiresIdempotencyKey(string $method, string $endpoint): bool
    {
        return strtoupper($method) === 'POST'
            && preg_match('#^/v1/(purchase-orders|sales-orders)$#', $endpoint) === 1;
    }

    protected function makeRequest(string $method, string $endpoint, array $data = [], array $params = [], ?string $idempotencyKey = null): array
    {
        $uri = '/api' . $endpoint;
        if (! empty($params)) {
            $uri .= '?' . http_build_query($params);
        }

        $request = Request::create(
            $uri,
            strtoupper($method),
            $method === 'GET' ? $data : [],
            [], [], [],
            ($method !== 'GET' && ! empty($data)) ? json_encode($data) : null
        );

        $request->headers->set('Authorization', "Bearer {$this->token}");
        $request->headers->set('X-Organization-Id', $this->orgId);
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/json');

        if ($idempotencyKey !== null || $this->requiresIdempotencyKey($method, $endpoint)) {
            $request->headers->set('Idempotency-Key', $idempotencyKey ?? (string) Str::uuid());
        }

        $sessionName = config('session.cookie', 'laravel_session');
        if ($sessionId = Session::getId()) {
            $request->cookies->set($sessionName, $sessionId);
        }

        $originalRequest = app('request');
        $originalSession = app()->bound('session.store') ? app('session.store') : null;

        try {
            $response = app()->handle($request);
        } finally {
            app()->instance('request', $originalRequest);
            RequestFacade::clearResolvedInstance();

            if ($originalSession) {
                app()->instance('session.store', $originalSession);
            }

            if ($url = app()->bound('url') ? app('url') : null) {
                $url->setRequest($originalRequest);
            }
        }

        $body = json_decode($response->getContent(), true) ?? [];

        if ($response->getStatusCode() >= 400) {
            $message = $body['message'] ?? 'Request failed';

            if ($response->getStatusCode() === 401) {
                $this->webSession->clearAuthSession();
            }

            return ['error' => $message, 'status' => $response->getStatusCode()];
        }

        return $body;
    }

    public function get(string $endpoint, array $params = []): array
    {
        return $this->makeRequest('GET', $endpoint, [], $params);
    }

    public function post(string $endpoint, array $data = [], ?string $idempotencyKey = null): array
    {
        return $this->makeRequest('POST', $endpoint, $data, [], $idempotencyKey);
    }

    public function put(string $endpoint, array $data = []): array
    {
        return $this->makeRequest('PUT', $endpoint, $data);
    }

    public function delete(string $endpoint): array
    {
        return $this->makeRequest('DELETE', $endpoint);
    }
}
