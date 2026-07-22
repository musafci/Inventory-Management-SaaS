<?php

namespace App\Services\Web;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Facades\Session;

class PlatformApiClient
{
    protected string $token;

    public function __construct(
        protected ?PlatformSessionService $platformSession = null,
    ) {
        $this->platformSession ??= app(PlatformSessionService::class);
        $this->token = Session::get('platform_auth_token', '');
    }

    protected function makeRequest(string $method, string $endpoint, array $data = [], array $params = []): array
    {
        $uri = '/api/platform/v1'.$endpoint;

        if ($params !== []) {
            $uri .= '?'.http_build_query($params);
        }

        $request = Request::create(
            $uri,
            strtoupper($method),
            $method === 'GET' ? $data : [],
            [], [], [],
            ($method !== 'GET' && $data !== []) ? json_encode($data) : null,
        );

        $request->headers->set('Authorization', "Bearer {$this->token}");
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/json');

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
                $this->platformSession->clearAuthSession();
            }

            $result = ['error' => $message, 'status' => $response->getStatusCode()];

            if (! empty($body['errors']) && is_array($body['errors'])) {
                $result['errors'] = $body['errors'];
            }

            return $result;
        }

        return $body;
    }

    public function get(string $endpoint, array $params = []): array
    {
        return $this->makeRequest('GET', $endpoint, [], $params);
    }

    public function patch(string $endpoint, array $data = []): array
    {
        return $this->makeRequest('PATCH', $endpoint, $data);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->makeRequest('POST', $endpoint, $data);
    }
}
