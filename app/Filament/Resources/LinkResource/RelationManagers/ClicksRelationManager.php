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
