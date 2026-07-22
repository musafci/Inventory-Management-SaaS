<?php

namespace App\Services;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationSubscriptionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Str;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use RuntimeException;

class AuthService
{
    public function __construct(
        protected OrganizationSubscriptionService $subscriptionService,
    ) {}

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
                'plan' => config('subscription.trial_plan_slug', 'growth'),
                'status' => OrganizationStatus::Trial,
                'trial_ends_at' => now()->addDays((int) config('subscription.trial_days', 14)),
            ]);

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'default_organization_id' => $organization->id,
            ]);

            $user->organizations()->attach($organization->id, ['role' => 'Org Owner']);

            setPermissionsTeamId($organization->id);

            app(RolesAndPermissionsSeeder::class)->seedRolesForOrganization($organization);

            $user->assignRole('Org Owner');

            $this->subscriptionService->assignTrialPlan($organization);

            return [$user, $organization];
        });

        try {
            $token = $this->issuePasswordGrantToken($data['email'], $data['password']);
        } catch (AuthenticationException $exception) {
            $this->rollbackRegistration($user, $organization);

            throw $exception;
        }

        $user->load('organizations');

        return [
            'user' => $user,
            'organizations' => $user->organizations,
            'token' => $token,
        ];
    }

    protected function rollbackRegistration(User $user, Organization $organization): void
    {
        DB::transaction(function () use ($user, $organization): void {
            setPermissionsTeamId($organization->id);
            $user->syncRoles([]);
            $user->organizations()->detach($organization->id);
            $user->delete();
            $organization->delete();
        });
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
     * Revoke the current access token for the authenticated user.
     */
    public function logout(User $user, ?string $accessToken = null): void
    {
        if ($accessToken !== null) {
            $this->revokeAccessToken($accessToken);

            return;
        }

        $token = $user->token();

        if ($token !== null) {
            $token->revoke();
        }
    }

    /**
     * Pick the organization to activate after login/register.
     */
    public function resolvePreferredOrganizationId(User $user): ?int
    {
        $user->loadMissing('organizations');

        if (
            $user->default_organization_id !== null
            && $user->organizations->contains('id', $user->default_organization_id)
        ) {
            return (int) $user->default_organization_id;
        }

        return $user->organizations->sortBy('name')->first()?->id;
    }

    /**
     * Revoke a bearer access token without an authenticated request context.
     */
    public function revokeAccessToken(string $accessToken): void
    {
        $tokenId = $this->extractTokenId($accessToken);

        if ($tokenId === null) {
            return;
        }

        \Laravel\Passport\Token::query()
            ->whereKey($tokenId)
            ->update(['revoked' => true]);

        \Laravel\Passport\RefreshToken::query()
            ->where('access_token_id', $tokenId)
            ->update(['revoked' => true]);
    }

    protected function extractTokenId(string $accessToken): ?string
    {
        try {
            $token = (new Parser(new JoseEncoder()))->parse($accessToken);
            $tokenId = $token->claims()->get('jti');

            return $tokenId !== null ? (string) $tokenId : null;
        } catch (\Throwable) {
            return null;
        }
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
        $originalRequest = app('request');

        $request = Request::create('/oauth/token', 'POST', array_merge([
            'client_id' => $credentials['client_id'],
            'client_secret' => $credentials['client_secret'],
        ], $params));

        $request->headers->set('Accept', 'application/json');

        try {
            $response = app()->handle($request);
        } finally {
            app()->instance('request', $originalRequest);
            RequestFacade::clearResolvedInstance();

            if ($url = app()->bound('url') ? app('url') : null) {
                $url->setRequest($originalRequest);
            }
        }

        $body = json_decode($response->getContent(), true) ?? [];

        if ($response->getStatusCode() !== 200) {
            $message = match ($body['error'] ?? null) {
                'invalid_client' => 'OAuth client is misconfigured. Run: php artisan passport:ensure-password-client --write-env',
                'invalid_grant' => 'These credentials do not match our records.',
                default => $body['message'] ?? $body['error_description'] ?? 'Authentication failed.',
            };

            throw new AuthenticationException($message);
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
