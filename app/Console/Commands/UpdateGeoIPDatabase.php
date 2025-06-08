<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateGeoIPDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geoip:update {--license= : MaxMind license key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download and update the MaxMind GeoLite2 database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating MaxMind GeoLite2 database...');

        // Get license key from option or environment
        $licenseKey = $this->option('license') ?? env('MAXMIND_LICENSE_KEY');

        if (! $licenseKey) {
            $this->error('MaxMind license key is required. Either:');
            $this->line('1. Use --license=YOUR_KEY option');
            $this->line('2. Set MAXMIND_LICENSE_KEY in your .env file');
            $this->line('3. Get a free license key at: https://www.maxmind.com/en/geolite2/signup');

            return Command::FAILURE;
        }

        // Create storage directory if it doesn't exist
        $storageDir = storage_path('app/geoip');
        if (! is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
            $this->info("Created directory: {$storageDir}");
        }

        // Download URL for GeoLite2-City database
        $downloadUrl = "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key={$licenseKey}&suffix=tar.gz";
        $tempFile = storage_path('app/geoip/temp.tar.gz');
        $extractDir = storage_path('app/geoip/temp_extract');
        $finalPath = storage_path('app/geoip/GeoLite2-City.mmdb');

        try {
            // Download the file
            $this->info('Downloading GeoLite2-City database...');
            $this->downloadWithProgress($downloadUrl, $tempFile);

            // Create extraction directory
            if (is_dir($extractDir)) {
                $this->removeDirectory($extractDir);
            }
            mkdir($extractDir, 0755, true);

            // Extract the tar.gz file
            $this->info('Extracting database...');
            $phar = new \PharData($tempFile);
            $phar->extractTo($extractDir);

            // Find the .mmdb file in the extracted directory
            $mmdbFile = $this->findMmdbFile($extractDir);

            if (! $mmdbFile) {
                throw new \Exception('Could not find .mmdb file in downloaded archive');
            }

            // Move the .mmdb file to final location
            if (file_exists($finalPath)) {
                unlink($finalPath);
            }

            if (! rename($mmdbFile, $finalPath)) {
                throw new \Exception('Failed to move database file to final location');
            }

            // Clean up temporary files
            unlink($tempFile);
            $this->removeDirectory($extractDir);

            $this->info('✓ GeoLite2 database updated successfully!');
            $this->line("Database location: {$finalPath}");
            $this->line('File size: '.$this->formatBytes(filesize($finalPath)));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to update database: '.$e->getMessage());

            // Clean up on failure
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            if (is_dir($extractDir)) {
                $this->removeDirectory($extractDir);
            }

            return Command::FAILURE;
        }
    }

    private function downloadWithProgress(string $url, string $destination): void
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Laravel GeoIP Updater/1.0',
                ],
            ],
        ]);

        $source = fopen($url, 'r', false, $context);
        if (! $source) {
            throw new \Exception('Failed to open download URL');
        }

        $dest = fopen($destination, 'w');
        if (! $dest) {
            fclose($source);
            throw new \Exception('Failed to create destination file');
        }

        $progressBar = $this->output->createProgressBar();
        $progressBar->start();

        while (! feof($source)) {
            $chunk = fread($source, 8192);
            fwrite($dest, $chunk);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line('');

        fclose($source);
        fclose($dest);
    }

    private function findMmdbFile(string $directory): ?string
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'mmdb') {
                return $file->getPathname();
            }
        }

        return null;
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }

    private function formatBytes(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, 2).' '.$units[$i];
    }
}
