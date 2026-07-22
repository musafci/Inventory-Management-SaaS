<?php

namespace App\Http\Livewire\Platform;

use App\Services\Web\PlatformApiClient;
use Livewire\Component;

class Organizations extends Component
{
    public $items = [];

    public $pagination = [];

    public $search = '';

    public $statusFilter = '';

    public $perPage = 15;

    public function mount(): void
    {
        $this->statusFilter = (string) request()->query('status', '');
        $this->loadItems();
    }

    public function updatedSearch(): void
    {
        $this->loadItems();
    }

    public function updatedStatusFilter(): void
    {
        $this->loadItems();
    }

    public function goToPage(int $page): void
    {
        $this->loadItems($page);
    }

    public function loadItems(int $page = 1): void
    {
        $api = new PlatformApiClient();
        $response = $api->get('/organizations', [
            'per_page' => $this->perPage,
            'page' => $page,
        ]);

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            $this->items = [];
            $this->pagination = [];

            return;
        }

        $items = $response['data'] ?? [];

        if ($this->search !== '') {
            $needle = strtolower($this->search);
            $items = array_values(array_filter($items, function (array $org) use ($needle): bool {
                return str_contains(strtolower($org['name'] ?? ''), $needle)
                    || str_contains(strtolower($org['email'] ?? ''), $needle)
                    || str_contains(strtolower($org['slug'] ?? ''), $needle);
            }));
        }

        if ($this->statusFilter !== '') {
            $items = array_values(array_filter(
                $items,
                fn (array $org): bool => ($org['status'] ?? '') === $this->statusFilter,
            ));
        }

        $this->items = $items;
        $this->pagination = $response['meta']['pagination'] ?? [];
    }

    public function render()
    {
        return view('livewire.platform.organizations')
            ->layout('layouts.platform', [
                'heading' => 'Organizations',
                'title' => 'Organizations',
            ]);
    }
}
