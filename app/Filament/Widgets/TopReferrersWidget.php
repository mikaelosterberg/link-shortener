<?php

namespace App\Filament\Widgets;

use App\Models\Click;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopReferrersWidget extends BaseWidget
{
    protected static ?int $sort = 11;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Top Referrers';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Click::query()
                    ->selectRaw('referer, COUNT(*) as clicks, COUNT(DISTINCT ip_address) as unique_visitors')
                    ->whereNotNull('referer')
                    ->where('referer', '!=', '')
                    ->groupBy('referer')
                    ->orderByDesc('clicks')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('referer')
                    ->label('Referrer')
                    ->formatStateUsing(function ($state) {
                        $host = parse_url($state, PHP_URL_HOST);

                        return $host ?: 'Unknown';
                    })
                    ->description(fn ($state) => $state)
                    ->limit(50)
                    ->tooltip(fn ($state) => $state),

                Tables\Columns\TextColumn::make('clicks')
                    ->label('Total Clicks')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('unique_visitors')
                    ->label('Unique Visitors')
                    ->numeric()
                    ->description(function ($record) {
                        $ratio = $record->unique_visitors > 0 ? round($record->clicks / $record->unique_visitors, 1) : 0;

                        return "Avg {$ratio} clicks per visitor";
                    }),

                Tables\Columns\TextColumn::make('percentage')
                    ->label('Share')
                    ->getStateUsing(function ($record) {
                        $total = Click::whereNotNull('referer')->where('referer', '!=', '')->count();

                        return $total > 0 ? round(($record->clicks / $total) * 100, 1).'%' : '0%';
                    })
                    ->badge()
                    ->color('primary'),
            ])
            ->defaultSort('clicks', 'desc')
            ->paginated(false);
    }

    public function getTableRecordKey($record): string
    {
        return md5($record->referer);
    }
}
