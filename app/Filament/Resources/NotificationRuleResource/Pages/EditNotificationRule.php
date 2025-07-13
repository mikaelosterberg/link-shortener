<?php

namespace App\Filament\Resources\NotificationRuleResource\Pages;

use App\Filament\Resources\NotificationRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotificationRule extends EditRecord
{
    protected static string $resource = NotificationRuleResource::class;

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
