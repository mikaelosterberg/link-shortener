<?php

namespace App\Filament\Resources\LinkResource\Pages;

use App\Filament\Resources\LinkResource;
use App\Services\LinkShortenerService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLink extends EditRecord
{
    protected static string $resource = LinkResource::class;

    protected bool $shouldRedirectToIndex = false;

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
            $service = new LinkShortenerService;

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
        // If "Save and Close" was clicked, redirect to index
        if ($this->shouldRedirectToIndex) {
            return $this->getResource()::getUrl('index');
        }

        // Otherwise, stay on the edit page
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Save')
                ->action(function () {
                    $this->shouldRedirectToIndex = false;
                    $this->save();
                }),
            Actions\Action::make('saveAndClose')
                ->label('Save and Close')
                ->action(function () {
                    $this->shouldRedirectToIndex = true;
                    $this->save();
                })
                ->color('gray'),
            $this->getCancelFormAction(),
        ];
    }
}
