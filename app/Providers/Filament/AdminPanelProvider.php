<?php

namespace App\Providers\Filament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        FilamentAsset::register([
            Css::make('custom-theme', \Illuminate\Support\Facades\Vite::asset('resources/css/app.css')),
        ]);
    }

    /**
     * Check if email is properly configured for sending emails
     */
    public function isEmailConfigured(): bool
    {
        $mailer = config('mail.default');

        // If mailer is 'log' or 'array', it's not configured for real email sending
        if (in_array($mailer, ['log', 'array', 'null'])) {
            return false;
        }

        // For SMTP, check if host is configured
        if ($mailer === 'smtp') {
            return ! empty(config('mail.mailers.smtp.host')) &&
                   config('mail.mailers.smtp.host') !== '127.0.0.1' &&
                   config('mail.mailers.smtp.host') !== 'localhost';
        }

        // For other mailers (mailgun, ses, etc.), assume they're configured if not log/array
        return true;
    }

    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login();

        // Only enable password reset if email is configured
        if ($this->isEmailConfigured()) {
            $panel->passwordReset();
        }

        return $panel
            ->colors([
                'primary' => Color::Amber,
            ])
            ->favicon(asset('favicon.svg'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->resources([
                // Resources are auto-discovered from app/Filament/Resources
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\OverviewStatsWidget::class,
                \App\Filament\Widgets\LinkHealthWidget::class,
                \App\Filament\Widgets\GeographicStatsWidget::class,
                \App\Filament\Widgets\ClickTrendsChart::class,
                \App\Filament\Widgets\TrendingLinksWidget::class,
                \App\Filament\Widgets\TopLinksWidget::class,
                \App\Filament\Widgets\TrafficTypesWidget::class,
                \App\Filament\Widgets\TopReferrersWidget::class,
                \App\Filament\Widgets\AbTestStatsWidget::class,
                \App\Filament\Widgets\UtmCampaignStatsWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Link Management'),
                NavigationGroup::make('Analytics'),
                NavigationGroup::make('Settings'),
                NavigationGroup::make('System'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Profile')
                    ->url('/admin/user-profile')
                    ->icon('heroicon-o-user-circle'),
            ])
            ->plugin(
                FilamentShieldPlugin::make()
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3,
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 4,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ])
            );
    }
}
