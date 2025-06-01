# Customization Guide

This document explains how to customize the link shortener without modifying core application files.

## 📁 File Organization

### Configuration Files
```
config/
├── shortener.php          # Main customization settings
└── app.php               # Laravel app config (usually no changes needed)
```

### Custom Code Locations
```
app/
├── Providers/
│   ├── CustomizationServiceProvider.php    # Main customization entry point
│   └── EventServiceProvider.php            # Event listener registration
├── Listeners/
│   ├── SendToGoogleAnalytics.php          # Example analytics listener
│   └── YourCustomListener.php             # Your custom listeners
├── Http/Middleware/
│   ├── CustomTracking.php                 # Example custom middleware
│   └── YourCustomMiddleware.php           # Your custom middleware
└── Filament/Widgets/
    ├── CustomAnalyticsWidget.php          # Example custom widget
    └── YourCustomWidget.php               # Your custom widgets
```

## 🚀 Quick Start

### 1. Enable Customization System
Uncomment this line in `bootstrap/providers.php`:
```php
App\Providers\CustomizationServiceProvider::class,
```

### 2. Basic Configuration
Edit `config/shortener.php`:
```php
return [
    'homepage' => [
        'redirect_to_admin' => true,    // Redirect / to /admin
        // OR
        'redirect_url' => 'https://mysite.com',  // Redirect to external site
    ],
    
    'not_found' => [
        'redirect_url' => 'https://mysite.com',  // Redirect 404s to main site
        'track_attempts' => true,                // Log failed attempts
    ],
];
```

## 📡 Event-Based Customization

### Option 1: Simple Event Listeners (in EventServiceProvider)
Add to `app/Providers/EventServiceProvider.php`:
```php
protected $listen = [
    \App\Events\LinkClicked::class => [
        \App\Listeners\SendToGoogleAnalytics::class,
    ],
    \App\Events\LinkNotFound::class => [
        function (\App\Events\LinkNotFound $event) {
            logger('404 attempt: ' . $event->shortCode);
        }
    ],
];
```

### Option 2: Service Provider Registration (in CustomizationServiceProvider)
Edit `app/Providers/CustomizationServiceProvider.php`:
```php
private function registerEventListeners(): void
{
    Event::listen(LinkClicked::class, function (LinkClicked $event) {
        // Your custom tracking code
        MyAnalytics::track($event->link, $event->request);
    });
}
```

## 🔧 Advanced Customization Examples

### Custom Analytics Integration
Create `app/Listeners/SendToMixpanel.php`:
```php
<?php

namespace App\Listeners;

use App\Events\LinkClicked;

class SendToMixpanel
{
    public function handle(LinkClicked $event): void
    {
        $mixpanel = new \Mixpanel('YOUR_TOKEN');
        $mixpanel->track('Link Clicked', [
            'link_id' => $event->link->id,
            'destination' => $event->link->original_url,
            'group' => $event->link->group?->name,
        ]);
    }
}
```

### Custom Middleware for Geo-blocking
Create `app/Http/Middleware/GeoBlock.php`:
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class GeoBlock
{
    public function handle(Request $request, Closure $next)
    {
        $country = $this->getCountryFromIP($request->ip());
        
        if (in_array($country, ['CN', 'RU'])) {
            return redirect('https://blocked-notice.example.com');
        }
        
        return $next($request);
    }
}
```

Register in `CustomizationServiceProvider.php`:
```php
private function registerCustomMiddleware(): void
{
    $this->app['router']->aliasMiddleware('geo-block', \App\Http\Middleware\GeoBlock::class);
}
```

### Custom Filament Dashboard Widget
Create `app/Filament/Widgets/CompanyMetricsWidget.php`:
```php
<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CompanyMetricsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Campaign ROI', '$12,543')
                ->description('This month\'s link performance')
                ->color('success'),
        ];
    }
}
```

Register in `CustomizationServiceProvider.php`:
```php
private function registerFilamentExtensions(): void
{
    \Filament\Facades\Filament::registerWidgets([
        \App\Filament\Widgets\CompanyMetricsWidget::class,
    ]);
}
```

## 🔄 Step-by-Step Customization Process

### 1. For Simple Changes
- Edit `config/shortener.php`
- No code restart needed

### 2. For Event Listeners
1. Create listener class in `app/Listeners/`
2. Register in `EventServiceProvider.php` OR `CustomizationServiceProvider.php`
3. Run `php artisan config:cache` (if needed)

### 3. For Complex Extensions
1. Enable `CustomizationServiceProvider` in `bootstrap/providers.php`
2. Add your custom code in the appropriate methods
3. Create supporting classes (listeners, middleware, widgets)
4. Test thoroughly

## 📋 Common Use Cases

| What You Want | How To Do It |
|---------------|--------------|
| Redirect homepage to admin | `'homepage' => ['redirect_to_admin' => true]` |
| Custom 404 redirect | `'not_found' => ['redirect_url' => 'https://mysite.com']` |
| Track clicks in external system | Create listener for `LinkClicked` event |
| Block certain countries | Create custom middleware |
| Add company metrics to dashboard | Create custom Filament widget |
| Log all requests | Add custom middleware |
| Send webhooks on events | Create event listeners |

## 🛡️ Best Practices

1. **Never modify core files** - Use the customization system
2. **Test thoroughly** - Especially event listeners and middleware
3. **Use proper namespacing** - Follow Laravel conventions
4. **Document your changes** - For future maintenance
5. **Consider performance** - Especially in event listeners
6. **Use queues for heavy operations** - Don't slow down redirects

## 🔍 Debugging

- Check logs: `storage/logs/laravel.log`
- Use `logger()` function to debug your custom code
- Test events: `Event::fake()` in tests
- Check middleware: Add logging to see if it's running

## 💡 Need Help?

Common issues and solutions:

- **Events not firing**: Check if listeners are registered properly
- **Middleware not running**: Verify registration in service provider
- **Config not updating**: Run `php artisan config:cache`
- **Widgets not showing**: Check if service provider is enabled

Remember: This customization system lets you extend functionality without touching core code, making updates safer and easier!