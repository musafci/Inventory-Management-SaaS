<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Web\ApiClient;
use App\Support\OrganizationSession;
use Illuminate\Http\Response;
use Illuminate\View\View;

class OrderPrintController extends Controller
{
    public function salesOrder(int $id): View|Response
    {
        abort_unless(OrganizationSession::can('orders.sales.view'), 403);

        $order = $this->fetchOrder("/v1/sales-orders/{$id}");

        return view('print.sales-order', [
            'order' => $order,
            'organization' => OrganizationSession::currentOrganization(),
            'autoPrint' => request()->boolean('print'),
        ]);
    }

    public function purchaseOrder(int $id): View|Response
    {
        abort_unless(OrganizationSession::can('orders.purchase.view'), 403);

        $order = $this->fetchOrder("/v1/purchase-orders/{$id}");

        return view('print.purchase-order', [
            'order' => $order,
            'organization' => OrganizationSession::currentOrganization(),
            'autoPrint' => request()->boolean('print'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function fetchOrder(string $endpoint): array
    {
        $api = new ApiClient();
        $response = $api->get($endpoint);

        if (isset($response['error'])) {
            abort($response['status'] ?? 404, $response['error']);
        }

        $order = $response['data'] ?? null;

        abort_if(! is_array($order), 404);

        return $order;
    }
}
