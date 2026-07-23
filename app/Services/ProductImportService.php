<?php

namespace App\Services;

use App\Exceptions\PlanLimitExceededException;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Unit;
use App\Support\CsvReader;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class ProductImportService
{
    private const REQUIRED_COLUMNS = [
        'name',
        'sku',
        'category',
        'unit',
        'cost_price',
        'selling_price',
    ];

    public function __construct(
        protected ProductService $productService,
    ) {}

    /**
     * @return array{imported: int, failed: int, errors: list<array{row: int, messages: list<string>}>}
     */
    public function import(Organization $organization, string $csv): array
    {
        $parsed = CsvReader::parse($csv);
        $this->assertRequiredColumns($parsed['headers']);

        $categories = Category::query()
            ->where('organization_id', $organization->id)
            ->get(['id', 'name'])
            ->keyBy(fn (Category $category): string => strtolower($category->name));

        $units = Unit::query()
            ->where('organization_id', $organization->id)
            ->get(['id', 'name', 'symbol'])
            ->flatMap(function (Unit $unit): array {
                return [
                    strtolower($unit->name) => $unit->id,
                    strtolower($unit->symbol) => $unit->id,
                ];
            });

        $existingSkus = Product::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->pluck('sku')
            ->map(fn (string $sku): string => strtolower($sku))
            ->all();

        $seenSkus = [];
        $imported = 0;
        $errors = [];

        foreach ($parsed['rows'] as $index => $row) {
            $rowNumber = $index + 2;
            $prepared = $this->prepareRow(
                $organization,
                $row,
                $categories,
                $units,
                $existingSkus,
                $seenSkus,
            );

            if ($prepared['errors'] !== []) {
                $errors[] = ['row' => $rowNumber, 'messages' => $prepared['errors']];

                continue;
            }

            try {
                $this->productService->create($prepared['data']);
                $imported++;
                $existingSkus[] = strtolower($prepared['data']['sku']);
                $seenSkus[] = strtolower($prepared['data']['sku']);
            } catch (PlanLimitExceededException $exception) {
                $errors[] = ['row' => $rowNumber, 'messages' => [$exception->getMessage()]];
            }
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
     * @param  \Illuminate\Support\Collection<string, Category>  $categories
     * @param  \Illuminate\Support\Collection<string, int>  $units
     * @param  list<string>  $existingSkus
     * @param  list<string>  $seenSkus
     * @return array{data: array<string, mixed>, errors: list<string>}
     */
    protected function prepareRow(
        Organization $organization,
        array $row,
        $categories,
        $units,
        array $existingSkus,
        array $seenSkus,
    ): array {
        $categoryName = strtolower((string) ($row['category'] ?? ''));
        $unitName = strtolower((string) ($row['unit'] ?? ''));
        $category = $categories->get($categoryName);
        $unitId = $units->get($unitName);

        $payload = [
            'category_id' => $category?->id,
            'unit_id' => $unitId,
            'name' => $row['name'] ?? null,
            'sku' => $row['sku'] ?? null,
            'barcode' => $this->nullableString($row['barcode'] ?? null),
            'cost_price' => $this->nullableString($row['cost_price'] ?? null),
            'selling_price' => $this->nullableString($row['selling_price'] ?? null),
            'tax_rate' => $this->nullableString($row['tax_rate'] ?? null) ?? '0',
            'reorder_point' => $this->nullableString($row['reorder_point'] ?? null),
            'is_active' => $this->parseBoolean($row['is_active'] ?? null),
        ];

        $messages = [];

        if ($category === null) {
            $messages[] = 'Category "'.($row['category'] ?? '').'" was not found.';
        }

        if ($unitId === null) {
            $messages[] = 'Unit "'.($row['unit'] ?? '').'" was not found.';
        }

        $skuKey = strtolower((string) ($payload['sku'] ?? ''));

        if ($skuKey !== '' && (in_array($skuKey, $seenSkus, true) || in_array($skuKey, $existingSkus, true))) {
            $messages[] = 'SKU already exists.';
        }

        $validator = Validator::make($payload, [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(
                    fn ($query) => $query->where('organization_id', $organization->id),
                ),
            ],
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('units', 'id')->where(
                    fn ($query) => $query->where('organization_id', $organization->id),
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'cost_price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'selling_price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'reorder_point' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
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
            'data' => [
                'category_id' => $payload['category_id'],
                'unit_id' => $payload['unit_id'],
                'name' => $payload['name'],
                'sku' => $payload['sku'],
                'barcode' => $payload['barcode'],
                'cost_price' => $payload['cost_price'],
                'selling_price' => $payload['selling_price'],
                'tax_rate' => $payload['tax_rate'],
                'reorder_point' => $payload['reorder_point'],
                'is_active' => $payload['is_active'],
            ],
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

    protected function parseBoolean(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return true;
        }

        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'y', 'active'], true);
    }
}
