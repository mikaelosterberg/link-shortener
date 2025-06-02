<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => 
                    auth()->user()->hasRole('super_admin') && 
                    !$this->record->hasRole('super_admin') &&
                    auth()->id() !== $this->record->id
                ),
        ];
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Fill the roles field with user's current roles
        $user = $this->record;
        $data['roles'] = $user->roles->pluck('name')->toArray();
        
        return $data;
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }
    
    protected function afterSave(): void
    {
        $user = $this->record;
        $formData = $this->form->getState();
        
        // Handle role changes
        if (isset($formData['roles'])) {
            $currentRoles = $user->roles->pluck('name')->toArray();
            $newRoles = $formData['roles'];
            
            // Don't allow removing super_admin role from self
            if (auth()->id() === $user->id && 
                in_array('super_admin', $currentRoles) && 
                !in_array('super_admin', $newRoles)) {
                return;
            }
            
            // Sync roles
            $user->syncRoles($newRoles);
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
