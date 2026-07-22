<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\OrganizationDataExportResource;
use App\Models\OrganizationDataExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrganizationDataExportController extends ApiController
{
    public function store(Request $request): JsonResponse
    {
        /** @var \App\Models\Organization $organization */
        $organization = app('currentOrganization');

        $this->authorize('exportData', $organization);

        $export = app(\App\Services\OrganizationDataExportService::class)
            ->queueExport($organization, $request->user());

        return $this->success(new OrganizationDataExportResource($export), status: 202);
    }

    public function download(Request $request, int $export): StreamedResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $exportModel = OrganizationDataExport::query()->findOrFail($export);

        abort_if(
            $exportModel->file_path === null || ! Storage::disk('local')->exists($exportModel->file_path),
            404,
        );

        return Storage::disk('local')->download(
            $exportModel->file_path,
            basename($exportModel->file_path),
            ['Content-Type' => 'application/json'],
        );
    }
}
