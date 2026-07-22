<?php

namespace App\Http\Livewire\Platform;

use App\Services\Web\PlatformApiClient;
use Livewire\Component;

class Dashboard extends Component
{
    public $stats = [
        'total' => 0,
        'active' => 0,
        'trial' => 0,
        'suspended' => 0,
    ];

    public $recentOrganizations = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public function refresh(): void
    {
        $this->loadData();
        $this->dispatch('toast', message: 'Dashboard refreshed.', type: 'success');
    }

    public function loadData(): void
    {
        $api = new PlatformApiClient();
        $response = $api->get('/organizations', ['per_page' => 100]);

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');

            return;
        }

        $organizations = $response['data'] ?? [];

        $this->stats = [
            'total' => (int) ($response['meta']['pagination']['total'] ?? count($organizations)),
            'active' => collect($organizations)->where('status', 'active')->count(),
            'trial' => collect($organizations)->where('status', 'trial')->count(),
            'suspended' => collect($organizations)->where('status', 'suspended')->count(),
        ];

        $this->recentOrganizations = collect($organizations)
            ->sortByDesc(fn (array $org): int => (int) ($org['id'] ?? 0))
            ->take(8)
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.platform.dashboard')
            ->layout('layouts.platform', [
                'heading' => 'Dashboard',
                'title' => 'Dashboard',
            ]);
    }
}
