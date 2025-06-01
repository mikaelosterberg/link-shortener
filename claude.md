# Link Shortener Laravel Project

## Project Overview
A link shortening/redirection service built with Laravel and Filament to learn these technologies before starting a larger project. The app focuses on fast redirections, admin management, and API access.

## Operating Environment

### Development Setup
- **Host OS**: Windows with Laragon
- **Laravel App Location**: Running under Laragon on C: drive
- **Local URL**: https://redirection.test
- **PHP Path**: `C:\laragon\bin\php\php-8.3.15-Win32-vs16-x64\php.exe`
- **Database**: SQLite (simpler than MySQL for learning)
- **Claude Code**: Running on Windows Subsystem for Linux (WSL)

### Potential WSL/Windows Issues RESOLVED
- **File path handling**: Use Windows cmd.exe for PHP/Composer commands when needed
- **SQLite Extensions**: Required enabling `extension=pdo_sqlite` and `extension=sqlite3` in php.ini
- **Laragon Restart**: Needed after php.ini changes
- **File permissions**: No issues encountered with shared directories
- **Testing**: All 44 tests pass successfully in WSL environment

## Technology Stack
- **Backend**: Laravel 12.x (latest)
- **Admin Panel**: Filament 3.x (latest)
- **Authentication/Permissions**: Filament Shield
- **Database**: SQLite
- **Cache**: File-based (development), Redis (production)
- **API Authentication**: Laravel Sanctum
- **Analytics**: Server-side Google Analytics (Measurement Protocol)
- **Geolocation**: MaxMind GeoLite2 (optional, local database)

## Database Schema

### Core Tables
```sql
-- Users (managed by Filament Shield)
users: id, name, email, password, role, timestamps

-- Link groups/categories
link_groups: id, name, description, color, timestamps

-- Main links table
links: id, short_code, original_url, group_id, redirect_type, 
       is_active, expires_at, created_by, click_count, 
       custom_slug, timestamps

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
- Create/edit/delete admin users
- Assign roles: Super Admin, Staff, Viewer

**Link Management** (Standard Laravel/Filament)
- CRUD operations using Filament resources and Eloquent
- Bulk operations via Filament actions
- Link grouping/categorization
- Click statistics dashboard

**Groups/Categories**
- Organize links by purpose/campaign
- Color coding for visual organization

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
```
POST /api/links - Create short link
GET /api/links - List user's links
GET /api/links/{id} - Get link details
PUT /api/links/{id} - Update link
DELETE /api/links/{id} - Delete link
GET /api/links/{id}/stats - Get click statistics
```

### 4. Analytics Integration
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
2. **Admin Panel** - Complete Filament interface with user management
3. **Link Management** - CRUD operations, categories, custom slugs, expiration
4. **Geographic Analytics** - MaxMind GeoLite2 integration working perfectly
5. **Complete REST API** - All CRUD endpoints with permission-based authentication
6. **API Key System** - User-friendly key management with visible keys
7. **Advanced Dashboard** - 4 custom widgets with dynamic titles and date filters
8. **User Profile Management** - Password changes and preferences from user menu
9. **QR Code Generation** - Instant QR codes with PNG/SVG downloads from table and edit views
10. **Click Analytics** - Detailed geographic, browser, and time-based statistics
11. **Clean Navigation** - Organized menu with roles under Settings (no separate Shield group)
12. **Comprehensive Testing** - 55+ tests with 285+ assertions covering all functionality

### Key Technical Insights
1. **WSL/Windows Integration**: Use Windows cmd.exe for PHP commands when needed
2. **SQLite Extensions**: Must enable pdo_sqlite and sqlite3 in php.ini
3. **Filament Casts**: JSON columns need proper model casts for complex data
4. **Performance**: File caching + raw SQL for redirects = sub-100ms response times
5. **Testing**: RefreshDatabase trait essential for clean test isolation
6. **Filament Customization**: Override vendor translations for navigation changes
7. **Dynamic Widgets**: Chart widgets support filters, table widgets need different approach

### Remaining Features from Original Plan
1. **Export Functionality** - CSV/JSON export for analytics data (Phase 5 remainder)
2. **Google Analytics Integration** - Server-side event tracking (Phase 5.2)
3. **A/B Testing System** - Multiple destination URLs with traffic splitting (Phase 6.1)
4. **Geo-targeting Rules** - Location-based redirects (Phase 6.2)

### Additional Enhancement Ideas

**High Value & Quick Wins:**
1. **UTM Parameter Tracking** - Campaign and source tracking (⚡ High business value)
2. **Database Backup/Download** - Admin backup functionality for data portability

**Medium Priority:**
4. **Custom Domains** - Support for branded short domains
5. **Password Protection** - Secure links with passwords
6. **Link Scheduling** - Auto-activate/deactivate at specific times
7. **Bulk Operations** - Mass edit/delete links

**Advanced Features:**
8. **Link Preview/Thumbnails** - Generate website previews
9. **Webhooks** - Real-time click event notifications
10. **Team Management** - Workspaces and collaboration features
11. **Email Reports** - Scheduled analytics reports

### Environment Configuration Notes
- **Database**: SQLite working perfectly for development
- **Environment**: `.env.example` updated with all current configurations
- **Geolocation**: 58.31MB MaxMind database downloaded and functional
- **Admin URL**: https://redirection.test/admin
- **User Profile**: Accessible from user menu (top-right dropdown)
- **Dashboard**: 4 custom widgets with comprehensive analytics
- **QR Codes**: Available in table view and edit screens with PNG/SVG downloads
- **Test Command**: `php artisan test` (all 55+ tests passing with 285+ assertions)
- **GeoIP Update**: `php artisan geoip:update` (monthly recommended)
- **API Endpoints**: `/api/links` with full CRUD and statistics
- **API Authentication**: Multiple methods (Bearer, X-API-Key, query param)