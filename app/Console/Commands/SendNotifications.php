<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send 
                            {type : The type of notification (system|maintenance)}
                            {--message= : Custom message for system/maintenance notifications}
                            {--severity=medium : Severity level for system alerts (low|medium|high)}
                            {--schedule= : Scheduled time for maintenance (e.g., "2024-01-01 12:00:00")}
                            {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send system alerts and maintenance notifications';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $type = $this->argument('type');
        $dryRun = $this->option('dry-run');

        switch ($type) {
            case 'system':
                return $this->sendSystemAlert($notificationService, $dryRun);

            case 'maintenance':
                return $this->sendMaintenanceNotification($notificationService, $dryRun);

            default:
                $this->error("Invalid notification type: {$type}");
                $this->line('Valid types: system, maintenance');
                $this->line('For health notifications, use: php artisan notifications:send-health');

                return Command::FAILURE;
        }
    }

    /**
     * Send system alert notification
     */
    private function sendSystemAlert(NotificationService $notificationService, bool $dryRun): int
    {
        $message = $this->option('message');
        $severity = $this->option('severity');

        if (! $message) {
            $message = $this->ask('Enter the system alert message');
            if (! $message) {
                $this->error('System alert message is required');

                return Command::FAILURE;
            }
        }

        $this->info('🚨 Preparing system alert:');
        $this->line("Message: {$message}");
        $this->line("Severity: {$severity}");

        if ($dryRun) {
            $this->info('🔍 DRY RUN - System alert would be sent');

            return Command::SUCCESS;
        }

        try {
            $additionalData = [
                'timestamp' => now(),
                'triggered_by' => 'Manual notification command',
            ];

            $notificationService->sendSystemAlert($message, $severity, $additionalData);
            $this->info('✅ System alert sent successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to send system alert: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Send maintenance notification
     */
    private function sendMaintenanceNotification(NotificationService $notificationService, bool $dryRun): int
    {
        $message = $this->option('message');
        $schedule = $this->option('schedule');

        if (! $message) {
            $message = $this->ask('Enter the maintenance notification message');
            if (! $message) {
                $this->error('Maintenance message is required');

                return Command::FAILURE;
            }
        }

        $scheduledTime = null;
        if ($schedule) {
            try {
                $scheduledTime = now()->parse($schedule);
            } catch (\Exception $e) {
                $this->error("Invalid schedule format: {$schedule}");

                return Command::FAILURE;
            }
        } else {
            $scheduleInput = $this->ask('Enter scheduled maintenance time (e.g., "2024-01-01 12:00:00") or leave blank for immediate');
            if ($scheduleInput) {
                try {
                    $scheduledTime = now()->parse($scheduleInput);
                } catch (\Exception $e) {
                    $this->error("Invalid schedule format: {$scheduleInput}");

                    return Command::FAILURE;
                }
            }
        }

        $this->info('🔧 Preparing maintenance notification:');
        $this->line("Message: {$message}");
        if ($scheduledTime) {
            $this->line("Scheduled Time: {$scheduledTime->format('Y-m-d H:i:s')} ({$scheduledTime->diffForHumans()})");
        } else {
            $this->line('Scheduled Time: Immediate');
        }

        if ($dryRun) {
            $this->info('🔍 DRY RUN - Maintenance notification would be sent');

            return Command::SUCCESS;
        }

        try {
            $additionalData = [
                'triggered_by' => 'Manual notification command',
                'expected_duration' => '2 hours', // Default, could be made configurable
            ];

            $notificationService->sendMaintenanceNotification($message, $scheduledTime ?? now(), $additionalData);
            $this->info('✅ Maintenance notification sent successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to send maintenance notification: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
