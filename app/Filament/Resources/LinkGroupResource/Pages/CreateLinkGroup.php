<?php

namespace App\Filament\Resources\LinkGroupResource\Pages;

use App\Filament\Resources\LinkGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLinkGroup extends CreateRecord
{
    protected static string $resource = LinkGroupResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store the is_default value for afterCreate
        $this->isSettingAsDefault = $data['is_default'] ?? false;
        
        // Remove is_default from data to prevent direct creation with it
        unset($data['is_default']);
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        // Handle setting as default if is_default is true
        if ($this->isSettingAsDefault) {
            // Use database transaction to ensure consistency
            \DB::transaction(function () {
                $this->record->setAsDefault();
            });
            
            // Refresh the record to ensure the UI shows the updated state
            $this->record->refresh();
            
            // Send a notification to confirm the action
            \Filament\Notifications\Notification::make()
                ->title('Default group set')
                ->body("'{$this->record->name}' has been set as the default group.")
                ->success()
                ->send();
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    private bool $isSettingAsDefault = false;
}
