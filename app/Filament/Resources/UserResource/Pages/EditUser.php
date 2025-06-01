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
        // Fill the role field with user's current role
        $user = $this->record;
        $data['role'] = $user->roles->first()?->name ?? 'user';
        
        // Fill email verified toggle
        $data['email_verified'] = !is_null($user->email_verified_at);
        
        return $data;
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle email verification toggle
        if (isset($data['email_verified'])) {
            if ($data['email_verified'] && is_null($this->record->email_verified_at)) {
                $data['email_verified_at'] = now();
            } elseif (!$data['email_verified'] && !is_null($this->record->email_verified_at)) {
                $data['email_verified_at'] = null;
            }
        }
        
        // Remove the email_verified toggle from the actual data
        unset($data['email_verified']);
        
        return $data;
    }
    
    protected function afterSave(): void
    {
        $user = $this->record;
        $formData = $this->form->getState();
        
        // Handle role changes
        if (isset($formData['role'])) {
            $currentRole = $user->roles->first()?->name;
            $newRole = $formData['role'];
            
            // Only change role if it's different
            if ($currentRole !== $newRole) {
                // Don't allow removing super_admin role from self
                if ($currentRole === 'super_admin' && auth()->id() === $user->id) {
                    return;
                }
                
                // Remove all current roles and assign new one
                $user->syncRoles([$newRole]);
            }
        }
    }
}
