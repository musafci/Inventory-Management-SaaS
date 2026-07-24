<div class="min-w-0 max-w-full">
    <x-settings-nav active="roles" />

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">Create custom roles and control what each team member can access.</p>
        @canaccess('settings.manage_roles')
            <button wire:click="openModal()" class="btn-primary shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Create Role
            </button>
        @endcanaccess
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Role</th>
                        <th class="hidden px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 md:table-cell">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Permissions</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Users</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($items as $item)
                        @php
                            $permissions = $item['permissions'] ?? [];
                            $permissionCount = count($permissions);
                        @endphp
                        <tr class="table-row-hover">
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-900">{{ $item['name'] }}</span>
                                    @if($item['is_protected'] ?? false)
                                        <span class="badge badge-warning">Protected</span>
                                    @endif
                                    @if($item['is_system'] ?? false)
                                        <span class="badge badge-info">System</span>
                                    @endif
                                </div>
                            </td>
                            <td class="hidden max-w-xs px-6 py-4 text-sm text-slate-500 md:table-cell">
                                <span class="line-clamp-2">{{ $item['description'] ?: '—' }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <span class="font-medium text-slate-900">{{ $permissionCount }}</span>
                                <span class="text-slate-500"> assigned</span>
                                @if($permissionCount > 0 && $permissionCount <= 4)
                                    <p class="mt-1 max-w-xs truncate text-xs text-slate-400" title="{{ implode(', ', $permissions) }}">
                                        {{ implode(', ', $permissions) }}
                                    </p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                <span class="badge badge-success">{{ $item['users_count'] ?? 0 }}</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                @unless($item['is_protected'] ?? false)
                                    <div class="flex items-center justify-end gap-2">
                                        <button wire:click="edit({{ $item['id'] }})" class="btn-secondary !px-3 !py-1.5 text-xs">Edit</button>
                                        <button
                                            type="button"
                                            @click="$store.confirm.open(
                                                'Delete role',
                                                @js('Delete "' . ($item['name'] ?? 'this role') . '"? Users must be reassigned before deletion.'),
                                                'danger',
                                                () => $wire.destroy({{ $item['id'] }}),
                                                'Delete'
                                            )"
                                            class="btn-danger !px-3 !py-1.5 text-xs"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">Locked</span>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">
                                No roles found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex min-h-full items-end justify-center px-4 pb-4 pt-4 sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-slate-900/50" wire:click="closeModal"></div>
                <div class="relative flex max-h-[90vh] w-full max-w-3xl flex-col rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/5">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit Role' : 'Create Role' }}</h3>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                        <div class="space-y-4">
                            <div>
                                <label class="form-label">Role name</label>
                                <input type="text" wire:model="form.name" class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Description</label>
                                <input type="text" wire:model="form.description" class="form-input">
                            </div>

                            <div>
                                <label class="form-label">Permissions</label>
                                <div class="space-y-4 rounded-xl border border-slate-200 p-4">
                                    @foreach($permissionGroups as $group => $permissions)
                                        <div>
                                            <h4 class="text-sm font-semibold text-slate-800">{{ $group }}</h4>
                                            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                                @foreach($permissions as $permission)
                                                    <label class="flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                                        <input
                                                            type="checkbox"
                                                            class="mt-0.5 shrink-0"
                                                            value="{{ $permission }}"
                                                            @checked(in_array($permission, $form['permissions'], true))
                                                            wire:click="togglePermission('{{ $permission }}')"
                                                        >
                                                        <span class="min-w-0 break-all">{{ $permission }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                        <button type="button" wire:click="closeModal" class="btn-secondary">Cancel</button>
                        <button type="button" wire:click="save" class="btn-primary">{{ $editingId ? 'Update Role' : 'Create Role' }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
