<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\MapsFormValidationAttributes;
use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\Web\ApiClient;

#[Layout('layouts.app')]
class Categories extends Component
{
    use MapsFormValidationAttributes;

    public $items = [];
    public $pagination = [];
    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $perPage = 15;

    public $showModal = false;
    public $editingId = null;

    public $form = [
        'name' => '',
        'slug' => '',
        'parent_id' => '',
    ];

    protected $listeners = ['deleteConfirmed' => 'destroy'];

    public function mount()
    {
        $this->loadItems();

        if (request()->routeIs('categories.create')) {
            $this->openModal();
        }
    }

    public function loadItems()
    {
        $api = new ApiClient();
        $params = [
            'per_page' => $this->perPage,
            'sort' => ($this->sortDirection === 'desc' ? '-' : '') . $this->sortField,
        ];
        if ($this->search) {
            $params['search'] = $this->search;
        }
        $response = $api->get('/v1/categories', $params);
        $this->items = $response['data'] ?? [];
        $this->pagination = $response['meta']['pagination'] ?? [];
    }

    public function updatedSearch()
    {
        $this->loadItems();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->loadItems();
    }

    public function goToPage($page)
    {
        $api = new ApiClient();
        $params = [
            'page' => $page,
            'per_page' => $this->perPage,
            'sort' => ($this->sortDirection === 'desc' ? '-' : '') . $this->sortField,
        ];
        if ($this->search) {
            $params['search'] = $this->search;
        }
        $response = $api->get('/v1/categories', $params);
        $this->items = $response['data'] ?? [];
        $this->pagination = $response['meta']['pagination'] ?? [];
    }

    public function rules()
    {
        return [
            'form.name' => 'required|string|max:255',
            'form.slug' => 'nullable|string|max:255',
            'form.parent_id' => 'nullable|integer',
        ];
    }

    public function resetForm()
    {
        $this->form = [
            'name' => '',
            'slug' => '',
            'parent_id' => '',
        ];
        $this->editingId = null;
        $this->resetErrorBag();
    }

    public function openModal($id = null)
    {
        $this->resetForm();
        $this->showModal = true;
        if ($id) {
            $this->editingId = $id;
            $api = new ApiClient();
            $response = $api->get("/v1/categories/{$id}");
            $category = $response['data'] ?? $response;
            $this->form = [
                'name' => $category['name'] ?? '',
                'slug' => $category['slug'] ?? '',
                'parent_id' => $category['parent_id'] ?? '',
            ];
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function store()
    {
        $this->validate();
        $api = new ApiClient();
        $response = $api->post('/v1/categories', $this->form);
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Category created successfully.', type: 'success');
        $this->closeModal();
        $this->loadItems();
    }

    public function update()
    {
        $this->validate();
        $api = new ApiClient();
        $response = $api->put("/v1/categories/{$this->editingId}", $this->form);
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Category updated successfully.', type: 'success');
        $this->closeModal();
        $this->loadItems();
    }

    public function destroy($id)
    {
        $api = new ApiClient();
        $response = $api->delete("/v1/categories/{$id}");
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Category deleted successfully.', type: 'success');
        $this->loadItems();
    }

    public function editItem($id)
    {
        $this->openModal($id);
    }

    public function render()
    {
        return view('livewire.categories.index');
    }
}
