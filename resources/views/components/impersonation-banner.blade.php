@if(session('impersonation'))
    @php($impersonation = session('impersonation'))
    <div class="impersonation-banner border-b border-amber-300 bg-amber-50 text-sm text-amber-950 shadow-sm">
        <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-start gap-3 sm:items-center">
                <div class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-amber-200 sm:mt-0">
                    <svg class="h-4 w-4 text-amber-900" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-amber-950">Support impersonation active</p>
                    <p class="mt-0.5 text-amber-900">
                        Viewing <strong>{{ $impersonation['organization_name'] ?? 'tenant' }}</strong>
                        as <strong>{{ $impersonation['impersonated_user_name'] ?? 'user' }}</strong>
                    </p>
                    @if(!empty($impersonation['reason']))
                        <p class="mt-1 text-xs text-amber-800">{{ $impersonation['reason'] }}</p>
                    @endif
                </div>
            </div>
            <form method="POST" action="{{ route('impersonation.exit') }}" class="flex-shrink-0">
                @csrf
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-amber-900 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-amber-800 sm:w-auto">
                    Exit impersonation
                </button>
            </form>
        </div>
    </div>
@endif
