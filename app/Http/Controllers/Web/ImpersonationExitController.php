<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Web\ImpersonationWebService;
use Illuminate\Http\RedirectResponse;

class ImpersonationExitController extends Controller
{
    public function __construct(
        protected ImpersonationWebService $impersonationWeb,
    ) {}

    public function __invoke(): RedirectResponse
    {
        if (! $this->impersonationWeb->isActive()) {
            return redirect()->route('dashboard');
        }

        $returnUrl = $this->impersonationWeb->exit();

        return redirect($returnUrl)->with('success', 'Impersonation ended. You are back in the platform portal.');
    }
}
