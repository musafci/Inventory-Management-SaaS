<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Services\Web\WebSessionService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected WebSessionService $webSession,
    ) {}

    public function showLogin()
    {
        if (session()->has('auth_token')) {
            return redirect('/dashboard');
        }
        return view('auth.login');
    }

    public function showRegister()
    {
        if (session()->has('auth_token')) {
            return redirect('/dashboard');
        }
        return view('auth.register');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $result = $this->authService->login(
                $request->email,
                $request->password,
            );
        } catch (\Exception $e) {
            return back()->withErrors(['email' => $e->getMessage()])->withInput($request->only('email'));
        }

        $organizations = $result['organizations']->toArray();

        $this->webSession->storeAuthSession(
            $result['token'],
            $result['user']->toArray(),
            $organizations,
        );

        session(['organization_id' => $this->authService->resolvePreferredOrganizationId($result['user'])]);

        return $this->redirectAfterAuth();
    }

    public function register(Request $request)
    {
        $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $result = $this->authService->register($request->only([
                'organization_name', 'name', 'email', 'phone', 'password', 'password_confirmation',
            ]));
        } catch (\Exception $e) {
            return back()->withErrors(['email' => $e->getMessage()])->withInput();
        }

        $organizations = $result['organizations']->toArray();

        $this->webSession->storeAuthSession(
            $result['token'],
            $result['user']->toArray(),
            $organizations,
        );

        session(['organization_id' => $this->authService->resolvePreferredOrganizationId($result['user'])]);

        return $this->redirectAfterAuth();
    }

    public function logout()
    {
        if ($token = session('auth_token')) {
            $this->authService->revokeAccessToken($token);
        }

        $this->webSession->clearAuthSession();

        return redirect('/login');
    }

    public function switchOrganization(Request $request)
    {
        $request->validate([
            'organization_id' => ['required', 'integer'],
        ]);

        if (! $this->webSession->setActiveOrganization($request->integer('organization_id'))) {
            abort(403, 'You do not belong to that organization.');
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Organization switched.']);
        }

        return redirect()->back()->with('success', 'Organization switched.');
    }

    /**
     * Always redirect within the current host:port (e.g. localhost:8000).
     */
    protected function redirectAfterAuth()
    {
        session()->forget('url.intended');

        return redirect('/dashboard');
    }
}
