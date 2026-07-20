<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Oneapp')</title>
    @vite(['resources/css/app.css'])
</head>
<body class="h-full">
<div class="flex min-h-full">
    {{-- Brand panel (desktop) --}}
    <div class="auth-panel relative hidden w-0 flex-1 flex-col justify-between overflow-hidden lg:flex lg:w-1/2 xl:w-[45%]">
        <div class="absolute inset-0 opacity-30">
            <div class="absolute -left-20 -top-20 h-72 w-72 rounded-full bg-primary-500/30 blur-3xl"></div>
            <div class="absolute -bottom-32 -right-20 h-96 w-96 rounded-full bg-indigo-400/20 blur-3xl"></div>
        </div>

        <div class="relative px-12 pt-12">
            <div class="flex items-center gap-3">
                <div class="sidebar-brand-icon flex h-11 w-11 items-center justify-center rounded-xl">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-white">Oneapp</h1>
                    <p class="text-sm text-primary-200">Inventory Management SaaS</p>
                </div>
            </div>
        </div>

        <div class="relative space-y-4 px-12 pb-16">
            <h2 class="text-3xl font-bold leading-tight text-white">
                Manage inventory<br>with confidence.
            </h2>
            <p class="max-w-md text-base leading-relaxed text-primary-100/80">
                Track stock, purchase orders, sales, and payments — all in one modern workspace built for growing teams.
            </p>

            <div class="mt-8 space-y-3">
                <div class="auth-feature">
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-emerald-500/20">
                        <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25-2.25M12 13.875V7.5" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">Real-time stock tracking</p>
                        <p class="text-xs text-slate-400">Multi-warehouse inventory with low-stock alerts</p>
                    </div>
                </div>
                <div class="auth-feature">
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-primary-500/20">
                        <svg class="h-5 w-5 text-primary-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">Purchase & sales orders</p>
                        <p class="text-xs text-slate-400">End-to-end procurement and fulfillment workflows</p>
                    </div>
                </div>
                <div class="auth-feature">
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-amber-500/20">
                        <svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">Analytics & reports</p>
                        <p class="text-xs text-slate-400">Stock valuation, sales summaries, and more</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Form panel --}}
    <div class="flex flex-1 flex-col justify-center px-6 py-12 app-bg lg:px-16 xl:px-24">
        <div class="mx-auto w-full max-w-md">
            {{-- Mobile logo --}}
            <div class="mb-8 flex justify-center lg:hidden">
                <div class="sidebar-brand-icon flex h-12 w-12 items-center justify-center rounded-xl">
                    <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </div>
            </div>

            @yield('content')
        </div>
    </div>
</div>
</body>
</html>
