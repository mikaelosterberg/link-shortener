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
        $maxNotifications = Cache::get('health_check.max_notifications_per_link', 3);
        $cooldownHours = Cache::get('health_check.notification_cooldown_hours', 24);

        // Build query for failed links
        $query = Link::where('is_active', true)
            ->where('exclude_from_health_checks', false)
            ->where(function ($q) {
                $q->where('notification_paused', false)
                    ->orWhereNull('notification_paused');
            });

        // Filter by selected status codes and conditions
        $query->where(function ($q) use ($notifyOnStatuses) {
            foreach ($notifyOnStatuses as $status) {
                if ($status === 'timeout') {
                    // Include links with timeout status OR job failures (which are timeouts)
                    $q->orWhere('health_status', 'timeout')
                        ->orWhere(function ($subQ) {
                            $subQ->where('health_status', 'error')
                                ->whereNull('http_status_code')
                                ->where(function ($innerQ) {
                                    $innerQ->where('health_check_message', 'like', '%timeout%')
                                        ->orWhere('health_check_message', 'like', '%job failed%');
                                });
                        });
                } elseif ($status === 'connection_failed') {
                    // Include links with error status (connection failures)
                    // BUT only if they don't have an HTTP status code (meaning it's a connection error, not an HTTP error)
                    // AND it's not a job failure/timeout
                    $q->orWhere(function ($subQ) {
                        $subQ->where('health_status', 'error')
                            ->whereNull('http_status_code')
                            ->where('health_check_message', 'not like', '%timeout%')
                            ->where('health_check_message', 'not like', '%job failed%');
                    });
                } else {
                    // HTTP status codes - only include if the status code matches
                    $q->orWhere('http_status_code', $status);
                }
            }
        });

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
                $this->line("    Notification count: {$link->notification_count}, Last sent: {$link->last_notification_sent_at}");
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
                // Ensure we handle NULL notification_count properly
                $currentCount = $link->notification_count ?? 0;
                $newCount = $currentCount + 1;

                $link->update([
                    'notification_count' => $newCount,
                    'last_notification_sent_at' => now(),
                ]);

                // Check if we've hit the limit and should pause notifications
                $maxNotifications = Cache::get('health_check.max_notifications_per_link', 3);
                if ($maxNotifications > 0 && $newCount >= $maxNotifications) {
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
