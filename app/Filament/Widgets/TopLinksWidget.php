<?php

namespace App\Filament\Widgets;

use App\Models\Link;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopLinksWidget extends BaseWidget
{
    protected static ?string $heading = 'Most Clicked Links (Last 7 Days)';
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Link::query()
                    ->withCount(['clicks' => function (Builder $query) {
                        $query->where('clicked_at', '>=', now()->subDays(7));
                    }])
                    ->orderByDesc('clicks_count')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('short_code')
                    ->label('Short Code')
                    ->url(fn (Link $record): string => url($record->short_code))
                    ->openUrlInNewTab()
                    ->copyable()
                    ->copyMessage('Short URL copied!')
                    ->badge()
                    ->color('primary'),
                    
                TextColumn::make('original_url')
                    ->label('Destination URL')
                    ->limit(50)
                    ->tooltip(fn (Link $record): string => $record->original_url),
                    
                TextColumn::make('clicks_count')
                    ->label('Clicks (7 days)')
                    ->badge()
                    ->color('success'),
                    
                TextColumn::make('click_count')
                    ->label('Total Clicks')
                    ->badge()
                    ->color('info'),
                    
                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable(),
            ])
            ->paginated(false);
    }
}