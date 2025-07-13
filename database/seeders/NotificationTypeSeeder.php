<?php

namespace Database\Seeders;

use App\Models\NotificationType;
use Illuminate\Database\Seeder;

class NotificationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultTypes = NotificationType::getDefaultTypes();

        foreach ($defaultTypes as $typeData) {
            NotificationType::firstOrCreate(
                ['name' => $typeData['name']],
                array_merge($typeData, ['is_active' => true])
            );
        }
    }
}
