<?php

namespace App\Filament\Resources\LinkResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClicksRelationManager extends RelationManager
{
    protected static string $relationship = 'clicks';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('ip_address')
                    ->label('IP Address')
                    ->disabled(),
                Forms\Components\TextInput::make('country')
                    ->label('Country')
                    ->disabled(),
                Forms\Components\TextInput::make('city')
                    ->label('City')
                    ->disabled(),
                Forms\Components\Textarea::make('user_agent')
                    ->label('User Agent')
                    ->disabled(),
                Forms\Components\TextInput::make('referer')
                    ->label('Referrer')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('clicked_at')
                    ->label('Clicked At')
                    ->disabled(),
                Forms\Components\TextInput::make('utm_source')
                    ->label('UTM Source')
                    ->disabled(),
                Forms\Components\TextInput::make('utm_medium')
                    ->label('UTM Medium')
                    ->disabled(),
                Forms\Components\TextInput::make('utm_campaign')
                    ->label('UTM Campaign')
                    ->disabled(),
                Forms\Components\TextInput::make('utm_term')
                    ->label('UTM Term')
                    ->disabled(),
                Forms\Components\TextInput::make('utm_content')
                    ->label('UTM Content')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('clicked_at')
            ->columns([
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('country')
                    ->label('Country')
                    ->searchable()
                    ->badge()
                    ->color('primary')
                    ->placeholder('Unknown'),
                Tables\Columns\TextColumn::make('city')
                    ->label('City')
                    ->searchable()
                    ->placeholder('Unknown'),
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('Browser/Device')
                    ->limit(30)
                    ->tooltip(fn ($state) => $state)
                    ->formatStateUsing(function ($state) {
                        if (str_contains($state, 'Mobile')) {
                            return 'Mobile';
                        } elseif (str_contains($state, 'Chrome')) {
                            return 'Chrome';
                        } elseif (str_contains($state, 'Firefox')) {
                            return 'Firefox';
                        } elseif (str_contains($state, 'Safari')) {
                            return 'Safari';
                        } elseif (str_contains($state, 'Edge')) {
                            return 'Edge';
                        }
                        return 'Other';
                    }),
                Tables\Columns\TextColumn::make('referer')
                    ->label('Referrer')
                    ->limit(25)
                    ->placeholder('Direct')
                    ->formatStateUsing(fn ($state) => $state ? parse_url($state, PHP_URL_HOST) : 'Direct'),
                Tables\Columns\TextColumn::make('utm_campaign')
                    ->label('Campaign')
                    ->badge()
                    ->color('info')
                    ->placeholder('None')
                    ->toggleable()
                    ->tooltip(function ($record) {
                        $utmData = [];
                        if ($record->utm_source) $utmData[] = "Source: {$record->utm_source}";
                        if ($record->utm_medium) $utmData[] = "Medium: {$record->utm_medium}";
                        if ($record->utm_campaign) $utmData[] = "Campaign: {$record->utm_campaign}";
                        if ($record->utm_term) $utmData[] = "Term: {$record->utm_term}";
                        if ($record->utm_content) $utmData[] = "Content: {$record->utm_content}";
                        return !empty($utmData) ? implode("\n", $utmData) : 'No UTM data';
                    }),
                Tables\Columns\TextColumn::make('utm_source')
                    ->label('Source')
                    ->placeholder('None')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('utm_medium')
                    ->label('Medium')
                    ->placeholder('None')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('clicked_at')
                    ->label('Time')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
            ])
            ->defaultSort('clicked_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('country')
                    ->options(function () {
                        return \App\Models\Click::whereNotNull('country')
                            ->distinct()
                            ->pluck('country', 'country')
                            ->toArray();
                    }),
                Tables\Filters\Filter::make('has_location')
                    ->query(fn (Builder $query) => $query->whereNotNull('country'))
                    ->label('Has Location Data'),
                Tables\Filters\Filter::make('today')
                    ->query(fn (Builder $query) => $query->whereDate('clicked_at', today()))
                    ->label('Today'),
                Tables\Filters\Filter::make('this_week')
                    ->query(fn (Builder $query) => $query->whereBetween('clicked_at', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->label('This Week'),
                Tables\Filters\SelectFilter::make('utm_source')
                    ->options(function () {
                        return \App\Models\Click::whereNotNull('utm_source')
                            ->distinct()
                            ->pluck('utm_source', 'utm_source')
                            ->toArray();
                    })
                    ->label('UTM Source'),
                Tables\Filters\SelectFilter::make('utm_medium')
                    ->options(function () {
                        return \App\Models\Click::whereNotNull('utm_medium')
                            ->distinct()
                            ->pluck('utm_medium', 'utm_medium')
                            ->toArray();
                    })
                    ->label('UTM Medium'),
                Tables\Filters\SelectFilter::make('utm_campaign')
                    ->options(function () {
                        return \App\Models\Click::whereNotNull('utm_campaign')
                            ->distinct()
                            ->pluck('utm_campaign', 'utm_campaign')
                            ->toArray();
                    })
                    ->label('UTM Campaign'),
                Tables\Filters\Filter::make('has_utm')
                    ->query(fn (Builder $query) => $query->where(function ($q) {
                        $q->whereNotNull('utm_source')
                          ->orWhereNotNull('utm_medium')
                          ->orWhereNotNull('utm_campaign')
                          ->orWhereNotNull('utm_term')
                          ->orWhereNotNull('utm_content');
                    }))
                    ->label('Has UTM Data'),
            ])
            ->headerActions([
                // Remove create action - clicks are created automatically
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
