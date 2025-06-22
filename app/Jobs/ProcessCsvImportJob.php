<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\CsvImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessCsvImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes

    public int $tries = 1; // Don't retry to avoid duplicate imports

    public function __construct(
        private string $filePath,
        private int $userId,
        private ?string $sessionId = null
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        try {
            $user = User::findOrFail($this->userId);

            // Check if user still has permission
            if (! $user->can('create_link')) {
                Log::warning('CSV import job cancelled: User no longer has permission', [
                    'user_id' => $this->userId,
                    'file_path' => $this->filePath,
                ]);
                $this->cleanup();

                return;
            }

            $csvContent = Storage::disk('local')->get($this->filePath);
            if ($csvContent === null) {
                Log::error('CSV import job failed: Unable to read file', [
                    'file_path' => $this->filePath,
                ]);
                $this->cleanup();

                return;
            }

            $csvService = new CsvImportService;

            // Parse and validate
            $parseResult = $csvService->parseAndValidate($csvContent, $user);

            if (! $parseResult['success']) {
                Log::warning('CSV import job failed validation', [
                    'user_id' => $this->userId,
                    'errors' => $parseResult['errors'],
                ]);
                $this->cleanup();

                return;
            }

            // Import the data
            $importResult = $csvService->import($parseResult['data'], $user);

            Log::info('CSV import job completed', [
                'user_id' => $this->userId,
                'imported' => $importResult['imported'],
                'total' => $importResult['total'],
                'skipped' => $parseResult['skipped_rows'] ?? 0,
                'errors' => $importResult['errors'],
                'warnings' => $parseResult['warnings'] ?? [],
            ]);

            // Store results for later retrieval if session ID provided
            if ($this->sessionId) {
                $resultData = [
                    'success' => $importResult['success'],
                    'imported' => $importResult['imported'],
                    'total' => $importResult['total'],
                    'errors' => $importResult['errors'],
                    'completed_at' => now()->toISOString(),
                ];

                Storage::disk('local')->put(
                    "csv-import-results/{$this->sessionId}.json",
                    json_encode($resultData)
                );
            }

        } catch (\Exception $e) {
            Log::error('CSV import job exception', [
                'user_id' => $this->userId,
                'file_path' => $this->filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } finally {
            $this->cleanup();
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CSV import job failed permanently', [
            'user_id' => $this->userId,
            'file_path' => $this->filePath,
            'error' => $exception->getMessage(),
        ]);

        $this->cleanup();

        // Store failure result if session ID provided
        if ($this->sessionId) {
            $resultData = [
                'success' => false,
                'error' => 'Import job failed: '.$exception->getMessage(),
                'completed_at' => now()->toISOString(),
            ];

            Storage::disk('local')->put(
                "csv-import-results/{$this->sessionId}.json",
                json_encode($resultData)
            );
        }
    }

    private function cleanup(): void
    {
        try {
            if (Storage::disk('local')->exists($this->filePath)) {
                Storage::disk('local')->delete($this->filePath);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to cleanup CSV import file', [
                'file_path' => $this->filePath,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
