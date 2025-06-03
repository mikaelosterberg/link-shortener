<?php

namespace App\Filament\Resources\LinkGroupResource\Pages;

use App\Filament\Resources\LinkGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLinkGroup extends EditRecord
{
    protected static string $resource = LinkGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store the is_default value for afterSave
        $this->isSettingAsDefault = $data['is_default'] ?? false;
        
        // Remove is_default from data to prevent direct update
        unset($data['is_default']);
        
        return $data;
    }
    
    protected function afterSave(): void
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
                ->title('Default group updated')
                ->body("'{$this->record->name}' has been set as the default group.")
                ->success()
                ->send();
        } elseif ($this->record->is_default && !$this->isSettingAsDefault) {
            // If it was default but now unchecked, unset it
            $this->record->update(['is_default' => false]);
            $this->record->refresh();
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    private bool $isSettingAsDefault = false;
}
