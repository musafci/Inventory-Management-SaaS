<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\PlatformAuthService;
use App\Services\Web\PlatformApiClient;
use App\Services\Web\PlatformSessionService;
use Illuminate\Http\Request;

class PlatformAuthController extends Controller
{
    public function __construct(
        protected PlatformAuthService $platformAuthService,
        protected PlatformSessionService $platformSession,
    ) {}

    public function showLogin()
    {
        if ($this->platformSession->hasAuthToken()) {
            return redirect()->route('platform.dashboard');
        }

        return view('platform.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $result = $this->platformAuthService->login(
                $request->email,
                $request->password,
            );
        } catch (\Exception $e) {
            return back()->withErrors(['email' => $e->getMessage()])->withInput($request->only('email'));
        }

        $this->platformSession->storeAuthSession(
            $result['token'],
            $result['admin']->toArray(),
        );

        return redirect()->route('platform.dashboard');
    }

    public function logout(Request $request)
    {
        if ($this->platformSession->hasAuthToken()) {
            $api = new PlatformApiClient();
            $api->post('/auth/logout');
        }

        $this->platformSession->clearAuthSession();

        return redirect()->route('platform.login');
    }
}
