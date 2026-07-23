<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StartImpersonationRequest;
use App\Models\Organization;
use App\Models\PlatformAdmin;
use App\Models\User;
use App\Services\Web\ImpersonationWebService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class PlatformImpersonationController extends Controller
{
    public function __construct(
        protected ImpersonationWebService $impersonationWeb,
    ) {}

    public function start(StartImpersonationRequest $request, int $organizationId): RedirectResponse
    {
        $admin = PlatformAdmin::query()->findOrFail((int) session('platform_admin_id'));
        $organization = Organization::query()->findOrFail($organizationId);
        $user = User::query()->findOrFail($request->validated('user_id'));

        try {
            $this->impersonationWeb->start(
                $admin,
                $organization,
                $user,
                $request->validated('reason'),
            );
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors())
                ->withInput();
        }

        return redirect()
            ->route('dashboard')
            ->with('success', "You are now viewing {$organization->name} as {$user->name}.");
    }
}
