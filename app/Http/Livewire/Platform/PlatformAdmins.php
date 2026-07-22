<?php

namespace App\Http\Livewire\Platform;

use App\Services\Web\PlatformApiClient;
use Livewire\Component;

class PlatformAdmins extends Component
{
    public $items = [];

    public $form = [
        'name' => '',
        'email' => '',
        'password' => '',
    ];

    public function mount(): void
    {
        $this->loadItems();
    }

    public function loadItems(): void
    {
        $api = new PlatformApiClient();
        $response = $api->get('/platform-admins');

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            $this->items = [];

            return;
        }

        $this->items = $response['data'] ?? [];
    }

    public function create(): void
    {
        $this->validate([
            'form.name' => 'required|string|max:255',
            'form.email' => 'required|email',
            'form.password' => 'required|string|min:8',
        ]);

        $api = new PlatformApiClient();
        $response = $api->post('/platform-admins', $this->form);

        if (isset($response['error'])) {
            $message = $response['error'];
            if (! empty($response['errors'])) {
                $first = collect($response['errors'])->flatten()->first();
                if (is_string($first) && $first !== '') {
                    $message = $first;
                }
            }

            $this->dispatch('toast', message: $message, type: 'error');

            return;
        }

        $this->reset('form');
        $this->loadItems();
        $this->dispatch('toast', message: 'Platform admin created.', type: 'success');
    }

    public function delete(int $adminId): void
    {
        $api = new PlatformApiClient();
        $response = $api->delete("/platform-admins/{$adminId}");

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');

            return;
        }

        $this->loadItems();
        $this->dispatch('toast', message: 'Platform admin removed.', type: 'success');
    }

    public function render()
    {
        return view('livewire.platform.admins')
            ->layout('layouts.platform', [
                'heading' => 'Platform Admins',
                'title' => 'Platform Admins',
            ]);
    }
}
