<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Report\StoreReportExportRequest;
use App\Http\Resources\ReportExportResource;
use App\Models\ReportExport;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends ApiController
{
    public function __construct(
        protected ReportExportService $reportExportService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewReports');

        $exports = ReportExport::query()
            ->where('user_id', request()->user()->id)
            ->orderByDesc('id')
            ->paginate(request()->integer('per_page', 15));

        return $this->success(
            ReportExportResource::collection($exports->items()),
            [
                'pagination' => [
                    'current_page' => $exports->currentPage(),
                    'per_page' => $exports->perPage(),
                    'total' => $exports->total(),
                    'last_page' => $exports->lastPage(),
                ],
            ],
        );
    }

    public function store(StoreReportExportRequest $request): JsonResponse
    {
        $this->authorize('viewReports');

        $export = $this->reportExportService->queueExport(
            $request->user(),
            $request->validated('type'),
        );

        return $this->success(new ReportExportResource($export), status: 202);
    }

    public function show(int $exportId): JsonResponse
    {
        $this->authorize('viewReports');

        $export = ReportExport::query()
            ->where('user_id', request()->user()->id)
            ->findOrFail($exportId);

        return $this->success(new ReportExportResource($export));
    }

    public function download(int $exportId): StreamedResponse
    {
        $this->authorize('viewReports');

        $export = ReportExport::query()
            ->where('user_id', request()->user()->id)
            ->findOrFail($exportId);

        abort_if($export->file_path === null || ! Storage::disk('local')->exists($export->file_path), 404);

        return Storage::disk('local')->download(
            $export->file_path,
            basename($export->file_path),
            ['Content-Type' => 'text/csv'],
        );
    }
}
