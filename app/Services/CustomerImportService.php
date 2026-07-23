<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Organization;
use App\Support\CsvReader;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class CustomerImportService
{
    private const REQUIRED_COLUMNS = [
        'name',
    ];

    public function __construct(
        protected CustomerService $customerService,
    ) {}

    /**
     * @return array{imported: int, failed: int, errors: list<array{row: int, messages: list<string>}>}
     */
    public function import(Organization $organization, string $csv): array
    {
        $parsed = CsvReader::parse($csv);
        $this->assertRequiredColumns($parsed['headers']);

        $existingEmails = Customer::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn (string $email): string => strtolower($email))
            ->all();

        $seenEmails = [];
        $imported = 0;
        $errors = [];

        foreach ($parsed['rows'] as $index => $row) {
            $rowNumber = $index + 2;
            $prepared = $this->prepareRow(
                $organization,
                $row,
                $existingEmails,
                $seenEmails,
            );

            if ($prepared['errors'] !== []) {
                $errors[] = ['row' => $rowNumber, 'messages' => $prepared['errors']];

                continue;
            }

            $this->customerService->create($prepared['data']);
            $imported++;

            if ($prepared['data']['email'] !== null) {
                $emailKey = strtolower($prepared['data']['email']);
                $existingEmails[] = $emailKey;
                $seenEmails[] = $emailKey;
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
     * @param  list<string>  $existingEmails
     * @param  list<string>  $seenEmails
     * @return array{data: array<string, mixed>, errors: list<string>}
     */
    protected function prepareRow(
        Organization $organization,
        array $row,
        array $existingEmails,
        array $seenEmails,
    ): array {
        $payload = [
            'name' => $row['name'] ?? null,
            'email' => $this->nullableString($row['email'] ?? null),
            'phone' => $this->nullableString($row['phone'] ?? null),
            'address' => $this->nullableString($row['address'] ?? null),
        ];

        $messages = [];
        $emailKey = $payload['email'] !== null ? strtolower($payload['email']) : null;

        if ($emailKey !== null && (in_array($emailKey, $seenEmails, true) || in_array($emailKey, $existingEmails, true))) {
            $messages[] = 'Email already exists.';
        }

        $validator = Validator::make($payload, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->where('organization_id', $organization->id),
            ],
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
