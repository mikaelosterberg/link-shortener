<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class TestNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:test {type : The notification type to test (system-alert|maintenance)} {--message= : Custom message to send}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sending different types of notifications';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $type = $this->argument('type');
        $customMessage = $this->option('message');

        $this->info("Testing {$type} notification...");

        try {
            switch ($type) {
                case 'system-alert':
                    $message = $customMessage ?: 'This is a test system alert notification';
                    $severity = $this->choice('Select severity level:', ['low', 'medium', 'high'], 'medium');

                    $additionalData = [
                        'affected_components' => ['Link Shortener', 'Analytics Dashboard'],
                        'recommended_actions' => [
                            'Check system logs for errors',
                            'Monitor service health',
                            'Contact system administrator if issues persist',
                        ],
                    ];

                    $notificationService->sendSystemAlert($message, $severity, $additionalData);
                    break;

                case 'maintenance':
                    $message = $customMessage ?: 'Scheduled maintenance will begin shortly';
                    $scheduledTime = now()->addHour(); // 1 hour from now

                    $additionalData = [
                        'affected_services' => ['Link Redirects', 'Admin Panel', 'API'],
                        'expected_duration' => '2 hours',
                        'impact_description' => 'Some services may be temporarily unavailable',
                    ];

                    $notificationService->sendMaintenanceNotification($message, $scheduledTime, $additionalData);
                    break;

                default:
                    $this->error("Unknown notification type: {$type}");
                    $this->line('Available types: system-alert, maintenance');

                    return 1;
            }

            $this->info("✅ {$type} notification sent successfully!");

        } catch (\Exception $e) {
            $this->error("❌ Failed to send {$type} notification: ".$e->getMessage());

            return 1;
        }

        return 0;
    }
}
