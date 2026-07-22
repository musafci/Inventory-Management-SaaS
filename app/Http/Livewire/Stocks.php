<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\EnsuresPermission;
use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\Web\ApiClient;

#[Layout('layouts.app')]
class Stocks extends Component
{
    use EnsuresPermission;

    public $items = [];
    public $pagination = [];
    public $search = '';
    public $perPage = 15;

    public function mount()
    {
        $this->ensurePermission('inventory.view');

        $this->loadItems();
    }

    public function loadItems()
    {
        $api = new ApiClient();
        $params = [
            'per_page' => $this->perPage,
        ];
        if ($this->search) {
            $params['search'] = $this->search;
        }
        $response = $api->get('/v1/stocks', $params);
        $this->items = $response['data'] ?? [];
        $this->pagination = $response['meta']['pagination'] ?? [];
    }

    public function updatedSearch()
    {
        $this->loadItems();
    }

    public function goToPage($page)
    {
        $api = new ApiClient();
        $params = [
            'page' => $page,
            'per_page' => $this->perPage,
        ];
        if ($this->search) {
            $params['search'] = $this->search;
        }
        $response = $api->get('/v1/stocks', $params);
        $this->items = $response['data'] ?? [];
        $this->pagination = $response['meta']['pagination'] ?? [];
    }

    public function render()
    {
        return view('livewire.stocks.index');
    }
}
