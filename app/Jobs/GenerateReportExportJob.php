<?php

namespace App\Jobs;

use App\Enums\ReportExportStatus;
use App\Models\ReportExport;
use App\Services\ReportExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateReportExportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $reportExportId,
    ) {}

    public function handle(ReportExportService $reportExportService): void
    {
        $export = ReportExport::query()
            ->withoutOrganizationScope()
            ->with('organization')
            ->findOrFail($this->reportExportId);

        app()->instance('currentOrganization', $export->organization);
        setPermissionsTeamId($export->organization_id);

        $export->update(['status' => ReportExportStatus::Processing]);

        try {
            $path = $reportExportService->generateCsvFile($export);
            $export->update([
                'status' => ReportExportStatus::Completed,
                'file_path' => $path,
                'completed_at' => now(),
                'error_message' => null,
            ]);
        } catch (Throwable $exception) {
            $export->update([
                'status' => ReportExportStatus::Failed,
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            throw $exception;
        }
    }
}
