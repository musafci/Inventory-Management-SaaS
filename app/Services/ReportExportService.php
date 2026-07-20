<?php

namespace App\Services;

use App\Enums\ReportExportStatus;
use App\Jobs\GenerateReportExportJob;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReportExportService
{
    public function __construct(
        protected ReportService $reportService,
    ) {}

    public function queueExport(User $user, string $type, array $filters = []): ReportExport
    {
        $allowedTypes = ['stock_valuation', 'low_stock', 'sales_summary', 'purchase_summary'];

        if (! in_array($type, $allowedTypes, true)) {
            throw ValidationException::withMessages([
                'type' => ['Invalid report export type.'],
            ]);
        }

        $export = ReportExport::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'status' => ReportExportStatus::Pending,
            'file_path' => null,
        ]);

        GenerateReportExportJob::dispatch($export->id);

        return $export;
    }

    public function generateCsvFile(ReportExport $export): string
    {
        $organizationId = $export->organization_id;
        $filename = sprintf(
            'reports/org-%d/export-%d-%s.csv',
            $organizationId,
            $export->id,
            $export->type,
        );

        $rows = match ($export->type) {
            'stock_valuation' => $this->stockValuationRows(),
            'low_stock' => $this->lowStockRows(),
            'sales_summary' => $this->salesSummaryRows(),
            'purchase_summary' => $this->purchaseSummaryRows(),
            default => throw ValidationException::withMessages([
                'type' => ['Unsupported report export type.'],
            ]),
        };

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new \RuntimeException('Unable to create CSV buffer.');
        }

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $contents = stream_get_contents($handle) ?: '';
        fclose($handle);

        Storage::disk('local')->put($filename, $contents);

        return $filename;
    }

    /**
     * @return list<list<string|int|float|null>>
     */
    protected function stockValuationRows(): array
    {
        $data = $this->reportService->stockValuation();
        $rows = [['warehouse_id', 'warehouse_name', 'total_value', 'total_units']];

        foreach ($data['by_warehouse'] as $row) {
            $rows[] = [
                $row['warehouse_id'],
                $row['warehouse_name'],
                $row['total_value'],
                $row['total_units'],
            ];
        }

        $rows[] = ['', 'TOTAL', $data['total_value'], $data['total_units']];

        return $rows;
    }

    /**
     * @return list<list<string|int|float|null>>
     */
    protected function lowStockRows(): array
    {
        $rows = [['stock_id', 'warehouse', 'product', 'sku', 'on_hand', 'reserved', 'available', 'reorder_point']];

        foreach ($this->reportService->lowStock() as $item) {
            $rows[] = [
                $item['stock_id'],
                $item['warehouse_name'],
                $item['product_name'],
                $item['sku'],
                $item['quantity_on_hand'],
                $item['quantity_reserved'],
                $item['quantity_available'],
                $item['reorder_point'],
            ];
        }

        return $rows;
    }

    /**
     * @return list<list<string|int|float|null>>
     */
    protected function salesSummaryRows(): array
    {
        $data = $this->reportService->salesSummary();
        $rows = [['status', 'order_count', 'total_amount']];

        foreach ($data['by_status'] as $row) {
            $rows[] = [$row['status'], $row['order_count'], $row['total_amount']];
        }

        $rows[] = ['TOTAL', $data['order_count'], $data['total_amount']];

        return $rows;
    }

    /**
     * @return list<list<string|int|float|null>>
     */
    protected function purchaseSummaryRows(): array
    {
        $data = $this->reportService->purchaseSummary();
        $rows = [['status', 'order_count', 'total_amount']];

        foreach ($data['by_status'] as $row) {
            $rows[] = [$row['status'], $row['order_count'], $row['total_amount']];
        }

        $rows[] = ['TOTAL', $data['order_count'], $data['total_amount']];

        return $rows;
    }
}
