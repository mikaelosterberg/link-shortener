<?php

namespace App\Filament\Resources\NotificationGroupResource\Pages;

use App\Filament\Resources\NotificationGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotificationGroup extends EditRecord
{
    protected static string $resource = NotificationGroupResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
