<?php

namespace App\Jobs;

use App\Mail\OrganizationDataExportReadyMail;
use App\Models\Category;
use App\Models\Customer;
use App\Models\OrganizationDataExport;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class ProcessOrganizationDataExportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $exportId,
    ) {}

    public function handle(): void
    {
        $export = OrganizationDataExport::query()
            ->withoutOrganizationScope()
            ->with(['organization', 'user'])
            ->find($this->exportId);

        if ($export === null) {
            return;
        }

        try {
            $organizationId = $export->organization_id;
            $payload = [
                'organization' => $export->organization->only([
                    'id', 'name', 'slug', 'email', 'phone', 'plan', 'status', 'trial_ends_at', 'created_at',
                ]),
                'users' => User::query()
                    ->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organizationId))
                    ->get(['id', 'name', 'email', 'phone', 'status', 'created_at']),
                'products' => Product::query()->where('organization_id', $organizationId)->get(),
                'categories' => Category::query()->where('organization_id', $organizationId)->get(),
                'warehouses' => Warehouse::query()->where('organization_id', $organizationId)->get(),
                'suppliers' => Supplier::query()->where('organization_id', $organizationId)->get(),
                'customers' => Customer::query()->where('organization_id', $organizationId)->get(),
                'stocks' => Stock::query()->where('organization_id', $organizationId)->get(),
                'stock_movements' => StockMovement::query()->where('organization_id', $organizationId)->get(),
                'purchase_orders' => PurchaseOrder::query()
                    ->where('organization_id', $organizationId)
                    ->with('items')
                    ->get(),
                'sales_orders' => SalesOrder::query()
                    ->where('organization_id', $organizationId)
                    ->with('items')
                    ->get(),
                'payments' => Payment::query()->where('organization_id', $organizationId)->get(),
            ];

            $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
            $path = "exports/organization-{$organizationId}-{$export->id}.json";
            Storage::disk('local')->put($path, $json);

            $export->forceFill([
                'status' => 'completed',
                'file_path' => $path,
                'completed_at' => now(),
            ])->save();

            $downloadUrl = URL::temporarySignedRoute(
                'organization-export.download',
                now()->addDay(),
                ['export' => $export->id],
            );

            Mail::to($export->user->email)->send(new OrganizationDataExportReadyMail(
                $export->organization->name,
                $downloadUrl,
            ));
        } catch (\Throwable $exception) {
            $export->forceFill([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ])->save();

            throw $exception instanceof RuntimeException
                ? $exception
                : new RuntimeException($exception->getMessage(), 0, $exception);
        }
    }
}
