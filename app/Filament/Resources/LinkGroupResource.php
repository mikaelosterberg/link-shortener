<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LinkGroupResource\Pages;
use App\Filament\Resources\LinkGroupResource\RelationManagers;
use App\Models\LinkGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LinkGroupResource extends Resource
{
    protected static ?string $model = LinkGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';
    
    protected static ?string $navigationGroup = 'Link Management';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(1000)
                    ->columnSpanFull(),
                Forms\Components\ColorPicker::make('color')
                    ->required(),
                Forms\Components\Toggle::make('is_default')
                    ->label('Set as default group')
                    ->helperText('New links without a specified group will be added to the default group')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            // Optionally show a notification that other defaults will be unset
                        }
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ColorColumn::make('color')
                    ->copyable()
                    ->copyMessage('Color copied')
                    ->copyMessageDuration(1500),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean()
                    ->label('Default')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('links_count')
                    ->counts('links')
                    ->label('Links')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('setDefault')
                    ->label('Set as Default')
                    ->icon('heroicon-o-star')
                    ->visible(fn ($record) => !$record->is_default)
                    ->action(function ($record) {
                        $record->setAsDefault();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Set as Default Group')
                    ->modalDescription('This will make this group the default for new links. Any existing default will be unset.')
                    ->modalSubmitActionLabel('Yes, set as default'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLinkGroups::route('/'),
            'create' => Pages\CreateLinkGroup::route('/create'),
            'edit' => Pages\EditLinkGroup::route('/{record}/edit'),
        ];
    }
}
