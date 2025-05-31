<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LinkResource\Pages;
use App\Filament\Resources\LinkResource\RelationManagers;
use App\Models\Link;
use App\Services\LinkShortenerService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\ViewField;

class LinkResource extends Resource
{
    protected static ?string $model = Link::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';
    
    protected static ?string $navigationGroup = 'Link Management';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Link Details')
                    ->schema([
                        Forms\Components\TextInput::make('custom_slug')
                            ->label('Custom Short URL (Optional)')
                            ->placeholder('my-custom-url')
                            ->helperText('Leave empty to auto-generate. Only letters, numbers, hyphens allowed.')
                            ->regex('/^[a-z0-9\-_]*$/')
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('original_url')
                            ->label('Destination URL')
                            ->required()
                            ->url()
                            ->placeholder('https://example.com/long-url'),
                        Forms\Components\Select::make('group_id')
                            ->label('Category')
                            ->relationship('group', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required(),
                                Forms\Components\TextInput::make('description'),
                                Forms\Components\ColorPicker::make('color')
                                    ->default('#3B82F6'),
                            ]),
                        Forms\Components\Select::make('redirect_type')
                            ->label('Redirect Type')
                            ->options([
                                301 => '301 - Permanent',
                                302 => '302 - Temporary',
                                307 => '307 - Temporary (preserve method)',
                                308 => '308 - Permanent (preserve method)',
                            ])
                            ->default(302)
                            ->required(),
                    ])->columns(2),
                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive links will return 404'),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expiration Date')
                            ->nullable()
                            ->minDate(now()),
                    ])->columns(2),
                Forms\Components\Hidden::make('created_by')
                    ->default(auth()->id()),
                Forms\Components\Hidden::make('short_code'),
                
                // QR Code section - only show when editing existing links
                Forms\Components\Section::make('QR Code')
                    ->schema([
                        ViewField::make('qr_code')
                            ->view('filament.forms.qr-code')
                            ->hiddenLabel(),
                    ])
                    ->visible(fn ($record) => $record !== null)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('qr_code')
                    ->label('QR')
                    ->getStateUsing(fn ($record) => route('qr.display', ['link' => $record->id, 'size' => 40]))
                    ->size(40)
                    ->tooltip('Click to view QR code'),
                Tables\Columns\TextColumn::make('short_code')
                    ->label('Short URL')
                    ->searchable()
                    ->copyable()
                    ->formatStateUsing(fn ($state) => url($state))
                    ->description(fn ($record) => $record->custom_slug ? 'Custom' : 'Generated'),
                Tables\Columns\TextColumn::make('original_url')
                    ->label('Destination')
                    ->limit(40)
                    ->tooltip(fn ($state) => $state)
                    ->searchable(),
                Tables\Columns\TextColumn::make('group.name')
                    ->label('Category')
                    ->badge()
                    ->color(fn ($record) => $record->group?->color ?? 'gray'),
                Tables\Columns\TextColumn::make('click_count')
                    ->label('Clicks')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('M j, Y')
                    ->placeholder('Never')
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : null),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->relationship('group', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\Filter::make('expired')
                    ->query(fn (Builder $query) => $query->where('expires_at', '<', now()))
                    ->label('Expired'),
            ])
            ->actions([
                Tables\Actions\Action::make('copy_url')
                    ->label('Copy')
                    ->icon('heroicon-o-clipboard')
                    ->action(fn ($record) => null)
                    ->tooltip('Copy short URL'),
                Tables\Actions\Action::make('qr_code')
                    ->label('QR Code')
                    ->icon('heroicon-o-qr-code')
                    ->modalHeading('QR Code')
                    ->modalContent(fn ($record) => view('filament.modals.qr-code', ['link' => $record]))
                    ->modalActions([
                        Tables\Actions\Action::make('download_png')
                            ->label('Download PNG')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->url(fn ($record) => route('qr.download', ['link' => $record->id, 'format' => 'png']))
                            ->openUrlInNewTab(),
                        Tables\Actions\Action::make('download_svg')
                            ->label('Download SVG')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->url(fn ($record) => route('qr.download', ['link' => $record->id, 'format' => 'svg']))
                            ->openUrlInNewTab(),
                    ])
                    ->tooltip('View QR code'),
                Tables\Actions\Action::make('view_stats')
                    ->label('Stats')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record->id]) . '#clicks')
                    ->tooltip('View click statistics'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            RelationManagers\ClicksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLinks::route('/'),
            'create' => Pages\CreateLink::route('/create'),
            'edit' => Pages\EditLink::route('/{record}/edit'),
        ];
    }
}
