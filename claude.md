# Link Shortener Laravel Project

## Project Overview
A link shortening/redirection service built with Laravel and Filament to learn these technologies before starting a larger project. The app focuses on fast redirections, admin management, and API access.

## Operating Environment

### Development Setup
- **Host OS**: Windows with Laragon
- **Laravel App Location**: Running under Laragon on C: drive
- **Local URL**: https://redirection.test
- **PHP Path**: `C:\laragon\bin\php\php-8.3\php.exe`
- **Database**: SQLite (simpler than MySQL for learning)
- **Claude Code**: Running on Windows Subsystem for Linux (WSL)

### Potential WSL/Windows Issues RESOLVED
- **File path handling**: Use Windows cmd.exe for PHP/Composer commands when needed
- **SQLite Extensions**: Required enabling `extension=pdo_sqlite` and `extension=sqlite3` in php.ini
- **Laragon Restart**: Needed after php.ini changes
- **File permissions**: No issues encountered with shared directories
- **Testing**: 93+ tests pass successfully in WSL environment (101 total tests)

## Technology Stack
- **Backend**: Laravel 12.x (latest)
- **Admin Panel**: Filament 3.x (latest)
- **Authentication/Permissions**: Filament Shield
- **Database**: SQLite
- **Cache**: File-based (development), Redis (production)
- **API Authentication**: Laravel Sanctum
- **Analytics**: Built-in dashboard with geographic tracking (Google Analytics planned)
- **Geolocation**: MaxMind GeoLite2 (optional, local database)

## Database Schema

### Core Tables
```sql
-- Users (managed by Filament Shield)
users: id, name, email, password, role, timestamps

-- Link groups/categories
link_groups: id, name, description, color, is_default, timestamps

-- Main links table
links: id, short_code, original_url, group_id, redirect_type, 
       is_active, expires_at, created_by, click_count, 
       custom_slug, last_checked_at, health_status, 
       http_status_code, health_check_message, final_url, timestamps

-- Click tracking (with optional geolocation)
clicks: id, link_id, ip_address, user_agent, referer, 
        country, city, clicked_at

-- API keys for external access
api_keys: id, name, key_hash, permissions, last_used_at, 
          expires_at, created_by, timestamps
```

### Future Features Tables
```sql
-- A/B testing (future)
ab_tests: id, link_id, name, variants, split_percentage, 
          is_active, timestamps

ab_test_variants: id, ab_test_id, url, weight, click_count

-- Geo-targeting rules (requires MaxMind database)
geo_rules: id, link_id, country_codes, redirect_url, 
           priority, timestamps
```

## Core Features

### 1. Admin Panel (Filament)
**User Management** (Super Admin only)
- Create/edit/delete admin users with multiple role assignment
- Role-based access control with 4 roles:
  - `super_admin` - Unrestricted access to everything
  - `admin` - Limited permissions (assignable by super admin)
  - `user` - Basic role for regular users
  - `panel_user` - Basic panel access
- Multi-select role editing (users can have multiple roles)
- Role-based UI visibility (super admins see all options)
- Security controls (can't delete self or other super admins)
- User profile page accessible from user menu

**Link Management** (Enhanced Filament Interface)
- CRUD operations using Filament resources and Eloquent
- Bulk operations: activate, deactivate, health check, delete
- Link grouping/categorization with default group assignment
- Click statistics dashboard
- Toggle column for instant activate/deactivate
- QR code generation with modal and edit page views
- Link health monitoring with visual status indicators
- Improved action ordering (Edit first, then QR, Stats, Health, Delete)

**Groups/Categories**
- Organize links by purpose/campaign
- Color coding for visual organization
- Default group functionality (auto-assignment for new links)
- Group management via admin panel and API

### 2. Fast Redirection System
**Performance Requirements**
- Use DB facade raw SQL queries for redirect lookups only
- Admin management uses standard Eloquent/Filament methods
- Minimal overhead - direct HTTP redirects
- Cache frequently accessed links using file cache (development)
- Implement rate limiting: 60 requests per minute per IP

**Redirect Types**
- 301 (Permanent)
- 302 (Temporary)  
- 307 (Temporary, preserve method)
- 308 (Permanent, preserve method)

**Short Code Generation**
- User can specify custom slug (alphanumeric, hyphens, underscores)
- Auto-generate if not provided: 6-character base62 (a-zA-Z0-9)
- Validate uniqueness before saving
- Slugify user input: lowercase, replace spaces with hyphens

### 3. API System
**Authentication**: Laravel Sanctum API tokens
**Endpoints**:

**Links API**:
```
POST /api/links - Create short link (full response)
POST /api/simple - Create short link (simple response format)
GET /api/links - List user's links
GET /api/links/{id} - Get link details
PUT /api/links/{id} - Update link
DELETE /api/links/{id} - Delete link
GET /api/links/{id}/stats - Get click statistics
```

**Groups API** (NEW):
```
GET /api/groups - List all groups (use ?simple=true for dropdown)
GET /api/groups/{id} - Get group details
POST /api/groups - Create new group
PUT /api/groups/{id} - Update group
DELETE /api/groups/{id} - Delete group (only if empty)
```

**Default Group Feature**:
- Groups can be marked as default with `is_default` field
- Only one group can be default at a time
- Links created without `group_id` use the default group automatically
- Set default via API: `POST/PUT` with `"is_default": true`

**Simple API Format**:
The `/api/simple` endpoint provides a streamlined response format:

*Request Parameters*:
- `url` (required): URL to shorten
- `keyword` (optional): Custom short code
- `title` (optional): Link title

*Response Format*:
```json
{
  "url": {
    "keyword": "abc123",
    "url": "https://example.com",
    "title": "Example Site",
    "date": "2025-01-01 12:00:00",
    "ip": "127.0.0.1"
  },
  "status": "success",
  "message": "https://example.com added to database",
  "shorturl": "https://redirection.test/abc123",
  "statusCode": 200
}
```

Applications can extract the `shorturl` field directly for immediate use.

### 4. Extensibility & Customization

The application supports customization without modifying core code through configuration files, events, and service providers.

#### Configuration-Based Customization

**Homepage Behavior** (`config/shortener.php`):
```php
'homepage' => [
    'redirect_to_admin' => true,           // Redirect / to /admin
    'redirect_url' => 'https://mysite.com', // Or redirect to external URL
    'view' => 'custom.homepage',           // Or use custom view
],
```

**404 Handling**:
```php
'not_found' => [
    'redirect_url' => 'https://mysite.com',  // Redirect instead of 404
    'track_attempts' => true,                // Log failed attempts
    'view' => 'custom.not-found',           // Custom 404 page
],
```

**Link Processing**:
```php
'link_processing' => [
    'validate_ssl' => true,      // Validate destination SSL
    'max_redirects' => 5,        // Max redirects to follow
    'validation_timeout' => 10,  // URL validation timeout
],
```

#### Event-Driven Extensions

**Key Events Dispatched**:
```php
// When a link is clicked successfully
event(new LinkClicked($link, $request));

// When a short code is not found
event(new LinkNotFound($shortCode, $request));
```

**Custom Event Listeners**:
```php
// In a service provider or EventServiceProvider
Event::listen(LinkClicked::class, function (LinkClicked $event) {
    // Custom analytics tracking
    Analytics::track('link_clicked', [
        'link_id' => $event->link->id,
        'user_agent' => $event->request->userAgent(),
        'referer' => $event->request->header('referer'),
    ]);
});

Event::listen(LinkNotFound::class, function (LinkNotFound $event) {
    // Custom 404 handling
    logger('Invalid short code attempted', [
        'code' => $event->shortCode,
        'ip' => $event->request->ip(),
    ]);
});
```

#### Service Provider Extensions

Create custom service providers for complex extensions:

```php
// app/Providers/CustomAnalyticsProvider.php
class CustomAnalyticsProvider extends ServiceProvider
{
    public function boot()
    {
        // Register custom analytics providers
        $this->app->bind('analytics.custom', CustomAnalyticsService::class);
        
        // Add custom middleware
        $this->app['router']->pushMiddlewareToGroup('web', CustomTrackingMiddleware::class);
        
        // Register custom Filament widgets
        Filament::registerWidgets([
            CustomAnalyticsWidget::class,
        ]);
    }
}
```

#### Use Cases & Examples

**1. Redirect Homepage to Admin**:
```php
// config/shortener.php
'homepage' => ['redirect_to_admin' => true]
```

**2. Custom 404 Redirect**:
```php
// Redirect to main website when short code not found
'not_found' => ['redirect_url' => 'https://mycompany.com']
```

**3. Custom Analytics Integration**:
```php
// Listen for clicks and send to external service
Event::listen(LinkClicked::class, SendToMixpanel::class);
```

**4. Geo-blocking Middleware**:
```php
// Block certain countries from accessing links
Route::middleware(['geo-block'])->group(function () {
    Route::get('/{shortCode}', [RedirectController::class, 'redirect']);
});
```

This extensibility system provides flexibility for common customizations while maintaining clean core code.

#### File Organization for Custom Code

Users should place their customization code in these locations:

**1. Configuration Changes:**
```
config/shortener.php - Main customization settings
```

**2. Event Listeners:**
```
app/Listeners/SendToGoogleAnalytics.php - Custom analytics
app/Listeners/YourCustomListener.php - Other listeners
```

**3. Custom Service Provider:**
```
app/Providers/CustomizationServiceProvider.php - Main extension point
bootstrap/providers.php - Uncomment to enable
```

**4. Custom Middleware:**
```
app/Http/Middleware/CustomTracking.php - Request processing
app/Http/Middleware/GeoBlock.php - Geographic restrictions
```

**5. Custom Widgets:**
```
app/Filament/Widgets/CustomAnalyticsWidget.php - Dashboard extensions
```

**Setup Process:**
1. Uncomment `CustomizationServiceProvider` in `bootstrap/providers.php`
2. Edit the provided example files or create new ones
3. Register listeners/middleware/widgets in the service provider
4. No core code modification needed

See `CUSTOMIZATION.md` for detailed examples and step-by-step instructions.

### 5. Analytics Integration
**Server-side Tracking**
- Log clicks before redirect
- Send events to Google Analytics Measurement Protocol
- Store click data for internal reporting

**Tracked Data**
- Click timestamps
- IP addresses with optional geolocation (country, city)
- User agent
- Referrer
- Click counts per link

### 6. A/B Testing System

The A/B testing feature allows you to test multiple destination URLs for a single short link to optimize conversion rates.

#### Core Functionality
**Test Management**
- Create multiple variants with different destination URLs
- Weighted traffic distribution (percentage-based)
- Time-based test scheduling (start/end dates)
- Performance tracking and analytics

**Variant Selection**
- Weighted random selection algorithm
- Consistent user experience during test period
- Fallback to original URL if no test is active

#### Database Schema
```sql
-- A/B test configuration
ab_tests: id, link_id, name, description, is_active, 
          starts_at, ends_at, timestamps

-- Test variants with traffic distribution
ab_test_variants: id, ab_test_id, name, url, weight, 
                  click_count, conversion_count, timestamps

-- Click tracking includes variant information
clicks: ab_test_variant_id (nullable, tracks which variant was shown)
```

#### Admin Interface Features
**Test Configuration**
- Create tests directly from link edit page
- Add 2-10 variants per test with custom names and URLs
- Set traffic weights (must total 100%)
- Schedule tests with start/end dates
- One A/B test per link maximum

**Performance Analytics**
- Real-time click distribution across variants
- Conversion rate tracking per variant
- Leading variant identification
- Statistical significance indicators
- Detailed performance modal with insights

**Dashboard Widget**
- Overview of all active A/B tests
- Quick performance metrics
- Test status monitoring (collecting data vs statistically significant)
- Direct links to test management

#### Technical Implementation
```php
// Redirect flow with A/B testing
public function redirect(string $shortCode)
{
    $link = // ... load link with A/B test and variants
    
    $targetUrl = $link->original_url;
    $selectedVariant = null;
    
    // Check A/B test if one exists and is active
    if ($link->abTest && $link->abTest->isActiveNow()) {
        $selectedVariant = $link->abTest->selectVariant();
        if ($selectedVariant) {
            $targetUrl = $selectedVariant->url;
        }
    }
    
    // Check geo rules (can override A/B test URL)
    if ($link->geoRules->isNotEmpty()) {
        // Geo-targeting logic...
    }
    
    // Log click with variant information
    LogClickJob::dispatch([
        'ab_test_variant_id' => $selectedVariant?->id,
        // ... other click data
    ]);
    
    // Increment variant counter
    if ($selectedVariant) {
        $selectedVariant->incrementClicks();
    }
    
    return redirect($targetUrl, $link->redirect_type);
}
```

#### Integration with Other Features
**UTM Parameter Pass-through**
- A/B test variants work seamlessly with UTM parameters
- Parameters are appended to the selected variant URL
- Tracking maintains UTM source attribution

**Geo-targeting Compatibility**
- Geo rules can override A/B test selections
- Priority: Geo rules → A/B test → Original URL
- Allows for geographic customization of test variants

**Performance Impact**
- Variant selection adds ~1-2ms to redirect time
- Uses cached link data to minimize database queries
- Click tracking remains asynchronous

#### Use Cases
1. **Landing Page Optimization**: Test different landing pages to improve conversion rates
2. **Content Variations**: Compare different content approaches for the same campaign
3. **Design Testing**: Test different UI/UX approaches
4. **Offer Comparison**: Test different promotional offers or pricing pages
5. **Audience Segmentation**: Combined with geo-targeting for region-specific tests

#### Best Practices
- Run tests for statistical significance (100+ clicks recommended)
- Keep variant weights balanced initially (50/50 for two variants)
- Document test hypotheses and expected outcomes
- Monitor conversion data beyond just click counts
- Schedule tests during consistent traffic periods

## Development Phases

### Phase 1: Foundation Setup ✅ COMPLETED
1. **Laravel Installation** ✅
   - Installed Laravel 12.x in `/mnt/c/laragon/www/redirection`
   - Used temporary directory approach due to non-empty target directory

2. **Configure SQLite** ✅
   - Updated `.env` for SQLite database connection
   - Enabled pdo_sqlite and sqlite3 extensions in php.ini
   - Database location: `database/database.sqlite`

3. **Install Filament & Shield** ✅
   - Installed Filament 3.x admin panel
   - Configured Filament Shield for role-based permissions
   - Created roles: super_admin, admin, user
   - Admin accessible at `/admin`

4. **Configure Laragon Virtual Host** ✅
   - Set up redirection.test virtual host
   - SSL certificate working properly

5. **Geolocation Setup** ✅
   - Installed geoip2/geoip2 package
   - Downloaded MaxMind GeoLite2 database (58.31 MB)
   - Created `php artisan geoip:update` command
   - License key configured in .env: `MAXMIND_LICENSE_KEY`
   - GeolocationService working with graceful fallbacks

### Phase 2: Core Models & Migrations ✅ COMPLETED
1. **Database Structure** ✅
   - Created all migrations: links, link_groups, clicks, api_keys
   - Set up model relationships (User->Links, Link->Clicks, etc.)
   - Configured Filament Shield permissions successfully

2. **Basic Filament Resources** ✅
   - UserResource: Complete user management with role assignment
   - LinkGroupResource: Category management with color coding
   - LinksResource: Full CRUD with custom slug support and click statistics
   - ApiKeyResource: API key generation with permission scoping
   - ClicksRelationManager: Geographic click analytics display

### Phase 3: Redirection Engine ✅ COMPLETED
1. **Fast Redirect Controller** ✅
   ```php
   // Route: GET /{shortCode} with rate limiting middleware
   public function redirect(string $shortCode)
   {
       // Check file cache first
       $cacheKey = "link_{$shortCode}";
       $link = Cache::remember($cacheKey, 3600, function () use ($shortCode) {
           return DB::selectOne(
               'SELECT id, original_url, redirect_type FROM links 
                WHERE short_code = ? AND is_active = 1 
                AND (expires_at IS NULL OR expires_at > NOW())',
               [$shortCode]
           );
       });
       
       if (!$link) {
           abort(404);
       }
       
       // Log click asynchronously
       LogClickJob::dispatch([
           'link_id' => $link->id,
           'ip_address' => request()->ip(),
           'user_agent' => request()->userAgent(),
           'referer' => request()->header('referer'),
           'clicked_at' => now()
       ])->onQueue('clicks');
       
       // Increment counter asynchronously
       DB::table('links')->where('id', $link->id)->increment('click_count');
       
       return redirect($link->original_url, $link->redirect_type);
   }
   ```

2. **Click Tracking** ✅
   - LogClickJob implemented for async click logging
   - IP geolocation fully integrated with MaxMind database
   - Click data includes: IP, user agent, referer, country, city, timestamp
   - Rate limiting working: 60 requests/minute per IP

### Phase 4: API Development ✅ FULLY COMPLETED
1. **Sanctum Setup** ✅ COMPLETED
   - Laravel Sanctum installed and configured
   - Migrations published and run successfully
   - Custom API key authentication implemented

2. **API Controllers & Routes** ✅ COMPLETED
   - Complete LinkController with all CRUD operations
   - Permission-based middleware for fine-grained access control
   - Rate limiting implemented and tested
   - Comprehensive validation and error handling

3. **API Key Management** ✅ COMPLETED
   - Filament resource for API keys working perfectly
   - Permission scoping implemented (links:create, links:read, etc.)
   - Key generation with `sk_` prefix
   - **IMPROVED**: Keys now visible and copyable (not hidden after creation)
   - Multiple authentication methods (Bearer token, X-API-Key header, query param)

### Phase 5: Analytics & Reporting ✅ FULLY COMPLETED
1. **Internal Analytics** ✅ COMPLETED
   - **Comprehensive Dashboard** ✅ 4 custom widgets with real-time insights
   - **OverviewStatsWidget** ✅ Total links, clicks, averages, and daily trends
   - **ClickTrendsChart** ✅ Interactive line chart with 7/30/90-day filters
   - **TopLinksWidget** ✅ Most clicked links table with performance metrics
   - **GeographicStatsWidget** ✅ Improved with localhost-friendly messaging
   - ClicksRelationManager shows detailed click data with country/city/browser
   - Link statistics API endpoint with geographic breakdown
   - Time-based analytics (today, this week, total)
   - Export functionality: ❌ TODO - CSV/JSON export (optional enhancement)

2. **Google Analytics Integration** ❌ TODO (FUTURE ENHANCEMENT)
   - Measurement Protocol implementation
   - Server-side container setup  
   - Event tracking

### Phase 6: Advanced Features (Future)
1. **A/B Testing**
   - Multiple URLs per short code
   - Traffic splitting logic
   - Performance comparison

2. **Geo-targeting**
   - IP-based location detection using MaxMind
   - Geographic redirect rules
   - Country/region targeting
   - Graceful fallback when geolocation unavailable

## File Structure Considerations
```
app/
├── Http/Controllers/
│   ├── Api/LinkController.php
│   └── RedirectController.php
├── Filament/Resources/
│   ├── UserResource.php
│   ├── LinkResource.php
│   └── LinkGroupResource.php
├── Models/
│   ├── Link.php
│   ├── LinkGroup.php
│   ├── Click.php
│   └── ApiKey.php
├── Jobs/
│   ├── LogClickJob.php
│   └── SendAnalyticsJob.php
└── Services/
    ├── LinkShortenerService.php
    ├── GeolocationService.php
    └── AnalyticsService.php
```

## Geolocation Implementation

### MaxMind GeoLite2 Setup
```php
// GeolocationService.php
class GeolocationService
{
    private $reader;
    
    public function __construct()
    {
        $dbPath = storage_path('app/geoip/GeoLite2-City.mmdb');
        
        if (file_exists($dbPath)) {
            try {
                $this->reader = new \GeoIp2\Database\Reader($dbPath);
            } catch (Exception $e) {
                Log::warning('GeoIP database unavailable: ' . $e->getMessage());
                $this->reader = null;
            }
        }
    }
    
    public function getLocation(string $ip): array
    {
        if (!$this->reader) {
            return ['country' => null, 'city' => null];
        }
        
        try {
            $record = $this->reader->city($ip);
            return [
                'country' => $record->country->name,
                'city' => $record->city->name
            ];
        } catch (Exception $e) {
            return ['country' => null, 'city' => null];
        }
    }
    
    public function isAvailable(): bool
    {
        return $this->reader !== null;
    }
}
```

## Short Code Generation Implementation

### LinkShortenerService
```php
// LinkShortenerService.php
class LinkShortenerService
{
    const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const DEFAULT_LENGTH = 6;
    
    public function generateShortCode(?string $customSlug = null): string
    {
        if ($customSlug) {
            return $this->processCustomSlug($customSlug);
        }
        
        return $this->generateRandomCode();
    }
    
    private function processCustomSlug(string $slug): string
    {
        // Convert to lowercase and replace spaces with hyphens
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/\s+/', '-', $slug);
        
        // Remove any characters that aren't alphanumeric, hyphens, or underscores
        $slug = preg_replace('/[^a-z0-9\-_]/', '', $slug);
        
        // Remove multiple consecutive hyphens
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Trim hyphens from beginning and end
        $slug = trim($slug, '-');
        
        if (empty($slug)) {
            throw new \InvalidArgumentException('Invalid slug provided');
        }
        
        return $slug;
    }
    
    private function generateRandomCode(): string
    {
        $code = '';
        $maxIndex = strlen(self::ALPHABET) - 1;
        
        for ($i = 0; $i < self::DEFAULT_LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, $maxIndex)];
        }
        
        return $code;
    }
    
    public function ensureUnique(string $code): bool
    {
        return !DB::table('links')->where('short_code', $code)->exists();
    }
    
    public function generateUniqueCode(?string $customSlug = null): string
    {
        $attempts = 0;
        $maxAttempts = 10;
        
        do {
            $code = $this->generateShortCode($customSlug);
            $attempts++;
            
            if ($this->ensureUnique($code)) {
                return $code;
            }
            
            // If custom slug is taken, throw exception
            if ($customSlug) {
                throw new \Exception('This custom URL is already taken');
            }
            
        } while ($attempts < $maxAttempts);
        
        throw new \Exception('Unable to generate unique short code');
    }
}
```

## Link Health Monitoring System

### Overview
The link health monitoring system automatically checks all destination URLs to ensure they're still accessible, helping maintain link quality at scale.

### Health Status Categories
- **Unchecked** - Never been checked (gray icon)
- **Healthy** - HTTP 200-299 responses (green check)
- **Warning** - HTTP 300-399 or redirected to different domain (yellow triangle)
- **Error** - HTTP 400-599 or connection failed (red X)

### Smart Scheduling Algorithm
Links are checked based on their current status:
- **Healthy links**: Checked weekly
- **Warning links**: Checked every 3 days
- **Error links**: Checked daily
- **New links**: Checked immediately

### Database Schema
```sql
-- Health check fields added to links table
last_checked_at: timestamp nullable
health_status: enum ['healthy', 'warning', 'error', 'unchecked']
http_status_code: integer nullable
health_check_message: string nullable
final_url: string nullable (tracks redirects)
```

### Job Queue Implementation
```php
// CheckLinkHealthJob.php
- Processes individual link checks
- 30-second timeout per check
- Follows up to 10 redirects
- Detects domain changes
- Handles connection errors gracefully
- Uses separate 'health-checks' queue
```

### Command Line Interface
```bash
# Check links needing it (smart scheduling)
php artisan links:check-health

# Check with custom batch size
php artisan links:check-health --batch=100

# Force check all links
php artisan links:check-health --all

# Check only links with specific status
php artisan links:check-health --status=error
```

### Admin Interface Features
1. **Health Status Column** - Visual icons with colors
2. **Hover Tooltips** - Shows last check time and message
3. **Health Filter** - Filter by status (healthy/warning/error)
4. **Manual Check Action** - Check individual links on demand
5. **Bulk Check Action** - Check multiple selected links
6. **Dashboard Widget** - Overview of link health across system

### Queue Worker Configuration
```bash
# Process both health checks and click tracking
php artisan queue:work --queue=health-checks,clicks

# Dedicated health check worker
php artisan queue:work --queue=health-checks --sleep=3
```

### Cron Job Setup
```cron
# Run health checks daily at 2 AM
0 2 * * * cd /path/to/project && php artisan links:check-health >> /dev/null 2>&1

# For shared hosting (every 4 hours)
0 */4 * * * cd /path/to/project && php artisan links:check-health --batch=50 >> /dev/null 2>&1
```

### Performance Considerations
- Health checks run in separate queue to not block redirects
- Batch processing prevents overwhelming external servers
- Smart scheduling reduces unnecessary checks
- Failed jobs retry up to 3 times
- Database indexed on health_status and last_checked_at

## Performance Considerations
```bash
# Artisan command for updating GeoIP database
php artisan geoip:update

# Or manual download from MaxMind
# Place GeoLite2-City.mmdb in storage/app/geoip/
```


1. **Database Indexing**
   - Index on `short_code` (unique)
   - Index on `is_active` for active links
   - Composite index on frequently queried combinations

2. **Caching Strategy**
   - Cache popular links in Redis/memory
   - Cache analytics data for dashboards

3. **Queue Management**
   - Async processing for click logging
   - Background analytics submission
   - Bulk operations for large datasets

## Queue Processing Configuration

### Overview
The application uses Laravel's queue system to process click tracking asynchronously, improving redirect performance. Currently configured to use the `database` driver by default.

### Configuration Options

#### 1. Synchronous Processing (Simple, No Setup Required)
```env
QUEUE_CONNECTION=sync
```
- Jobs execute immediately during the request
- No additional processes needed
- Suitable for low-traffic sites
- Slightly slower redirects due to inline processing

#### 2. Database Queue (Recommended for Most Sites)
```env
QUEUE_CONNECTION=database
```
- Jobs stored in `jobs` table and processed separately
- Requires queue worker to be running
- Better performance for redirects
- Suitable for medium to high traffic

**Running the Queue Worker:**
```bash
# Process jobs from the 'clicks' queue
php artisan queue:work --queue=clicks --sleep=3 --tries=3

# Or process all queues
php artisan queue:work
```

#### 3. Production Setup Options

**Option A: Supervisor (VPS/Dedicated Servers)**
Create `/etc/supervisor/conf.d/laravel-worker.conf`:
```ini
[program:redirection-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=clicks --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/logs/worker.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start redirection-worker:*
```

**Option B: Systemd Service (Modern Linux Servers)**
Create `/etc/systemd/system/redirection-queue.service`:
```ini
[Unit]
Description=Redirection Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /path/to/artisan queue:work --queue=clicks --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

Then:
```bash
sudo systemctl enable redirection-queue
sudo systemctl start redirection-queue
```

**Option C: Cron Job (Shared Hosting)**
Add to crontab (runs every minute):
```cron
* * * * * cd /path/to/project && php artisan queue:work --queue=clicks --stop-when-empty --max-time=59 >> /dev/null 2>&1
```

This approach:
- Runs every minute via cron
- Processes any pending jobs
- Stops when queue is empty
- Maximum runtime of 59 seconds (to avoid overlap)
- Suitable for shared hosting environments

**Alternative for very limited hosting:**
```cron
*/5 * * * * cd /path/to/project && php artisan queue:work --queue=clicks --stop-when-empty --max-jobs=10 >> /dev/null 2>&1
```
- Runs every 5 minutes
- Processes maximum 10 jobs per run
- Less resource intensive

### Monitoring Queue Health

Check queue status:
```bash
php artisan queue:monitor clicks:100
```

Failed jobs:
```bash
php artisan queue:failed
php artisan queue:retry all
```

### Performance Impact

- **Sync mode**: ~50-100ms added to redirect time
- **Queue mode**: ~5-10ms added to redirect time
- Queue processing happens in background, not affecting user experience

## Testing Strategy
1. **Feature Tests**
   - Redirect functionality
   - API endpoints
   - Admin panel operations

2. **Performance Tests**
   - Redirect response times
   - Database query optimization
   - Load testing for concurrent redirects

## Deployment Considerations
- Environment-specific configurations
- Database migrations
- Queue worker setup
- Analytics configuration
- SSL certificate for production

## Learning Objectives
- Laravel best practices and conventions
- Filament admin panel development
- Performance optimization techniques
- API design and authentication
- Real-world application architecture

This project will provide hands-on experience with Laravel and Filament while building a practical application that demonstrates key web development concepts including performance optimization, user management, and API design.

---

## ✅ IMPLEMENTATION STATUS & LESSONS LEARNED

### Completed Features (Working in Production)
1. **Core Redirect System** - Fast, cached redirects with rate limiting
2. **Admin Panel** - Complete Filament interface with enhanced user management
3. **Link Management** - CRUD operations, categories, custom slugs, expiration, health monitoring
4. **Geographic Analytics** - MaxMind GeoLite2 integration working perfectly
5. **Complete REST API** - All CRUD endpoints with permission-based authentication
6. **API Key System** - User-friendly key management with visible keys
7. **Advanced Dashboard** - 5 custom widgets with dynamic titles and date filters
8. **User Profile Management** - Password changes and preferences from user menu
9. **QR Code Generation** - Enhanced QR codes with Filament-styled buttons in modals and edit views
10. **Click Analytics** - Detailed geographic, browser, and time-based statistics
11. **Clean Navigation** - Organized menu with roles under Settings (no separate Shield group)
12. **Comprehensive Testing** - 101 tests with 93+ passing covering all functionality
13. **Groups API** - Full CRUD operations for link groups via API
14. **Default Group System** - Automatic group assignment for new links (admin & API consistent)
15. **Queue Documentation** - Complete guide for async click processing
16. **Link Health Monitoring** - Smart health checks with redirect loop detection (warnings vs errors)
17. **Role-Based Permissions** - Complete Filament Shield integration with multi-role assignment
18. **User Role Management** - Multi-select role editing UI (users can have multiple roles)
19. **Extensibility System** - Configuration-based customization with events and service providers
20. **Role Setup Command** - `php artisan roles:setup` for automatic permission configuration
21. **Dashboard Customization** - Reordered widgets with category color badges
22. **Simple API Endpoint** - YOURLS-compatible `/api/simple` for legacy integrations
23. **SVG Favicon** - Consistent branding with heroicon link icon
24. **Enhanced Link Table** - Toggle columns, bulk actions (activate/deactivate), improved UX
25. **Improved Actions** - Edit action first, proper QR modal, streamlined interface
26. **Active Link Monitoring** - Health checks only monitor active links (no false alarms)
27. **Redirect After Save** - All edit/create forms redirect to list for better workflow
28. **Custom CSS Integration** - Proper Tailwind utilities without preflight conflicts
29. **Geo-targeting System** - Location-based redirects with country, continent, and custom region support
30. **UTM Parameter Tracking** - Complete UTM pass-through and tracking with dashboard analytics
31. **A/B Testing System** - Complete A/B testing with weighted variants, time-based scheduling, and performance analytics

### Key Technical Insights
1. **WSL/Windows Integration**: Use Windows cmd.exe for PHP commands when needed
2. **SQLite Extensions**: Must enable pdo_sqlite and sqlite3 in php.ini
3. **Filament Casts**: JSON columns need proper model casts for complex data
4. **Performance**: File caching + raw SQL for redirects = sub-100ms response times
5. **Testing**: RefreshDatabase trait essential for clean test isolation
6. **Filament Customization**: Override vendor translations for navigation changes
7. **Dynamic Widgets**: Chart widgets support filters, table widgets need different approach
8. **Permissions Generation**: `shield:generate --all` creates permissions but doesn't assign them
9. **Role Management**: Super admin bypasses all checks, others need explicit permissions
10. **Color Contrast**: Calculate readable text colors for any background dynamically
11. **Tailwind Integration**: Use utilities-only import to avoid conflicts with Filament's base styles
12. **Toggle Columns**: Use ToggleColumn instead of IconColumn for interactive table elements
13. **Filament Components**: Use `<x-filament::button>` for consistent styling in custom views
14. **Health Check Categorization**: Distinguish redirect loops (warnings) from connection errors
15. **Multi-role Support**: Use syncRoles() for proper multiple role assignment

### Remaining Features from Original Plan
1. **Export Functionality** - CSV/JSON export for analytics data (Phase 5 remainder)
2. **Google Analytics Integration** - Server-side event tracking (Phase 5.2)

### TODO: Documentation Improvements
1. **Queue System Instructions** - Review README and consolidate queue setup instructions. Currently the health check section shows `php artisan queue:work --queue=health-checks,clicks` but other sections only mention clicks queue. Should standardize to just `php artisan queue:work` without specifying queues, as Laravel will process all queues by default. This simplifies instructions and ensures all job types are processed.

### Additional Enhancement Ideas

**High Value & Quick Wins:**
1. **Database Backup/Download** - Admin backup functionality for data portability

**Medium Priority:**
2. **Custom Domains** - Support for branded short domains
3. **Password Protection** - Secure links with passwords
4. **Link Scheduling** - Auto-activate/deactivate at specific times
5. **Bulk Operations** - Mass edit/delete links

**Advanced Features:**
6. **Link Preview/Thumbnails** - Generate website previews
7. **Webhooks** - Real-time click event notifications
8. **Team Management** - Workspaces and collaboration features
9. **Email Reports** - Scheduled analytics reports

### Environment Configuration Notes
- **Database**: SQLite working perfectly for development
- **Environment**: `.env.example` updated with all current configurations
- **Geolocation**: 58.31MB MaxMind database downloaded and functional
- **Admin URL**: https://redirection.test/admin
- **User Profile**: Accessible from user menu (top-right dropdown)
- **Dashboard**: 5 custom widgets with reorderable layout and dynamic date ranges
- **QR Codes**: Available in table view and edit screens with PNG/SVG downloads
- **Test Command**: `php artisan test` (93+ of 101 tests passing with 400+ assertions)
- **Role Setup**: `php artisan roles:setup` (automatic permission configuration)
- **GeoIP Update**: `php artisan geoip:update` (monthly recommended)
- **Health Checks**: `php artisan links:check-health` (daily recommended)
- **API Endpoints**: `/api/links` and `/api/groups` with full CRUD operations
- **API Authentication**: Multiple methods (Bearer, X-API-Key, query param)
- **Queue Processing**: Database driver by default, cron job example for shared hosting
- **Default Queue**: Set `QUEUE_CONNECTION=sync` for simple setup without workers
- **Health Check Queue**: Separate `health-checks` queue for link monitoring
