<?php

namespace App\Services;

use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CsvImportService
{
    /**
     * Expected CSV columns in order
     */
    public const EXPECTED_COLUMNS = [
        'original_url',
        'custom_slug',
        'group_name',
        'expires_at',
        'password',
        'click_limit',
        'redirect_type',
        'is_active',
        'notes',
    ];

    /**
     * Parse and validate CSV content
     */
    public function parseAndValidate(string $csvContent, User $user): array
    {
        $lines = collect(explode("\n", trim($csvContent)))
            ->map(fn ($line) => str_getcsv($line))
            ->filter(fn ($line) => ! empty(array_filter($line)));

        if ($lines->isEmpty()) {
            return [
                'success' => false,
                'errors' => ['CSV file is empty or invalid'],
                'data' => [],
            ];
        }

        // Check header row
        $header = $lines->first();
        if (! $this->validateHeader($header)) {
            return [
                'success' => false,
                'errors' => [
                    'Invalid CSV header. Expected columns: '.implode(', ', self::EXPECTED_COLUMNS),
                    'Current header: '.implode(', ', $header),
                ],
                'data' => [],
            ];
        }

        // Process data rows
        $dataRows = $lines->skip(1);
        $validatedData = [];
        $errors = [];
        $warnings = [];
        $skipped = 0;

        // Check if this looks like the unmodified template file and add a warning
        if ($this->isUnmodifiedTemplate($dataRows)) {
            $warnings[] = 'Note: This appears to be the unmodified template file. The example URLs will be imported as-is.';
            $warnings[] = 'For production use, download the template, edit it with your own URLs, and upload your customized file.';
        }

        foreach ($dataRows as $index => $row) {
            $rowNumber = $index + 2; // +2 because we skip header and arrays are 0-indexed

            // Skip if this row looks like a header row (contains column names)
            $firstCell = trim($row[0] ?? '');
            if (in_array(strtolower($firstCell), ['original_url', 'url', 'link', 'original url'])) {
                $warnings[] = "Row {$rowNumber}: Skipped header-like row";
                $skipped++;

                continue;
            }

            // Skip completely empty rows
            if (empty(array_filter($row, fn ($cell) => ! empty(trim($cell))))) {
                $skipped++;

                continue;
            }

            // Check if redirect_type was invalid and we defaulted to 302
            $originalRedirectType = trim($row[6] ?? '');
            if (! empty($originalRedirectType) && ! in_array((int) $originalRedirectType, [301, 302, 307, 308])) {
                $warnings[] = "Row {$rowNumber}: Invalid redirect_type '{$originalRedirectType}' changed to 302";
            }

            $rowData = $this->parseRow($row, $user);
            $validation = $this->validateRow($rowData, $rowNumber);

            if (! $validation['valid']) {
                // All validation issues are treated as warnings - we skip invalid rows but continue processing
                $warnings = array_merge($warnings, $validation['errors']);
                $skipped++;
            } else {
                $validatedData[] = $validation['data'];
            }
        }

        // Check if we have at least some valid data
        $hasValidData = count($validatedData) > 0;

        return [
            'success' => $hasValidData, // Succeed if we have at least one valid row
            'errors' => $errors, // Only structural errors that prevent processing
            'warnings' => $warnings, // Validation issues with individual rows
            'data' => $validatedData,
            'total_rows' => count($dataRows),
            'valid_rows' => count($validatedData),
            'skipped_rows' => $skipped,
        ];
    }

    /**
     * Import validated data into database
     */
    public function import(array $validatedData, User $user): array
    {
        $imported = 0;
        $errors = [];
        $createdLinks = [];

        foreach ($validatedData as $linkData) {
            try {
                $link = $this->createLink($linkData, $user);
                $createdLinks[] = $link;
                $imported++;
            } catch (\Exception $e) {
                // Handle specific errors gracefully
                if (str_contains($e->getMessage(), 'short_code') && str_contains($e->getMessage(), 'unique')) {
                    $errors[] = "Skipped link '{$linkData['original_url']}': Custom slug already exists";
                } else {
                    $errors[] = "Failed to create link for URL {$linkData['original_url']}: {$e->getMessage()}";
                }
            }
        }

        return [
            'success' => $imported > 0,
            'imported' => $imported,
            'total' => count($validatedData),
            'errors' => $errors,
            'links' => $createdLinks,
        ];
    }

    /**
     * Check if this is the unmodified template file
     */
    private function isUnmodifiedTemplate($dataRows): bool
    {
        // Convert to array if it's a collection
        $rows = is_array($dataRows) ? $dataRows : $dataRows->toArray();

        // Check if this contains the specific template URLs we generate
        $templateUrls = [
            'https://github.com/laravel/laravel',
            'https://docs.laravel.com',
            'https://filamentphp.com',
        ];

        $foundTemplateUrls = 0;
        foreach ($rows as $row) {
            if (! empty($row) && isset($row[0])) {
                $url = trim($row[0]);
                if (in_array($url, $templateUrls)) {
                    $foundTemplateUrls++;
                }
            }
        }

        // If we found 2 or more template URLs, it's likely the unmodified template
        return $foundTemplateUrls >= 2;
    }

    /**
     * Validate CSV header
     */
    private function validateHeader(array $header): bool
    {
        // Allow for flexible header matching (case-insensitive, handle extra columns)
        $normalizedHeader = array_map('strtolower', array_map('trim', $header));
        $expectedNormalized = array_map('strtolower', self::EXPECTED_COLUMNS);

        // Check if all required columns are present
        foreach ($expectedNormalized as $expected) {
            if (! in_array($expected, $normalizedHeader)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Parse a single CSV row into structured data
     */
    private function parseRow(array $row, User $user): array
    {
        // Pad row with empty strings if it's shorter than expected
        $row = array_pad($row, count(self::EXPECTED_COLUMNS), '');

        // Parse and validate redirect_type, default to 302 if invalid
        $redirectType = trim($row[6] ?? '');
        if (empty($redirectType) || ! in_array((int) $redirectType, [301, 302, 307, 308])) {
            $redirectType = 302;
        } else {
            $redirectType = (int) $redirectType;
        }

        return [
            'original_url' => trim($row[0] ?? ''),
            'custom_slug' => trim($row[1] ?? '') ?: null,
            'group_name' => trim($row[2] ?? '') ?: null,
            'expires_at' => trim($row[3] ?? '') ?: null,
            'password' => trim($row[4] ?? '') ?: null,
            'click_limit' => trim($row[5] ?? '') ?: null,
            'redirect_type' => $redirectType,
            'is_active' => trim($row[7] ?? '') !== '' ? (bool) $row[7] : true,
            'notes' => trim($row[8] ?? '') ?: null,
            'created_by' => $user->id,
        ];
    }

    /**
     * Validate a single row of data
     */
    private function validateRow(array $data, int $rowNumber): array
    {
        // Check for duplicate slug first
        $slugExists = false;
        if (! empty($data['custom_slug'])) {
            $slugExists = Link::where('short_code', $data['custom_slug'])->exists();
        }

        $validator = Validator::make($data, [
            'original_url' => ['required', 'url', 'max:2048'],
            'custom_slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\-_]+$/',
                // Skip unique validation if we're handling duplicates gracefully
            ],
            'expires_at' => ['nullable', 'date'],
            'password' => ['nullable', 'string', 'max:255'],
            'click_limit' => ['nullable', 'integer', 'min:1'],
            'redirect_type' => [
                'required',
                'integer',
                Rule::in([301, 302, 307, 308]),
            ],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'redirect_type.in' => 'The redirect_type must be exactly 301, 302, 307, or 308',
        ]);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->all() as $error) {
                $errors[] = "Row {$rowNumber}: {$error}";
            }

            return [
                'valid' => false,
                'errors' => $errors,
                'data' => null,
                'duplicate_slug' => false,
            ];
        }

        // Check if slug already exists
        if ($slugExists) {
            return [
                'valid' => false,
                'errors' => ["Row {$rowNumber}: Custom slug '{$data['custom_slug']}' already exists - will be skipped"],
                'data' => $data,
                'duplicate_slug' => true,
            ];
        }

        return [
            'valid' => true,
            'errors' => [],
            'data' => $data,
            'duplicate_slug' => false,
        ];
    }

    /**
     * Create a link from validated data
     */
    private function createLink(array $data, User $user): Link
    {
        // Handle group assignment
        $groupId = null;
        if (! empty($data['group_name'])) {
            try {
                // First check if group exists
                $group = LinkGroup::where('name', $data['group_name'])->first();

                if (! $group) {
                    // Create new group
                    $group = LinkGroup::create([
                        'name' => $data['group_name'],
                        'color' => $this->generateGroupColor(),
                        'description' => null,
                        'is_default' => false,
                    ]);
                }

                $groupId = $group->id;
            } catch (\Exception $e) {
                // Log the error for debugging
                \Log::error('Failed to create LinkGroup', [
                    'group_name' => $data['group_name'],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Continue without group assignment
                $groupId = null;
            }
        } else {
            // No group specified, try to use default group
            $defaultGroup = LinkGroup::getDefault();
            if ($defaultGroup) {
                $groupId = $defaultGroup->id;
            }
        }

        // Generate short code if not provided
        $shortCode = $data['custom_slug'] ?: $this->generateShortCode();

        // Parse expires_at
        $expiresAt = null;
        if (! empty($data['expires_at'])) {
            $expiresAt = Carbon::parse($data['expires_at']);
        }

        return Link::create([
            'original_url' => $data['original_url'],
            'short_code' => $shortCode,
            'group_id' => $groupId,
            'expires_at' => $expiresAt,
            'password' => $data['password'],
            'click_limit' => $data['click_limit'],
            'redirect_type' => $data['redirect_type'],
            'is_active' => $data['is_active'],
            'notes' => $data['notes'],
            'created_by' => $data['created_by'],
            'click_count' => 0,
        ]);
    }

    /**
     * Generate a unique short code
     */
    private function generateShortCode(): string
    {
        do {
            $shortCode = Str::random(6);
        } while (Link::where('short_code', $shortCode)->exists());

        return $shortCode;
    }

    /**
     * Generate a random color for new groups
     */
    private function generateGroupColor(): string
    {
        $colors = [
            '#3B82F6', // blue-500
            '#10B981', // green-500
            '#F59E0B', // yellow-500
            '#EF4444', // red-500
            '#8B5CF6', // purple-500
            '#EC4899', // pink-500
            '#6366F1', // indigo-500
            '#6B7280', // gray-500
        ];

        return $colors[array_rand($colors)];
    }

    /**
     * Get a sample CSV template
     */
    public static function getSampleCsv(): string
    {
        $header = implode(',', self::EXPECTED_COLUMNS);

        // Use future dates to avoid validation errors
        $futureDate1 = now()->addMonths(6)->format('Y-m-d');
        $futureDate2 = now()->addYear()->format('Y-m-d');

        $samples = [
            "https://github.com/laravel/laravel,marketing-page,,{$futureDate1},secret123,1000,302,,\"Marketing campaign link\"",
            'https://docs.laravel.com,,,,,,,""',
            "https://filamentphp.com,product-launch,Products,{$futureDate2},,500,301,1,\"Product launch campaign\"",
        ];

        return $header."\n".implode("\n", $samples);
    }
}
