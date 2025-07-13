<?php

namespace App\Filament\Resources\LinkResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class NotificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'linkNotifications';

    protected static ?string $title = 'Notification Settings';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Notification Assignment')
                    ->schema([
                        Forms\Components\Select::make('notification_group_id')
                            ->label('Notification Group')
                            ->relationship('notificationGroup', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Select which group should be notified about this link'),

                        Forms\Components\Select::make('notification_type_id')
                            ->label('Notification Type')
                            ->relationship('notificationType', 'display_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Select the type of notifications to send'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Enable or disable notifications for this assignment'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Notification Settings')
                    ->schema([
                        Forms\Components\KeyValue::make('settings')
                            ->helperText('Override default notification settings for this specific link')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('notificationGroup.name')
            ->columns([
                Tables\Columns\TextColumn::make('notificationGroup.name')
                    ->label('Group')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('notificationType.display_name')
                    ->label('Type')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('notificationGroup.users_count')
                    ->counts('notificationGroup.users')
                    ->label('Users'),

                Tables\Columns\TextColumn::make('notificationGroup.channels_count')
                    ->counts('notificationGroup.channels')
                    ->label('Channels'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('notification_group_id')
                    ->label('Group')
                    ->relationship('notificationGroup', 'name'),

                Tables\Filters\SelectFilter::make('notification_type_id')
                    ->label('Type')
                    ->relationship('notificationType', 'display_name'),

                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->slideOver(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn ($record) => $record->is_active ? 'warning' : 'success')
                    ->action(function ($record) {
                        $record->update(['is_active' => ! $record->is_active]);
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No Notifications Configured')
            ->emptyStateDescription('This link has no notification groups assigned. Add one to receive health alerts and other notifications.')
            ->emptyStateIcon('heroicon-o-bell-slash');
    }
}
