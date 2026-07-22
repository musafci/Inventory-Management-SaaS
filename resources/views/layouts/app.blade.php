<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Inventory Management')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full app-bg" x-data="{ mobileMenu: false, userName: @js(session('user_name', '')), userEmail: @js(session('user_email', '')) }" x-on:toast.window="$store.toast.add($event.detail.message, $event.detail.type || 'success')">
    <div id="livewire-loading-bar" class="loading-bar" style="display:none"></div>
    <div class="min-h-full">
        {{-- Mobile sidebar --}}
        <aside class="sidebar-panel sidebar-transition fixed inset-y-0 left-0 z-50 flex w-72 flex-col lg:hidden"
               :class="mobileMenu ? 'translate-x-0' : '-translate-x-full'"
               x-show="mobileMenu" x-cloak>
            <div class="flex grow flex-col gap-y-2 overflow-y-auto px-5 pb-4">
                @include('components.sidebar-nav')
            </div>
        </aside>

        {{-- Desktop sidebar --}}
        <aside class="sidebar-panel fixed inset-y-0 left-0 z-50 hidden w-72 flex-col lg:flex">
            <div class="flex grow flex-col gap-y-2 overflow-y-auto px-5 pb-4">
                @include('components.sidebar-nav')
            </div>
        </aside>

        {{-- Mobile overlay --}}
        <div class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden" x-show="mobileMenu" x-cloak
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="mobileMenu = false"></div>

        {{-- Main content --}}
        <div class="lg:pl-72 flex flex-col min-h-full">
            <header class="app-header">
                <button type="button" class="-m-2.5 rounded-xl p-2.5 text-slate-600 transition-colors hover:bg-slate-100 lg:hidden" @click="mobileMenu = !mobileMenu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                    <div class="flex items-center">
                        <h1 class="text-lg font-bold tracking-tight text-slate-900">@yield('page-title', 'Dashboard')</h1>
                    </div>
                    <div class="flex flex-1"></div>
                    <div class="flex items-center gap-x-3 lg:gap-x-4">
                        <div class="relative" x-data="orgSelector(@js(session('organizations', [])), @js(session('organization_id')))" x-init="init()">
                            <button type="button" class="org-selector-btn" @click="open = !open">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                                <span x-text="currentName" class="hidden sm:inline max-w-[140px] truncate"></span>
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                            </button>
                            <div x-show="open" x-cloak @click.away="open = false" x-transition class="dropdown-panel">
                                <template x-for="org in organizations" :key="org.id">
                                    <button @click="switchOrg(org)" class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition-colors hover:bg-slate-50"
                                            :class="org.id == currentId ? 'bg-primary-50 text-primary-700 font-semibold' : ''">
                                        <span x-text="org.name" class="truncate"></span>
                                        <span x-show="org.id == currentId" class="ml-auto text-primary-600">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="relative" x-data="{ open: false }">
                            <button type="button" class="flex items-center gap-x-2 rounded-xl px-2 py-1.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100" @click="open = !open">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-primary-500 to-primary-700 text-sm font-bold text-white shadow-sm" x-text="userName.charAt(0).toUpperCase()"></span>
                                <span class="hidden sm:inline" x-text="userName"></span>
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                            </button>
                            <div x-show="open" x-cloak @click.away="open = false" x-transition class="dropdown-panel w-52">
                                <div class="border-b border-slate-100 px-4 py-3">
                                    <p class="text-sm font-semibold text-slate-900" x-text="userName"></p>
                                    <p class="text-xs text-slate-500" x-text="userEmail"></p>
                                </div>
                                <form method="POST" action="{{ route('logout') }}" x-data>
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-red-600 transition-colors hover:bg-red-50">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
                                        Sign out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 py-6 px-4 sm:px-6 lg:px-8">
                @if(session('success'))
                    <div class="mb-5 flex items-center gap-3 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)">
                        <svg class="h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-5 flex items-center gap-3 rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">
                        <svg class="h-5 w-5 flex-shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                        {{ session('error') }}
                    </div>
                @endif
                @yield('content', $slot ?? '')
            </main>
        </div>
    </div>

    {{-- Toast notifications --}}
    <div x-data class="fixed inset-0 z-[100] pointer-events-none">
        <div class="flex flex-col gap-2 p-4 sm:p-6 items-end">
            <template x-for="toast in $store.toast.toasts" :key="toast.id">
                <div class="toast-item toast-enter">
                    <template x-if="toast.type === 'success'">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 flex-shrink-0">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </div>
                    </template>
                    <template x-if="toast.type === 'error'">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 flex-shrink-0">
                            <svg class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </div>
                    </template>
                    <div class="flex-1 pt-0.5">
                        <p class="text-sm font-medium text-slate-900" x-text="toast.message"></p>
                    </div>
                    <button @click="$store.toast.remove(toast.id)" class="text-slate-400 hover:text-slate-600 flex-shrink-0 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </template>
        </div>
    </div>

    {{-- Confirm dialog --}}
    <div x-data x-show="$store.confirm.show" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="$store.confirm.deny()"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-900/5"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             @click.away="$store.confirm.deny()" @keydown.escape.window="$store.confirm.deny()">
            <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full"
                     :class="$store.confirm.type === 'danger' ? 'bg-red-100' : ($store.confirm.type === 'warning' ? 'bg-amber-100' : 'bg-sky-100')">
                    <template x-if="$store.confirm.type === 'danger'">
                        <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    </template>
                    <template x-if="$store.confirm.type === 'warning'">
                        <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                    </template>
                    <template x-if="$store.confirm.type === 'info'">
                        <svg class="h-5 w-5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                    </template>
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-semibold text-slate-900" x-text="$store.confirm.title"></h3>
                    <p class="mt-2 text-sm text-slate-600" x-text="$store.confirm.message"></p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="$store.confirm.deny()" class="btn-secondary"
                        x-text="$store.confirm.denyLabel"></button>
                <button type="button" @click="$store.confirm.confirm()" :class="$store.confirm.type === 'danger' ? 'btn-danger' : ($store.confirm.type === 'warning' ? 'btn-warning' : 'btn-primary')"
                        x-text="$store.confirm.confirmLabel"></button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('orgSelector', (orgs = [], currentOrgId = null) => ({
                organizations: orgs,
                currentId: currentOrgId,
                open: false,
                get currentName() {
                    const org = this.organizations.find(o => o.id == this.currentId);
                    return org ? org.name : 'Organization';
                },
                init() {},
                switchOrg(org) {
                    this.currentId = org.id;
                    this.open = false;
                    fetch('{{ route('organization.switch') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        },
                        body: JSON.stringify({ organization_id: org.id }),
                    }).then((response) => {
                        if (response.ok) {
                            window.location.href = '/dashboard';
                        }
                    });
                },
            }));
        });
    </script>
    @stack('scripts')
    @livewireScripts
</body>
</html>
