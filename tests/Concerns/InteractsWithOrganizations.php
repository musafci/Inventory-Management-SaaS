<?php

namespace Tests\Concerns;

use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\Plan;
use App\Services\OrganizationSubscriptionService;
use Illuminate\Testing\TestResponse;

trait InteractsWithOrganizations
{
    /**
     * @return array{organization_id: int, token: string, response: TestResponse}
     */
    protected function registerOrganizationWithOwner(array $overrides = []): array
    {
        $response = $this->postJson('/api/v1/auth/register', \validRegistrationPayload($overrides))
            ->assertCreated();

        $organizationId = (int) $response->json('data.organizations.0.id');
        $enterprise = Plan::query()->where('slug', 'enterprise')->first();

        if ($enterprise !== null) {
            app(OrganizationSubscriptionService::class)->updateSubscription(
                Organization::query()->findOrFail($organizationId),
                $enterprise,
                SubscriptionStatus::Active,
            );
        }

        return [
            'organization_id' => $organizationId,
            'token' => $response->json('data.token.access_token'),
            'response' => $response,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function organizationHeaders(string $token, int|string $organizationId): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'X-Organization-Id' => (string) $organizationId,
        ];
    }

    /**
     * Headers for a request that switches authenticated user or organization.
     * Feature tests reuse the application container, so the API guard and tenant
     * context must be reset when alternating between organizations.
     *
     * @return array<string, string>
     */
    protected function organizationContextHeaders(string $token, int|string $organizationId): array
    {
        app('auth')->forgetGuards();
        app()->forgetInstance('currentOrganization');

        return $this->organizationHeaders($token, $organizationId);
    }

    /**
     * @return array{organization_id: int, token: string, response: TestResponse}
     */
    protected function loginOrganizationOwner(string $email, string $password = 'password123'): array
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ])->assertOk();

        return [
            'organization_id' => $response->json('data.organizations.0.id'),
            'token' => $response->json('data.token.access_token'),
            'response' => $response,
        ];
    }
}
