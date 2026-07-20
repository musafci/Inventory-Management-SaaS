@extends('layouts.auth')

@section('title', 'Register - Oneapp')

@section('content')
    <div class="auth-card">
        <div class="mb-8 text-center">
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Create your account</h2>
            <p class="mt-2 text-sm text-slate-500">
                Already have an account?
                <a href="/login" class="font-semibold text-primary-600 hover:text-primary-500">Sign in</a>
            </p>
        </div>

        @if($errors->any())
            <div class="mb-5 rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">
                <ul class="list-inside list-disc space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="/register" class="space-y-4">
            @csrf
            <div>
                <label for="organization_name" class="form-label">Organization name</label>
                <input id="organization_name" name="organization_name" type="text" value="{{ old('organization_name') }}" required class="form-input" placeholder="Acme Corp">
            </div>
            <div>
                <label for="name" class="form-label">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required class="form-input" placeholder="Jane Doe">
            </div>
            <div>
                <label for="email" class="form-label">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="form-input" placeholder="you@example.com">
            </div>
            <div>
                <label for="phone" class="form-label">Phone <span class="text-slate-400">(optional)</span></label>
                <input id="phone" name="phone" type="text" value="{{ old('phone') }}" class="form-input" placeholder="+1 555 123 4567">
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="password" class="form-label">Password</label>
                    <input id="password" name="password" type="password" required minlength="8" class="form-input" placeholder="Min. 8 characters">
                </div>
                <div>
                    <label for="password_confirmation" class="form-label">Confirm</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required class="form-input" placeholder="Confirm password">
                </div>
            </div>
            <button type="submit" class="btn-primary w-full mt-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 3.75a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                Create account
            </button>
        </form>
    </div>
@endsection
