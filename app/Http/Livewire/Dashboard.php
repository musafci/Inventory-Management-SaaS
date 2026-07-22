<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\EnsuresPermission;
use App\Services\Web\ApiClient;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    use EnsuresPermission;

    public $stats = [];
    public $recentOrders = [];
    public $lowStockItems = [];

    public function mount(ApiClient $api)
    {
        $this->ensureAnyPermission([
            'inventory.view',
            'reports.view_inventory',
            'reports.view_sales',
            'reports.view_purchases',
            'orders.purchase.view',
            'orders.sales.view',
        ]);

        $this->loadData($api);
    }

    public function loadData(ApiClient $api)
    {
        try {
            $dashboard = $api->get('/v1/reports/dashboard');
            $purchaseOrders = $api->get('/v1/purchase-orders', ['per_page' => '5', 'sort' => '-created_at']);
            $salesOrders = $api->get('/v1/sales-orders', ['per_page' => '5', 'sort' => '-created_at']);
            $lowStock = $api->get('/v1/reports/low-stock');

            $this->stats = [
                'total_products' => $dashboard['data']['total_products'] ?? 0,
                'total_stock_items' => $dashboard['data']['total_stock_items'] ?? 0,
                'stock_value' => $dashboard['data']['stock_value'] ?? '0.00',
                'low_stock_count' => $dashboard['data']['low_stock_count'] ?? 0,
                'pending_purchase_orders' => $dashboard['data']['pending_purchase_orders'] ?? 0,
                'pending_sales_orders' => $dashboard['data']['pending_sales_orders'] ?? 0,
            ];

            $this->recentOrders = array_merge(
                $purchaseOrders['data'] ?? [],
                $salesOrders['data'] ?? [],
            );
            $this->recentOrders = array_slice($this->recentOrders, 0, 10);

            $this->lowStockItems = $lowStock['data'] ?? [];
        } catch (\Exception $e) {
            $this->stats = [
                'total_products' => 0,
                'total_stock_items' => 0,
                'pending_purchase_orders' => 0,
                'pending_sales_orders' => 0,
            ];
        }
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
