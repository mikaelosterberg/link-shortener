<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Handle email verification
        if (isset($data['email_verified']) && $data['email_verified']) {
            $data['email_verified_at'] = now();
        }
        
        // Remove the email_verified toggle from the actual data
        unset($data['email_verified']);
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        $user = $this->record;
        $roleData = $this->form->getState();
        
        // Assign the selected role
        if (isset($roleData['role'])) {
            $user->assignRole($roleData['role']);
        }
    }
}
