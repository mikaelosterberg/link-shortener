<?php

namespace App\Filament\Resources\LinkGroupResource\Pages;

use App\Filament\Resources\LinkGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLinkGroups extends ListRecords
{
    protected static string $resource = LinkGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
