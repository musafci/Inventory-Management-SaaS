<?php

namespace App\Services;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AuthService
{
    /**
     * Register a new organization and owner user, then issue OAuth tokens.
     *
     * @return array{user: User, organizations: \Illuminate\Support\Collection, token: array<string, mixed>}
     */
    public function register(array $data): array
    {
        [$user, $organization] = DB::transaction(function () use ($data) {
            $organization = Organization::query()->create([
                'name' => $data['organization_name'],
                'slug' => $this->uniqueSlug($data['organization_name']),
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'plan' => 'trial',
                'status' => OrganizationStatus::Trial,
                'trial_ends_at' => now()->addDays(14),
            ]);

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'default_organization_id' => $organization->id,
            ]);

            $user->organizations()->attach($organization->id, ['role' => 'Owner']);

            return [$user, $organization];
        });

        $user->load('organizations');

        return [
            'user' => $user,
            'organizations' => $user->organizations,
            'token' => $this->issuePasswordGrantToken($data['email'], $data['password']),
        ];
    }

    /**
     * Authenticate a user via the Passport password grant.
     *
     * @return array{user: User, organizations: \Illuminate\Support\Collection, token: array<string, mixed>}
     */
    public function login(string $email, string $password): array
    {
        $token = $this->issuePasswordGrantToken($email, $password);

        $user = User::query()->where('email', $email)->firstOrFail();
        $user->forceFill(['last_login_at' => now()])->save();
        $user->load('organizations');

        return [
            'user' => $user,
            'organizations' => $user->organizations,
            'token' => $token,
        ];
    }

    /**
     * Refresh an access token using a refresh token.
     *
     * @return array{user: User, organizations: \Illuminate\Support\Collection, token: array<string, mixed>}
     */
    public function refresh(string $refreshToken): array
    {
        $token = $this->requestOAuthToken([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        $user = $this->resolveUserFromAccessToken($token['access_token']);
        $user->load('organizations');

        return [
            'user' => $user,
            'organizations' => $user->organizations,
            'token' => $token,
        ];
    }

    /**
     * @return array{user: User, organizations: \Illuminate\Support\Collection}
     */
    public function me(User $user): array
    {
        $user->load('organizations');

        return [
            'user' => $user,
            'organizations' => $user->organizations,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function issuePasswordGrantToken(string $email, string $password): array
    {
        return $this->requestOAuthToken([
            'grant_type' => 'password',
            'username' => $email,
            'password' => $password,
            'scope' => '',
        ]);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    protected function requestOAuthToken(array $params): array
    {
        $credentials = $this->passwordClientCredentials();

        $request = Request::create('/oauth/token', 'POST', array_merge([
            'client_id' => $credentials['client_id'],
            'client_secret' => $credentials['client_secret'],
        ], $params));

        $request->headers->set('Accept', 'application/json');

        $response = app()->handle($request);
        $body = json_decode($response->getContent(), true) ?? [];

        if ($response->getStatusCode() !== 200) {
            throw new AuthenticationException($body['message'] ?? 'These credentials do not match our records.');
        }

        return $body;
    }

    /**
     * @return array{client_id: string, client_secret: string}
     */
    protected function passwordClientCredentials(): array
    {
        $clientId = config('passport.password_grant.client_id');
        $clientSecret = config('passport.password_grant.client_secret');

        if (! $clientId || ! $clientSecret) {
            throw new RuntimeException('Passport password grant client is not configured.');
        }

        return [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ];
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'organization';
        $slug = $base;
        $counter = 1;

        while (Organization::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    protected function resolveUserFromAccessToken(string $accessToken): User
    {
        $parts = explode('.', $accessToken);

        if (count($parts) !== 3) {
            throw new AuthenticationException('Unable to resolve user from access token.');
        }

        $payload = json_decode($this->base64UrlDecode($parts[1]), true);

        if (! is_array($payload) || ! isset($payload['sub'])) {
            throw new AuthenticationException('Unable to resolve user from access token.');
        }

        return User::query()->findOrFail($payload['sub']);
    }

    protected function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder !== 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}
