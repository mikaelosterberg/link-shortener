<?php

namespace App\Filament\Resources\NotificationChannelResource\Pages;

use App\Filament\Resources\NotificationChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewNotificationChannel extends ViewRecord
{
    protected static string $resource = NotificationChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
