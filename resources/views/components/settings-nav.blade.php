@props([
    'active' => 'organization',
])

@if(\App\Support\OrganizationSession::canAccessSettings())
    <div class="mb-6">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Settings</h2>
            <p class="mt-1 text-sm text-slate-500">Manage your organization profile, team access, and roles.</p>
        </div>

        <nav class="mt-4 flex flex-wrap gap-1 border-b border-slate-200">
            @if(\App\Support\OrganizationSession::canManageOrganization())
                <a href="/settings/organization"
                   @class([
                       'border-b-2 px-4 py-2.5 text-sm font-medium transition-colors',
                       'border-primary-600 text-primary-700' => $active === 'organization',
                       'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' => $active !== 'organization',
                   ])>
                    Organization
                </a>
            @endif

            @if(\App\Support\OrganizationSession::canManageUsers())
                <a href="/settings/team"
                   @class([
                       'border-b-2 px-4 py-2.5 text-sm font-medium transition-colors',
                       'border-primary-600 text-primary-700' => $active === 'team',
                       'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' => $active !== 'team',
                   ])>
                    Team Members
                </a>
            @endif

            @if(\App\Support\OrganizationSession::canManageRoles())
                <a href="/settings/roles"
                   @class([
                       'border-b-2 px-4 py-2.5 text-sm font-medium transition-colors',
                       'border-primary-600 text-primary-700' => $active === 'roles',
                       'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' => $active !== 'roles',
                   ])>
                    Roles & Permissions
                </a>
            @endif
        </nav>
    </div>
@endif
