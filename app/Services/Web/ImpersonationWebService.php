<?php

namespace App\Services\Web;

use App\Models\Organization;
use App\Models\PlatformAdmin;
use App\Models\User;
use App\Services\AuthService;
use App\Services\ImpersonationService;
use Illuminate\Support\Facades\Session;

class ImpersonationWebService
{
    public function __construct(
        protected ImpersonationService $impersonationService,
        protected WebSessionService $webSession,
        protected PlatformSessionService $platformSession,
        protected AuthService $authService,
    ) {}

    public function start(PlatformAdmin $admin, Organization $organization, User $user, string $reason): void
    {
        $result = $this->impersonationService->start($admin, $organization, $user, $reason);
        $platformBackup = $this->platformSession->exportSession();

        Session::put('platform_session_backup', $platformBackup);
        Session::put('impersonation', [
            'log_id' => $result['impersonation']['log_id'],
            'token_id' => $result['log']->token_id,
            'platform_admin_id' => $admin->id,
            'platform_admin_name' => $admin->name,
            'organization_id' => $organization->id,
            'organization_name' => $organization->name,
            'impersonated_user_name' => $user->name,
            'reason' => $reason,
            'return_url' => route('platform.organizations.show', $organization->id),
        ]);

        $this->platformSession->clearAuthSession();

        $organizations = $user->organizations()
            ->where('organizations.id', $organization->id)
            ->get();

        $this->webSession->storeAuthSession(
            $result['token'],
            $user->toArray(),
            $organizations,
        );

        Session::put('organization_id', $organization->id);
        $this->webSession->syncPermissionsForActiveOrganization();
    }

    public function exit(): string
    {
        $impersonation = Session::get('impersonation', []);
        $returnUrl = $impersonation['return_url'] ?? route('platform.dashboard');
        $platformBackup = Session::get('platform_session_backup', []);

        if ($token = Session::get('auth_token')) {
            $this->authService->revokeAccessToken($token);
        }

        $adminId = (int) ($impersonation['platform_admin_id'] ?? 0);
        $tokenId = $impersonation['token_id'] ?? null;

        if ($adminId > 0) {
            $admin = PlatformAdmin::query()->find($adminId);

            if ($admin !== null) {
                $this->impersonationService->end($admin, $tokenId !== null ? (string) $tokenId : null);
            }
        }

        $this->webSession->clearAuthSession();
        Session::forget(['impersonation', 'platform_session_backup']);

        if ($platformBackup !== []) {
            Session::put($platformBackup);
        }

        return $returnUrl;
    }

    public function isActive(): bool
    {
        return Session::has('impersonation');
    }

    /**
     * @return array<string, mixed>
     */
    public function sessionMeta(): array
    {
        $meta = Session::get('impersonation', []);

        return is_array($meta) ? $meta : [];
    }
}
