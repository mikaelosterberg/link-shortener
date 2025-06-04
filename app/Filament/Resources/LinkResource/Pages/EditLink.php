<?php

namespace App\Filament\Resources\LinkResource\Pages;

use App\Filament\Resources\LinkResource;
use App\Services\LinkShortenerService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLink extends EditRecord
{
    protected static string $resource = LinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Set custom_slug to the current short_code so it shows in the form
        $data['custom_slug'] = $data['short_code'] ?? '';
        return $data;
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Check if custom_slug has changed
        $newSlug = $data['custom_slug'] ?? '';
        $currentShortCode = $this->record->short_code;
        
        if ($newSlug !== $currentShortCode) {
            $service = new LinkShortenerService();
            
            if (empty($newSlug)) {
                // If slug is empty, generate a new random code
                $data['short_code'] = $service->generateUniqueCode();
            } else {
                // Process the custom slug and ensure it's unique
                $processedSlug = $service->processCustomSlug($newSlug);
                
                // Check if it's unique (excluding current record)
                $exists = \App\Models\Link::where('short_code', $processedSlug)
                    ->where('id', '!=', $this->record->id)
                    ->exists();
                    
                if ($exists) {
                    throw new \Exception('This custom URL is already taken');
                }
                
                $data['short_code'] = $processedSlug;
            }
        }
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
