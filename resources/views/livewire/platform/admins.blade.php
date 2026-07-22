<div>
    <div class="page-banner mb-8">
        <h2 class="text-2xl font-bold sm:text-3xl">Platform admins</h2>
        <p class="mt-2 text-sm text-primary-100/80">Bootstrap and manage super-admin accounts. No public registration.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900">Create admin</h3>
            <form wire:submit.prevent="create" class="mt-5 space-y-4">
                <div>
                    <label class="form-label">Name</label>
                    <input type="text" wire:model="form.name" class="form-input">
                    @error('form.name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" wire:model="form.email" class="form-input">
                    @error('form.email') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Password</label>
                    <input type="password" wire:model="form.password" class="form-input">
                    @error('form.password') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">Create admin</button>
            </form>
        </div>

        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Existing admins</h3>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($items as $admin)
                    <div class="flex items-center justify-between gap-4 px-6 py-4" wire:key="admin-{{ $admin['id'] }}">
                        <div>
                            <p class="font-medium text-slate-900">{{ $admin['name'] }}</p>
                            <p class="text-sm text-slate-500">{{ $admin['email'] }}</p>
                        </div>
                        <button type="button"
                                class="text-sm font-semibold text-red-600 hover:text-red-500"
                                @click="$store.confirm.open('Remove admin', @js('Remove ' . ($admin['email'] ?? 'this admin') . '?'), 'danger', () => $wire.delete({{ $admin['id'] }}), 'Remove')">
                            Remove
                        </button>
                    </div>
                @empty
                    <p class="px-6 py-8 text-sm text-slate-500">No platform admins found.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
