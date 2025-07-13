<?php

namespace App\Filament\Resources\NotificationGroupResource\Pages;

use App\Filament\Resources\NotificationGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNotificationGroup extends CreateRecord
{
    protected static string $resource = NotificationGroupResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
