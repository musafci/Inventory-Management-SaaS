<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\Web\ApiClient;

#[Layout('layouts.app')]
class Stocks extends Component
{
    public $items = [];
    public $pagination = [];
    public $search = '';
    public $perPage = 15;

    public function mount()
    {
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
