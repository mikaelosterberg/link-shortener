<?php

namespace App\Filament\Resources\NotificationGroupResource\Pages;

use App\Filament\Resources\NotificationGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotificationGroups extends ListRecords
{
    protected static string $resource = NotificationGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
