<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Inventory Management')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
        <div class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden" x-show="mobileMenu" x-cloak
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="mobileMenu = false"></div>

        {{-- Main content --}}
        <div class="lg:pl-72 flex flex-col min-h-full">
            @include('components.impersonation-banner')
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
                    <div class="flex items-center gap-x-2 lg:gap-x-3">
                        {{-- Command palette trigger --}}
                        <button type="button" @click="$store.commandPalette.open()" class="hidden sm:flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-400 shadow-sm transition-all hover:border-slate-300 hover:text-slate-500 hover:shadow">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                            <span class="hidden lg:inline">Search...</span>
                            <span class="hidden lg:inline-flex items-center gap-0.5">
                                <span class="kbd">&#8984;</span><span class="kbd">K</span>
                            </span>
                        </button>

                        @unless(session('impersonation'))
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
                        @endunless

                        <div class="relative" x-data="{ open: false }">
                            <button type="button" class="flex items-center gap-x-2 rounded-xl px-2 py-1.5 text-sm font-medium text-slate-700 transition-all duration-200 hover:bg-slate-100 hover:shadow-sm" @click="open = !open">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-primary-500 to-primary-700 text-sm font-bold text-white shadow-sm shadow-primary-500/25" x-text="userName.charAt(0).toUpperCase()"></span>
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

            <main class="flex-1 py-6 px-4 sm:px-6 lg:px-8 page-enter">
                @if(session('success'))
                    <div class="mb-5 flex items-center gap-3 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 flex-shrink-0">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </div>
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-5 flex items-center gap-3 rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 flex-shrink-0">
                            <svg class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        </div>
                        {{ session('error') }}
                    </div>
                @endif
                @yield('content', $slot ?? '')
            </main>
        </div>
    </div>

    {{-- Toast notifications --}}
    <div x-data class="fixed inset-0 z-[100] pointer-events-none">
        <div class="flex flex-col gap-3 p-4 sm:p-6 items-end">
            <template x-for="toast in $store.toast.toasts" :key="toast.id">
                <div class="toast-item toast-enter pointer-events-auto">
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
                    <template x-if="toast.type === 'warning'">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 flex-shrink-0">
                            <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        </div>
                    </template>
                    <div class="flex-1 pt-0.5">
                        <p class="text-sm font-medium text-slate-900" x-text="toast.message"></p>
                    </div>
                    <button @click="$store.toast.remove(toast.id)" class="text-slate-400 hover:text-slate-600 flex-shrink-0 transition-colors rounded-lg p-1 hover:bg-slate-100">
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
        <div class="fixed inset-0 bg-slate-900/50" @click="$store.confirm.deny()"></div>
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

    {{-- Command Palette --}}
    <div x-data="commandPalette()" x-show="$store.commandPalette.show" x-cloak @keydown.escape.window="$store.commandPalette.close()" @keydown.meta.k.window="$store.commandPalette.toggle()" @keydown.ctrl.k.window="$store.commandPalette.toggle()">
        <div class="command-backdrop" @click="$store.commandPalette.close()"></div>
        <div class="command-panel" @click.away="$store.commandPalette.close()">
            <div class="flex items-center gap-3 border-b border-slate-100 px-4">
                <svg class="h-5 w-5 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                <input type="text" x-ref="commandInput" x-model="query" @input="search()" placeholder="Type a command or search..." class="command-input" autocomplete="off">
                <span class="kbd text-[10px]">ESC</span>
            </div>
            <div class="max-h-80 overflow-y-auto py-2">
                <template x-if="query === ''">
                    <div>
                        <div class="command-group-label">Navigation</div>
                        <template x-for="item in allItems" :key="item.href">
                            <a :href="item.href" class="command-item" @click="$store.commandPalette.close()">
                                <span x-html="item.icon" class="h-4 w-4 flex-shrink-0 text-slate-400"></span>
                                <span x-text="item.label"></span>
                                <span class="kbd ml-auto" x-text="item.shortcut || ''" x-show="item.shortcut"></span>
                            </a>
                        </template>
                    </div>
                </template>
                <template x-if="query !== '' && filteredItems.length > 0">
                    <div>
                        <div class="command-group-label">Results</div>
                        <template x-for="item in filteredItems" :key="item.href">
                            <a :href="item.href" class="command-item" @click="$store.commandPalette.close()">
                                <span x-html="item.icon" class="h-4 w-4 flex-shrink-0 text-slate-400"></span>
                                <span x-text="item.label"></span>
                            </a>
                        </template>
                    </div>
                </template>
                <template x-if="query !== '' && filteredItems.length === 0">
                    <div class="px-4 py-8 text-center text-sm text-slate-500">
                        No results found for "<span x-text="query"></span>"
                    </div>
                </template>
            </div>
            <div class="border-t border-slate-100 px-4 py-2.5 flex items-center gap-4 text-[11px] text-slate-400">
                <span class="flex items-center gap-1"><span class="kbd">&uarr;</span><span class="kbd">&darr;</span> Navigate</span>
                <span class="flex items-center gap-1"><span class="kbd">&crarr;</span> Open</span>
                <span class="flex items-center gap-1"><span class="kbd">ESC</span> Close</span>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('commandPalette', () => ({
                query: '',
                allItems: [
                    { label: 'Dashboard', href: '/dashboard', icon: '<svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>' },
                    { label: 'Products', href: '/products', icon: '<svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /></svg>' },
                    { label: 'Categories', href: '/categories', icon: '<svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>' },
                    { label: 'Units', href: '/units', icon: '<svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6z" /></svg>' },
                    { label: 'Warehouses', href: '/warehouses', icon: '<svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" /></svg>' },
                    { label: 'Stock Levels', href: '/stocks', icon: '<svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>' },
                    { label: 'Stock Movements', href: '/stock-movements', icon: '<svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>' },
                    { label: 'Suppliers', href: '/suppliers', icon: '<svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>' },
                    { label: 'Purchase Orders', href: '/purchase-orders', icon: '<svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>' },
                    { label: 'Customers', href: '/customers', icon: '<svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>' },
                    { label: 'Sales Orders', href: '/sales-orders', icon: '<svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121 0 2.09-.773 2.34-1.872l1.836-8.046A1.125 1.125 0 0018.054 3H5.106" /></svg>' },
                    { label: 'Payments', href: '/payments', icon: '<svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>' },
                    { label: 'Reports', href: '/reports', icon: '<svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>' },
                    { label: 'Settings', href: '/settings', icon: '<svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>' },
                ],
                filteredItems: [],
                init() {
                    this.$watch('$store.commandPalette.show', (val) => {
                        if (val) {
                            this.query = '';
                            this.filteredItems = this.allItems;
                            this.$nextTick(() => this.$refs.commandInput?.focus());
                        }
                    });
                },
                search() {
                    const q = this.query.toLowerCase();
                    this.filteredItems = q ? this.allItems.filter(i => i.label.toLowerCase().includes(q)) : this.allItems;
                },
            }));
        });

        document.addEventListener('livewire:load', () => {
            const bar = document.getElementById('livewire-loading-bar');
            Livewire.hook('message.sent', () => { bar.style.display = 'block'; });
            Livewire.hook('message.received', () => { bar.style.display = 'none'; });
            Livewire.hook('request.failed', () => { bar.style.display = 'none'; });
        });
    </script>
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
