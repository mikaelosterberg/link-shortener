<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class NotificationLimitsSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static string $view = 'filament.pages.notification-limits-settings';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Notification Limits';

    protected static ?string $title = 'Notification Limits';

    protected static ?int $navigationSort = 35;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'max_notifications_per_link' => Cache::get('health_check.max_notifications_per_link', 3),
            'notification_cooldown_hours' => Cache::get('health_check.notification_cooldown_hours', 24),
            'check_timeout_seconds' => Cache::get('health_check.timeout_seconds', 10),
            'notify_on_status_codes' => Cache::get('health_check.notify_on_status_codes', ['404']),
            'exclude_timeout_from_notifications' => Cache::get('health_check.exclude_timeout_from_notifications', true),
            'batch_notification_limit' => Cache::get('health_check.batch_notification_limit', 50),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Notification Limits')
                    ->description('Configure limits for health check failure notifications')
                    ->schema([
                        Forms\Components\TextInput::make('max_notifications_per_link')
                            ->label('Maximum notifications per link')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Number of failure notifications to send before pausing (0 = unlimited)')
                            ->required(),
                        Forms\Components\TextInput::make('notification_cooldown_hours')
                            ->label('Notification cooldown (hours)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(168)
                            ->helperText('Hours to wait between sending notifications for the same link')
                            ->required(),
                        Forms\Components\TextInput::make('batch_notification_limit')
                            ->label('Maximum links per notification email')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(500)
                            ->helperText('Maximum number of broken links to include in a single notification')
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Health Check Settings')
                    ->description('Configure health check behavior')
                    ->schema([
                        Forms\Components\TextInput::make('check_timeout_seconds')
                            ->label('Check timeout (seconds)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(60)
                            ->helperText('Seconds to wait for a response before marking as timeout')
                            ->required(),
                        Forms\Components\Toggle::make('exclude_timeout_from_notifications')
                            ->label('Exclude timeouts from notifications')
                            ->helperText('Do not send notifications for links that timeout (likely blocked by server)')
                            ->columnSpan(2),
                    ])->columns(3),

                Forms\Components\Section::make('Status Code Filtering')
                    ->description('Choose which HTTP status codes should trigger notifications')
                    ->schema([
                        Forms\Components\CheckboxList::make('notify_on_status_codes')
                            ->label('Notify on these status codes')
                            ->options([
                                '400' => '400 - Bad Request',
                                '401' => '401 - Unauthorized',
                                '403' => '403 - Forbidden',
                                '404' => '404 - Not Found',
                                '410' => '410 - Gone',
                                '500' => '500 - Internal Server Error',
                                '502' => '502 - Bad Gateway',
                                '503' => '503 - Service Unavailable',
                                'timeout' => 'Timeout (no response)',
                                'connection_failed' => 'Connection Failed',
                            ])
                            ->columns(2)
                            ->helperText('Only selected status codes will trigger failure notifications')
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Save settings to cache (or database if you prefer)
        Cache::forever('health_check.max_notifications_per_link', $data['max_notifications_per_link']);
        Cache::forever('health_check.notification_cooldown_hours', $data['notification_cooldown_hours']);
        Cache::forever('health_check.timeout_seconds', $data['check_timeout_seconds']);
        Cache::forever('health_check.notify_on_status_codes', $data['notify_on_status_codes']);
        Cache::forever('health_check.exclude_timeout_from_notifications', $data['exclude_timeout_from_notifications']);
        Cache::forever('health_check.batch_notification_limit', $data['batch_notification_limit']);

        Notification::make()
            ->title('Notification limits updated')
            ->success()
            ->send();
    }
}
