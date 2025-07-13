<?php

namespace App\Filament\Resources\NotificationGroupResource\RelationManagers;

use App\Models\NotificationChannel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ChannelsRelationManager extends RelationManager
{
    protected static string $relationship = 'channels';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('type')
                    ->options(NotificationChannel::getAvailableTypes())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set) {
                        $set('config', []);
                    }),

                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('config.email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->visible(fn (Forms\Get $get) => $get('type') === 'email'),

                        Forms\Components\TextInput::make('config.name')
                            ->label('Display Name')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'email'),

                        Forms\Components\TextInput::make('config.url')
                            ->label('Webhook URL')
                            ->url()
                            ->required()
                            ->visible(fn (Forms\Get $get) => $get('type') === 'webhook'),

                        Forms\Components\Select::make('config.method')
                            ->label('HTTP Method')
                            ->options(['GET' => 'GET', 'POST' => 'POST'])
                            ->default('POST')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'webhook'),

                        Forms\Components\Textarea::make('config.headers')
                            ->label('Custom Headers (JSON)')
                            ->helperText('Additional HTTP headers as JSON object')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'webhook'),

                        Forms\Components\TextInput::make('config.webhook_url')
                            ->label('Slack Webhook URL')
                            ->url()
                            ->required()
                            ->visible(fn (Forms\Get $get) => $get('type') === 'slack'),

                        Forms\Components\TextInput::make('config.channel')
                            ->label('Channel Name')
                            ->helperText('Override default channel (optional)')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'slack'),

                        Forms\Components\TextInput::make('config.username')
                            ->label('Bot Username')
                            ->helperText('Custom username for messages (optional)')
                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['slack', 'discord'])),

                        Forms\Components\TextInput::make('config.webhook_url')
                            ->label('Discord Webhook URL')
                            ->url()
                            ->required()
                            ->visible(fn (Forms\Get $get) => $get('type') === 'discord'),

                        Forms\Components\TextInput::make('config.webhook_url')
                            ->label('Teams Webhook URL')
                            ->url()
                            ->required()
                            ->visible(fn (Forms\Get $get) => $get('type') === 'teams'),
                    ]),

                Forms\Components\Toggle::make('is_active')
                    ->default(true),

                Forms\Components\KeyValue::make('settings')
                    ->helperText('Additional channel-specific settings'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'email',
                        'warning' => 'webhook',
                        'success' => 'slack',
                        'info' => 'discord',
                        'secondary' => 'teams',
                    ]),

                Tables\Columns\TextColumn::make('config_display')
                    ->label('Configuration')
                    ->getStateUsing(function (NotificationChannel $record): string {
                        return $record->config_display;
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(NotificationChannel::getAvailableTypes()),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
