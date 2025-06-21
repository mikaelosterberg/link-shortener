<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\Report;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Analytics';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Select::make('visibility')
                    ->options([
                        'private' => 'Private (Only Me)',
                        'team' => 'Team (Admins)',
                        'public' => 'Public (Everyone)',
                    ])
                    ->default('private')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),

                Forms\Components\Section::make('Date Range')
                    ->schema([
                        Forms\Components\Select::make('global_filters.date_range_type')
                            ->label('Date Range Type')
                            ->options([
                                'relative' => 'Relative (e.g., Last 30 days)',
                                'fixed' => 'Fixed Date Range',
                            ])
                            ->default('relative')
                            ->live()
                            ->required(),

                        Forms\Components\Select::make('global_filters.relative_period')
                            ->label('Period')
                            ->options([
                                'last_7_days' => 'Last 7 days',
                                'last_30_days' => 'Last 30 days',
                                'last_90_days' => 'Last 90 days',
                                'last_6_months' => 'Last 6 months',
                                'last_year' => 'Last year',
                                'this_week' => 'This week',
                                'this_month' => 'This month',
                                'this_quarter' => 'This quarter',
                                'this_year' => 'This year',
                                'yesterday' => 'Yesterday',
                                'last_week' => 'Last week',
                                'last_month' => 'Last month',
                            ])
                            ->default('last_30_days')
                            ->visible(fn (Forms\Get $get): bool => $get('global_filters.date_range_type') === 'relative'),

                        Forms\Components\DatePicker::make('global_filters.start_date')
                            ->label('Start Date')
                            ->default(now()->subDays(30))
                            ->visible(fn (Forms\Get $get): bool => $get('global_filters.date_range_type') === 'fixed'),
                        Forms\Components\DatePicker::make('global_filters.end_date')
                            ->label('End Date')
                            ->default(now())
                            ->visible(fn (Forms\Get $get): bool => $get('global_filters.date_range_type') === 'fixed'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Data Filters')
                    ->schema([
                        Forms\Components\Select::make('global_filters.link_filter_type')
                            ->label('Include Data From')
                            ->options([
                                'all' => 'All Links',
                                'link_groups' => 'Specific Link Groups',
                                'specific_links' => 'Specific Links',
                            ])
                            ->default('all')
                            ->live()
                            ->required(),

                        Forms\Components\Select::make('global_filters.link_group_ids')
                            ->label('Link Groups')
                            ->multiple()
                            ->options(fn () => LinkGroup::pluck('name', 'id')->toArray())
                            ->searchable()
                            ->visible(fn (Forms\Get $get): bool => $get('global_filters.link_filter_type') === 'link_groups')
                            ->helperText('Select which link groups to include in this report'),

                        Forms\Components\Select::make('global_filters.link_ids')
                            ->label('Specific Links')
                            ->multiple()
                            ->options(fn () => Link::with('group')
                                ->get()
                                ->mapWithKeys(fn ($link) => [
                                    $link->id => $link->short_code.' - '.($link->group->name ?? 'No Group'),
                                ])
                                ->toArray())
                            ->searchable()
                            ->visible(fn (Forms\Get $get): bool => $get('global_filters.link_filter_type') === 'specific_links')
                            ->helperText('Select specific links to include in this report'),
                    ])
                    ->collapsible(),

                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner')
                    ->sortable(),
                Tables\Columns\TextColumn::make('visibility')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'public' => 'success',
                        'team' => 'warning',
                        'private' => 'gray',
                    }),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\TextColumn::make('components_count')
                    ->label('Components')
                    ->counts('components')
                    ->badge(),
                Tables\Columns\TextColumn::make('last_generated_at')
                    ->label('Last Generated')
                    ->dateTime()
                    ->sortable()
                    ->since(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('visibility')
                    ->options([
                        'private' => 'Private',
                        'team' => 'Team',
                        'public' => 'Public',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Report $record): string => route('reports.view', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('builder')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->label('Build')
                    ->url(fn (Report $record): string => route('reports.builder', $record)),
                Tables\Actions\Action::make('clone')
                    ->icon('heroicon-o-document-duplicate')
                    ->label('Clone')
                    ->action(function (Report $record) {
                        $cloned = $record->replicate(['components_count']);
                        $cloned->name = $record->name.' (Copy)';
                        $cloned->user_id = auth()->id();
                        $cloned->visibility = 'private';
                        $cloned->last_generated_at = null; // Reset since this is a new report
                        $cloned->save();

                        // Clone all components
                        foreach ($record->components as $component) {
                            $clonedComponent = $component->replicate();
                            $clonedComponent->report_id = $cloned->id;
                            $clonedComponent->save();
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Report cloned successfully!')
                            ->body("Created '{$cloned->name}' with all components.")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Clone Report')
                    ->modalDescription('This will create a copy of this report with all its components.'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }
}
