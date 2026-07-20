<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\Web\ApiClient;

#[Layout('layouts.app')]
class Reports extends Component
{
    public $activeTab = 'stock-valuation';

    public $stockValuation = [];
    public $lowStock = [];
    public $salesSummary = [];
    public $purchaseSummary = [];

    public $dateFrom = '';
    public $dateTo = '';
    public $warehouseId = '';

    public function mount()
    {
        $this->loadStockValuation();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        match ($tab) {
            'stock-valuation' => $this->loadStockValuation(),
            'low-stock' => $this->loadLowStock(),
            'sales-summary' => $this->loadSalesSummary(),
            'purchase-summary' => $this->loadPurchaseSummary(),
        };
    }

    public function loadStockValuation()
    {
        $api = new ApiClient();
        $params = [];
        if ($this->warehouseId) {
            $params['warehouse_id'] = $this->warehouseId;
        }
        $response = $api->get('/v1/reports/stock-valuation', $params);
        $this->stockValuation = $response['data'] ?? [];
    }

    public function loadLowStock()
    {
        $api = new ApiClient();
        $params = [];
        if ($this->warehouseId) {
            $params['warehouse_id'] = $this->warehouseId;
        }
        $response = $api->get('/v1/reports/low-stock', $params);
        $this->lowStock = $response['data'] ?? [];
    }

    public function loadSalesSummary()
    {
        $api = new ApiClient();
        $params = [];
        if ($this->dateFrom) {
            $params['date_from'] = $this->dateFrom;
        }
        if ($this->dateTo) {
            $params['date_to'] = $this->dateTo;
        }
        if ($this->warehouseId) {
            $params['warehouse_id'] = $this->warehouseId;
        }
        $response = $api->get('/v1/reports/sales-summary', $params);
        $this->salesSummary = $response['data'] ?? $response;
    }

    public function loadPurchaseSummary()
    {
        $api = new ApiClient();
        $params = [];
        if ($this->dateFrom) {
            $params['date_from'] = $this->dateFrom;
        }
        if ($this->dateTo) {
            $params['date_to'] = $this->dateTo;
        }
        if ($this->warehouseId) {
            $params['warehouse_id'] = $this->warehouseId;
        }
        $response = $api->get('/v1/reports/purchase-summary', $params);
        $this->purchaseSummary = $response['data'] ?? $response;
    }

    public function applyFilters()
    {
        match ($this->activeTab) {
            'stock-valuation' => $this->loadStockValuation(),
            'low-stock' => $this->loadLowStock(),
            'sales-summary' => $this->loadSalesSummary(),
            'purchase-summary' => $this->loadPurchaseSummary(),
        };
    }

    public function exportCsv()
    {
        $type = match ($this->activeTab) {
            'stock-valuation' => 'stock_valuation',
            'low-stock' => 'low_stock',
            'sales-summary' => 'sales_summary',
            'purchase-summary' => 'purchase_summary',
            default => null,
        };

        if ($type === null) {
            return;
        }

        $api = new ApiClient();
        $response = $api->post('/v1/reports/exports', ['type' => $type]);

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');

            return;
        }

        $this->dispatch('toast', message: 'CSV export queued. Check back shortly.', type: 'success');
    }

    public function render()
    {
        return view('livewire.reports.index');
    }
}
