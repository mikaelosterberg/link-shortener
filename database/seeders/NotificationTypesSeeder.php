<?php

namespace Database\Seeders;

use App\Models\NotificationType;
use Illuminate\Database\Seeder;

class NotificationTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $notificationTypes = [
            [
                'name' => 'link_health',
                'display_name' => 'Link Health Alert',
                'description' => 'Notifications for failed link health checks',
                'default_groups' => [],
                'notify_link_owner' => true,
                'apply_to_link_groups' => null,
                'exclude_blocked_links' => false,
                'is_active' => true,
                'default_settings' => [],
            ],
            [
                'name' => 'system_alert',
                'display_name' => 'System Alert',
                'description' => 'Critical system notifications',
                'default_groups' => [],
                'notify_link_owner' => false,
                'apply_to_link_groups' => null,
                'exclude_blocked_links' => false,
                'is_active' => true,
                'default_settings' => [],
            ],
            [
                'name' => 'maintenance',
                'display_name' => 'Maintenance Notification',
                'description' => 'Scheduled maintenance announcements',
                'default_groups' => [],
                'notify_link_owner' => false,
                'apply_to_link_groups' => null,
                'exclude_blocked_links' => false,
                'is_active' => true,
                'default_settings' => [],
            ],
        ];

        foreach ($notificationTypes as $type) {
            NotificationType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}