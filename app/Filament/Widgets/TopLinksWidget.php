<?php

namespace App\Filament\Widgets;

use App\Models\Link;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopLinksWidget extends BaseWidget
{
    protected static ?string $heading = 'Most Clicked Links';
    
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = 'full';
    
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
                    ->copyMessage('Short URL copied!')
                    ->badge()
                    ->color(fn (Link $record): string => $record->group ? self::mapColorToFilamentColor($record->group->color) : 'primary'),
                    
                TextColumn::make('original_url')
                    ->label('Destination URL')
                    ->limit(50)
                    ->tooltip(fn (Link $record): string => $record->original_url),
                    
                TextColumn::make('clicks_count')
                    ->label(fn () => 'Clicks (' . $this->getFilterLabel() . ')')
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
        
        return 'Most Clicked Links (' . $selectedLabel . ')';
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
    
    /**
     * Map hex color to closest Filament color name
     */
    private static function mapColorToFilamentColor(string $hexColor): string
    {
        // Remove # if present
        $hex = ltrim($hexColor, '#');
        
        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Map to closest Filament color based on dominant channel
        if ($r > $g && $r > $b) {
            if ($r > 200) return 'danger';   // Red
            return 'warning';                // Dark red/orange
        } elseif ($g > $r && $g > $b) {
            return 'success';                // Green
        } elseif ($b > $r && $b > $g) {
            if ($b > 180) return 'info';     // Blue
            return 'primary';                // Dark blue
        } else {
            // Mixed colors or grays
            $brightness = ($r + $g + $b) / 3;
            if ($brightness < 100) return 'gray';
            if ($r > 150 && $g > 150) return 'warning'; // Yellow/orange
            return 'primary';                // Default
        }
    }
}