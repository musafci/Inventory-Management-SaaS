<?php

namespace App\Http\Livewire\Platform;

use App\Services\Web\PlatformApiClient;
use Livewire\Component;

class ActivityLogs extends Component
{
    public $items = [];

    public $pagination = [];

    public $summary = [];

    public $subjectTypes = [];

    public $filters = [
        'search' => '',
        'event' => '',
        'subject_type' => '',
        'organization_id' => '',
        'from' => '',
        'to' => '',
    ];

    public function mount(): void
    {
        $this->filters['organization_id'] = (string) request()->query('organization_id', '');
        $this->loadSummary();
        $this->loadItems();
    }

    public function updatedFilters(): void
    {
        $this->loadSummary();
        $this->loadItems();
    }

    public function applyFilters(): void
    {
        $this->loadSummary();
        $this->loadItems();
    }

    public function clearFilters(): void
    {
        $this->filters = [
            'search' => '',
            'event' => '',
            'subject_type' => '',
            'organization_id' => '',
            'from' => '',
            'to' => '',
        ];
        $this->loadSummary();
        $this->loadItems();
    }

    public function goToPage(int $page): void
    {
        $this->loadItems($page);
    }

    public function loadSummary(): void
    {
        $api = new PlatformApiClient();
        $query = array_filter([
            'organization_id' => $this->filters['organization_id'] !== '' ? $this->filters['organization_id'] : null,
        ]);

        $response = $api->get('/activity-logs/summary', $query);

        if (isset($response['error'])) {
            $this->summary = [];

            return;
        }

        $this->summary = $response['data'] ?? [];
    }

    public function loadItems(int $page = 1): void
    {
        $api = new PlatformApiClient();
        $query = array_filter([
            'page' => $page,
            'per_page' => 25,
            'search' => $this->filters['search'] !== '' ? $this->filters['search'] : null,
            'event' => $this->filters['event'] !== '' ? $this->filters['event'] : null,
            'subject_type' => $this->filters['subject_type'] !== '' ? $this->filters['subject_type'] : null,
            'organization_id' => $this->filters['organization_id'] !== '' ? $this->filters['organization_id'] : null,
            'from' => $this->filters['from'] !== '' ? $this->filters['from'] : null,
            'to' => $this->filters['to'] !== '' ? $this->filters['to'] : null,
        ], fn ($value): bool => $value !== null && $value !== '');

        $response = $api->get('/activity-logs', $query);

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            $this->items = [];
            $this->pagination = [];

            return;
        }

        $this->items = $response['data'] ?? [];
        $this->pagination = $response['meta']['pagination'] ?? [];
        $this->subjectTypes = $response['meta']['filters']['subject_types'] ?? [];
    }

    public function render()
    {
        return view('livewire.platform.activity-logs')
            ->layout('layouts.platform', [
                'heading' => 'Activity audit',
                'title' => 'Activity audit',
            ]);
    }
}
