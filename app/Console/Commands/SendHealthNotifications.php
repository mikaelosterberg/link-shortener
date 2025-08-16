<?php

namespace App\Console\Commands;

use App\Models\Link;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendHealthNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-health {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for failed link health checks';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $this->info('Checking for failed links...');

        // Get notification settings
        $notifyOnStatuses = Cache::get('health_check.notify_on_status_codes', ['404']);
        $excludeTimeout = Cache::get('health_check.exclude_timeout_from_notifications', true);
        $maxNotifications = Cache::get('health_check.max_notifications_per_link', 3);
        $cooldownHours = Cache::get('health_check.notification_cooldown_hours', 24);

        // Build query for failed links
        $query = Link::where('is_active', true)
            ->where('exclude_from_health_checks', false)
            ->where('notification_paused', false);

        // Build status conditions
        $statusConditions = [];
        foreach ($notifyOnStatuses as $status) {
            if ($status === 'timeout') {
                if (! $excludeTimeout) {
                    $statusConditions[] = 'timeout';
                }
            } elseif ($status === 'connection_failed') {
                $statusConditions[] = 'error';
            } else {
                // HTTP status codes
                $query->orWhere('http_status_code', $status);
            }
        }

        if (! empty($statusConditions)) {
            $query->whereIn('health_status', $statusConditions);
        }

        // Apply notification limits
        if ($maxNotifications > 0) {
            $query->where('notification_count', '<', $maxNotifications);
        }

        // Apply cooldown
        $query->where(function ($q) use ($cooldownHours) {
            $q->whereNull('last_notification_sent_at')
                ->orWhere('last_notification_sent_at', '<', now()->subHours($cooldownHours));
        });

        $failedLinks = $query->with(['group', 'creator'])->get();

        // Get previously notified links that are still broken but hit notification limit
        $previouslyFailedLinks = Link::where('is_active', true)
            ->where('exclude_from_health_checks', false)
            ->whereIn('health_status', ['error', 'timeout', 'warning'])
            ->where('notification_count', '>=', $maxNotifications)
            ->where('notification_count', '>', 0)
            ->with(['group', 'creator'])
            ->get();

        if ($failedLinks->isEmpty() && $previouslyFailedLinks->isEmpty()) {
            $this->info('✅ No failed links found. All links are healthy!');

            return 0;
        }

        if ($failedLinks->isNotEmpty()) {
            $this->warn("Found {$failedLinks->count()} newly failed links:");
            foreach ($failedLinks as $link) {
                $this->line("  - {$link->original_url} ({$link->health_status}): {$link->health_check_message}");
            }
        }

        if ($previouslyFailedLinks->isNotEmpty()) {
            $this->warn("Plus {$previouslyFailedLinks->count()} previously notified links still broken:");
            foreach ($previouslyFailedLinks as $link) {
                $this->line("  - {$link->original_url} ({$link->health_status}): notifications paused after {$link->notification_count} sent");
            }
        }

        if ($this->option('dry-run')) {
            $this->info('🔍 Dry run mode - no notifications will be sent');
            $this->info('The following would happen:');

            // Show what notifications would be sent
            $this->showNotificationPlan($failedLinks, $previouslyFailedLinks);

            return 0;
        }

        $this->info('📧 Sending health notifications...');

        try {
            $notificationService->sendLinkHealthNotifications($failedLinks, $previouslyFailedLinks);

            // Update notification counts for notified links
            foreach ($failedLinks as $link) {
                $link->update([
                    'notification_count' => $link->notification_count + 1,
                    'last_notification_sent_at' => now(),
                ]);

                // Check if we've hit the limit and should pause notifications
                $maxNotifications = Cache::get('health_check.max_notifications_per_link', 3);
                if ($maxNotifications > 0 && $link->notification_count >= $maxNotifications) {
                    $link->update(['notification_paused' => true]);
                }
            }

            $this->info('✅ Health notifications sent successfully!');
        } catch (\Exception $e) {
            $this->error('❌ Failed to send health notifications: '.$e->getMessage());

            return 1;
        }

        return 0;
    }

    /**
     * Show what notifications would be sent (dry run)
     */
    private function showNotificationPlan($failedLinks, $previouslyFailedLinks): void
    {
        $this->line('');
        $this->info('Notification Plan:');
        $this->line('');

        // Group links by notification groups (simplified logic for preview)
        $groupedLinks = $failedLinks->groupBy(function ($link) {
            return $link->group ? $link->group->name : 'Ungrouped';
        });

        foreach ($groupedLinks as $groupName => $links) {
            $this->line("📧 Group notifications for {$groupName}:");
            foreach ($links as $link) {
                $this->line("  - {$link->original_url}");
            }
            $this->line('');
        }

        // Show owner notifications
        $ownedLinks = $failedLinks->whereNotNull('created_by')->groupBy('created_by');

        if ($ownedLinks->isNotEmpty()) {
            $this->line('👤 Individual owner notifications:');
            foreach ($ownedLinks as $userId => $links) {
                $ownerName = $links->first()->creator->name ?? 'Unknown User';
                $this->line("  - {$ownerName}: {$links->count()} failed link(s)");
            }
        }
    }
}
