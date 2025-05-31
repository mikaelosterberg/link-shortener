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
}
