<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\HasApiDetailView;
use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\Web\ApiClient;

#[Layout('layouts.app')]
class Payments extends Component
{
    use HasApiDetailView;
    public $items = [];
    public $pagination = [];
    public $search = '';
    public $perPage = 15;

    public function mount()
    {
        $this->mountDetailRoute('payments.show', '/v1/payments', '/payments');

        if (! $this->isDetailView()) {
            $this->loadItems();
        }
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
        $response = $api->get('/v1/payments', $params);
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
        $response = $api->get('/v1/payments', $params);
        $this->items = $response['data'] ?? [];
        $this->pagination = $response['meta']['pagination'] ?? [];
    }

    public function render()
    {
        return view('livewire.payments.index');
    }
}
