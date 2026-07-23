<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Supplier;
use App\Support\CsvReader;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class SupplierImportService
{
    private const REQUIRED_COLUMNS = [
        'name',
    ];

    public function __construct(
        protected SupplierService $supplierService,
    ) {}

    /**
     * @return array{imported: int, failed: int, errors: list<array{row: int, messages: list<string>}>}
     */
    public function import(Organization $organization, string $csv): array
    {
        $parsed = CsvReader::parse($csv);
        $this->assertRequiredColumns($parsed['headers']);

        $existingNames = Supplier::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->pluck('name')
            ->map(fn (string $name): string => strtolower($name))
            ->all();

        $seenNames = [];
        $imported = 0;
        $errors = [];

        foreach ($parsed['rows'] as $index => $row) {
            $rowNumber = $index + 2;
            $prepared = $this->prepareRow(
                $organization,
                $row,
                $existingNames,
                $seenNames,
            );

            if ($prepared['errors'] !== []) {
                $errors[] = ['row' => $rowNumber, 'messages' => $prepared['errors']];

                continue;
            }

            $this->supplierService->create($prepared['data']);
            $imported++;
            $nameKey = strtolower($prepared['data']['name']);
            $existingNames[] = $nameKey;
            $seenNames[] = $nameKey;
        }

        return [
            'imported' => $imported,
            'failed' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * @param  list<string>  $headers
     */
    protected function assertRequiredColumns(array $headers): void
    {
        $missing = array_values(array_diff(self::REQUIRED_COLUMNS, $headers));

        if ($missing !== []) {
            throw new InvalidArgumentException(
                'Missing required CSV columns: '.implode(', ', $missing).'.',
            );
        }
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  list<string>  $existingNames
     * @param  list<string>  $seenNames
     * @return array{data: array<string, mixed>, errors: list<string>}
     */
    protected function prepareRow(
        Organization $organization,
        array $row,
        array $existingNames,
        array $seenNames,
    ): array {
        $payload = [
            'name' => $this->nullableString($row['name'] ?? null),
            'contact_person' => $this->nullableString($row['contact_person'] ?? null),
            'email' => $this->nullableString($row['email'] ?? null),
            'phone' => $this->nullableString($row['phone'] ?? null),
            'address' => $this->nullableString($row['address'] ?? null),
        ];

        $messages = [];
        $nameKey = $payload['name'] !== null ? strtolower($payload['name']) : null;

        if ($nameKey !== null && (in_array($nameKey, $seenNames, true) || in_array($nameKey, $existingNames, true))) {
            $messages[] = 'Supplier name already exists.';
        }

        $validator = Validator::make($payload, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers', 'name')->where('organization_id', $organization->id),
            ],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            $messages = array_merge($messages, array_values($validator->errors()->all()));
        }

        if ($messages !== []) {
            return [
                'data' => [],
                'errors' => array_values(array_unique($messages)),
            ];
        }

        return [
            'data' => $payload,
            'errors' => [],
        ];
    }

    protected function nullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
