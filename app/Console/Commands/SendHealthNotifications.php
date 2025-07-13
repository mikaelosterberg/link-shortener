<?php

namespace App\Console\Commands;

use App\Models\Link;
use App\Services\NotificationService;
use Illuminate\Console\Command;

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

        // Get all links that have failed health checks
        $failedLinks = Link::where('is_active', true)
            ->whereIn('health_status', ['error', 'warning'])
            ->with(['group', 'creator'])
            ->get();

        if ($failedLinks->isEmpty()) {
            $this->info('✅ No failed links found. All links are healthy!');

            return 0;
        }

        $this->warn("Found {$failedLinks->count()} failed links:");

        foreach ($failedLinks as $link) {
            $this->line("  - {$link->original_url} ({$link->health_status}): {$link->health_check_message}");
        }

        if ($this->option('dry-run')) {
            $this->info('🔍 Dry run mode - no notifications will be sent');
            $this->info('The following would happen:');

            // Show what notifications would be sent
            $this->showNotificationPlan($failedLinks);

            return 0;
        }

        $this->info('📧 Sending health notifications...');

        try {
            $notificationService->sendLinkHealthNotifications($failedLinks);
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
    private function showNotificationPlan($failedLinks): void
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
