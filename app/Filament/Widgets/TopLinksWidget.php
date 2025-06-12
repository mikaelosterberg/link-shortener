<?php

namespace App\Filament\Widgets;

use App\Models\Link;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopLinksWidget extends BaseWidget
{
    protected static ?string $heading = 'Most Clicked Links';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '7';

    public function table(Table $table): Table
    {
        $days = (int) $this->filter;

        return $table
            ->query(
                Link::query()
                    ->with('group')
                    ->withCount(['clicks' => function (Builder $query) use ($days) {
                        $query->where('clicked_at', '>=', now()->subDays($days));
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
                    ->copyableState(fn (Link $record): string => url($record->short_code))
                    ->copyMessage('Short URL copied!')
                    ->copyMessageDuration(1500)
                    ->badge()
                    ->color(fn (Link $record) => $record->group ? Color::hex($record->group->color) : 'primary'),

                TextColumn::make('original_url')
                    ->label('Destination URL')
                    ->limit(50)
                    ->tooltip(fn (Link $record): string => 'Click to view stats: '.$record->original_url)
                    ->url(fn (Link $record): string => route('filament.admin.resources.links.edit', $record))
                    ->color('primary')
                    ->weight('medium'),

                TextColumn::make('clicks_count')
                    ->label(fn () => 'Clicks ('.$this->getFilterLabel().')')
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

    protected function getFilters(): ?array
    {
        return [
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 3 months',
        ];
    }

    public function getHeading(): string
    {
        $filterLabels = [
            '7' => 'Last 7 Days',
            '30' => 'Last 30 Days',
            '90' => 'Last 3 Months',
        ];

        $selectedLabel = $filterLabels[$this->filter] ?? 'Last 7 Days';

        return 'Most Clicked Links ('.$selectedLabel.')';
    }

    private function getFilterLabel(): string
    {
        $filterLabels = [
            '7' => '7 days',
            '30' => '30 days',
            '90' => '3 months',
        ];

        return $filterLabels[$this->filter] ?? '7 days';
    }
}
