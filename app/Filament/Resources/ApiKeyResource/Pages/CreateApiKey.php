<?php

namespace App\Filament\Resources\ApiKeyResource\Pages;

use App\Filament\Resources\ApiKeyResource;
use App\Models\ApiKey;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateApiKey extends CreateRecord
{
    protected static string $resource = ApiKeyResource::class;

    private string $plainTextKey;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate the API key
        $this->plainTextKey = ApiKey::generateKey();

        // Store both the hash (for fast lookups) and the actual key (for display)
        $data['key_hash'] = hash('sha256', $this->plainTextKey);
        $data['api_key'] = $this->plainTextKey;

        // Set created_by if not set
        if (! isset($data['created_by'])) {
            $data['created_by'] = auth()->id();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Show a simple success notification
        Notification::make()
            ->title('API Key Created Successfully')
            ->body('Your API key is now visible in the table below. You can copy it by clicking on it.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
