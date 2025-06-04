<?php

namespace App\Filament\Resources\LinkResource\Pages;

use App\Filament\Resources\LinkResource;
use App\Services\LinkShortenerService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLink extends CreateRecord
{
    protected static string $resource = LinkResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $service = new LinkShortenerService();
        
        // Generate short code - use custom_slug if provided and not empty, otherwise auto-generate
        $customSlug = !empty($data['custom_slug']) ? $data['custom_slug'] : null;
        $data['short_code'] = $service->generateUniqueCode($customSlug);
        
        // Set created_by if not set
        if (!isset($data['created_by'])) {
            $data['created_by'] = auth()->id();
        }
        
        // If no group is selected, use the default group if one exists
        if (empty($data['group_id'])) {
            $defaultGroup = \App\Models\LinkGroup::where('is_default', true)->first();
            if ($defaultGroup) {
                $data['group_id'] = $defaultGroup->id;
            }
        }
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
