# Link Shortener

A modern URL shortening service built with Laravel and Filament, featuring geographic analytics, role-based permissions, and a powerful admin interface.

## Features

### 🔗 Core Functionality
- **Fast URL Shortening** - Generate custom or automatic short codes
- **Multiple Redirect Types** - Support for 301, 302, 307, and 308 redirects
- **Link Categories** - Organize links with color-coded groups
- **Expiration Dates** - Set automatic link expiration
- **Custom Slugs** - Create memorable short URLs
- **QR Code Generation** - Instant QR codes with multiple download formats

### 📊 Analytics & Tracking
- **Comprehensive Dashboard** - 6 custom widgets with real-time insights
- **Click Trends Chart** - Interactive line graphs with 7/30/90-day filters
- **Top Links Widget** - Most clicked links with performance metrics
- **Geographic Analytics** - Country and city tracking using MaxMind GeoLite2
- **UTM Campaign Tracking** - Automatic UTM parameter pass-through and analytics
- **Campaign Performance Widget** - Top performing campaigns, sources, and mediums
- **Link Health Status** - Real-time monitoring of destination URL availability
- **Performance Metrics** - Click rates, averages, and growth tracking
- **Browser Detection** - Track user agents and devices
- **Referrer Tracking** - See where clicks are coming from

### 🛡️ Admin & Security
- **Filament Admin Panel** - Modern, responsive admin interface
- **Role-based Permissions** - Complete permission system with 4 roles:
  - `super_admin` - Unrestricted access to everything
  - `admin` - Limited permissions (configurable)
  - `user` - Basic role for regular users
  - `panel_user` - Basic panel access
- **User Management** - Complete user administration with role assignment
- **User Profile Settings** - Password changes and preferences from user menu
- **API Key Management** - Secure key generation with visible keys and easy copying
- **RESTful API** - Complete REST API with permission-based authentication
- **Rate Limiting** - Protection against abuse

### 🌍 Geographic Features
- **IP Geolocation** - Automatic location detection for clicks using MaxMind GeoLite2
- **Geo-Targeting Rules** - Redirect visitors to different URLs based on their location
- **Flexible Targeting** - Support for countries, continents, and custom regions
- **Custom Regions** - Pre-defined regions like GDPR Zone, Five Eyes, North America
- **Priority-Based Rules** - Multiple rules with priority ordering for complex scenarios
- **Country Statistics** - Top countries dashboard widget with click analytics
- **Location Filtering** - Filter clicks by geographic data in admin interface
- **Smart Caching** - Performance-optimized caching that doesn't interfere with geo-targeting

### 📈 UTM Campaign Tracking
- **Automatic Pass-Through** - UTM parameters added to short links are preserved and passed to destination URLs
- **Parameter Validation** - Only valid UTM parameters (source, medium, campaign, term, content) are processed
- **Smart URL Merging** - UTM parameters merge intelligently with existing query parameters
- **Campaign Analytics** - Track performance of email campaigns, social media, and paid ads
- **Dashboard Widget** - Real-time campaign performance overview with top sources and mediums
- **Click-Level Data** - Every click stores complete UTM attribution for detailed analysis
- **Filtering & Search** - Filter clicks by campaign, source, medium in admin interface
- **Email Marketing Ready** - Works seamlessly with MailChimp, Constant Contact, and other platforms

### 📱 QR Code Features
- **Instant Generation** - QR codes available in both table view and edit screens
- **Multiple Formats** - Download as PNG (200px, 400px) or SVG (vector)
- **Cross-Platform UX** - Clear download buttons work on all devices
- **Professional Quality** - High-resolution codes perfect for print materials

### 🔍 Link Health Monitoring
- **Automated Health Checks** - Periodic checking of destination URLs
- **Smart Scheduling** - Healthy links checked weekly, errors checked daily
- **Visual Status Indicators** - Color-coded icons show link health at a glance
- **Health Dashboard Widget** - Real-time overview of all link statuses
- **Manual Health Checks** - Check individual or bulk links on demand
- **Detailed Diagnostics** - HTTP status codes, redirect chains, and error messages
- **Queue-Based Processing** - Non-blocking health checks via job queue

### 🧪 A/B Testing
- **Multiple Destination URLs** - Test different landing pages for the same short link
- **Weighted Traffic Distribution** - Control percentage of traffic to each variant
- **Real-Time Performance Tracking** - Monitor click distribution across variants
- **Time-Based Scheduling** - Set start and end dates for tests
- **Dashboard Widget** - Overview of all active A/B tests with performance metrics
- **Statistical Insights** - Identify leading variants and track performance
- **UTM Compatible** - Works seamlessly with UTM parameter tracking
- **Geo-Targeting Compatible** - Combine with location-based rules for advanced targeting

### 🔒 Security & Access Control
- **Password Protection** - Secure links with password entry before redirect
- **Click Limits** - Automatically disable links after specified number of clicks
- **Session-Based Authentication** - Password entry persists across user sessions
- **Professional UI** - Clean password forms and limit exceeded pages
- **Real-Time Enforcement** - Security checks use live database data, not cached values
- **Admin Management** - Easy bulk operations, filtering, and click count resets
- **Performance Optimized** - Security checks only run for protected links

<!-- TODO: Add screenshots when available
## Screenshots
- Admin Dashboard with real-time statistics
- Link management with QR codes and analytics  
- Geographic analytics with country tracking
-->

## Installation

**⚡ Quick Setup:** Just 5 commands to get running! The automated installer handles all the complex setup for you.

### Requirements
- PHP 8.3+
- Composer
- MySQL 8.0+ or SQLite 3.8.8+
- MaxMind GeoLite2 license key (free, optional but recommended)

### Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/robwent/link-shortener.git
   cd link-shortener
   ```

2. **Install dependencies**
   ```bash
   # For development
   composer install
   
   # For production (smaller footprint)
   composer install --no-dev --optimize-autoloader
   ```

3. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   
   **Configure your environment:**
   - Set `APP_URL` to your domain (e.g., `https://yourdomain.com`)
   - Add your MaxMind license key to `MAXMIND_LICENSE_KEY`

4. **Database setup**
   
   **For SQLite:**
   ```bash
   touch database/database.sqlite
   ```
   
   **For MySQL:**
   ```bash
   # Update .env with your MySQL credentials:
   # DB_CONNECTION=mysql
   # DB_HOST=127.0.0.1
   # DB_PORT=3306
   # DB_DATABASE=your_database
   # DB_USERNAME=your_username
   # DB_PASSWORD=your_password
   ```

5. **Run the automated installer**
   
   ```bash
   php artisan app:install
   ```
   
   That's it! The installer will:
   - ✅ Publish all required configurations and translations
   - ✅ Run database migrations automatically
   - ✅ Install Filament Shield with proper navigation grouping
   - ✅ Create your admin user (you'll be prompted for details)
   - ✅ Set up all roles and permissions automatically
   - ✅ Configure the admin panel with "Settings" menu organization
   - ✅ Clear caches and optimize the application
   
   **You're ready to go!** Login to `/admin` with the credentials you provided.

6. **Access the application**

   Visit your configured domain to see the homepage and `/admin` for the admin panel.

7. **Configure MaxMind GeoLite2 (Optional but recommended)**
   - Sign up for a free account at [MaxMind](https://www.maxmind.com/en/geolite2/signup)
   - Add your license key to the `MAXMIND_LICENSE_KEY` field in `.env`
   - Download the database:
     ```bash
     php artisan geoip:update
     ```
   - **Note:** Geographic features will gracefully degrade without this setup

8. **Configure Queue Processing (Optional for better performance)**

   The application uses queues for async click tracking. Choose one of these options:

   **Option A: Synchronous Processing (Simple, No Setup)**
   ```bash
   # In your .env file, set:
   QUEUE_CONNECTION=sync
   ```
   
   **Option B: Database Queue with Worker (Recommended)**
   ```bash
   # In your .env file, set:
   QUEUE_CONNECTION=database
   
   # Run the queue worker:
   php artisan queue:work --queue=default,clicks,health-checks
   ```
   
   **Option C: Cron Job for Shared Hosting**
   ```bash
   # Add to your crontab:
   * * * * * cd /path/to/project && php artisan queue:work --queue=default,clicks,health-checks --stop-when-empty --max-time=59 >> /dev/null 2>&1
   ```
   
   See the [Queue Processing](#queue-processing) section for detailed setup instructions.

9. **Production optimization (recommended for live servers)**
   ```bash
   # Cache configuration
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   
   # Optimize Filament
   php artisan filament:cache-components
   
   # Optimize autoloader (if not done during composer install)
   composer dump-autoload --optimize
   ```

10. **Run tests (optional)**
    ```bash
    php artisan test
    ```

## Usage

### User Management

Once you have a super admin account set up, you can manage other users:

**Creating Additional Users:**
1. Login to `/admin` with your super admin account
2. Navigate to "Settings" → "Users"
3. Click "Create User"
4. Fill in name, email, password
5. Select a role (`admin`, `user`, or `panel_user`)
6. Toggle "Email Verified" if needed
7. Save to create the user

**Role Permissions:**
- **Super Admin**: Can do everything, manage all users and roles
- **Admin**: Limited permissions based on what you assign in "Settings" → "Roles"
- **User**: Basic role, assign permissions as needed
- **Panel User**: Basic panel access

**Managing Roles:**
1. Go to "Settings" → "Roles"
2. Click on a role name (e.g., "admin") 
3. Check/uncheck permissions for that role
4. Users with that role will immediately have those permissions

**Important Security Notes:**
- Only super admins can assign the `super_admin` role
- Super admins cannot delete themselves or other super admins
- Regular admins cannot see or assign super admin permissions

### Creating Short Links

**Via Admin Panel:**
1. Login to `/admin`
2. Navigate to "Links" → "Create"
3. Enter the destination URL
4. Optionally set a custom slug, category, and expiration
5. Save to generate your short link

**Via API:**
```bash
curl -X POST http://localhost:8000/api/links \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "original_url": "https://example.com/very-long-url",
    "custom_slug": "my-link",
    "group_id": 1,
    "expires_at": "2024-12-31T23:59:59Z"
  }'
```

### Geographic Features

**Setting up Geolocation:**
The application requires a MaxMind GeoLite2 database for geographic features. Get a free license key from MaxMind and add it to your `.env`:
```env
MAXMIND_LICENSE_KEY=your_license_key_here
```

Then download the database:
```bash
php artisan geoip:update
```

**Geo-Targeting Rules:**
1. Edit any link in the admin panel
2. Go to the "Geo-Targeting Rules" tab
3. Create rules to redirect visitors based on:
   - **Countries** - Target specific countries (US, CA, GB, etc.)
   - **Continents** - Target entire continents (EU, NA, AS, etc.)
   - **Custom Regions** - Pre-defined groups like GDPR Zone, Five Eyes

**Example Use Cases:**
- Privacy policies: EU visitors → GDPR-compliant page
- Language targeting: Spanish speakers → Spanish content
- Compliance: Financial services → region-specific disclaimers
- Marketing: Different landing pages for different markets

**Priority System:**
Rules are evaluated in priority order (lower number = higher priority). First matching rule wins.

**Database Maintenance:**
Update the GeoLite2 database monthly for accuracy:
```bash
php artisan geoip:update
```

### UTM Campaign Tracking

**How UTM Pass-Through Works:**
UTM parameters added to your short links are automatically passed through to the destination URL, enabling end-to-end campaign tracking.

**Example Flow:**
```
Original Link: https://short.ly/product
With UTM: https://short.ly/product?utm_source=newsletter&utm_medium=email&utm_campaign=spring2024
Destination: https://example.com/product?utm_source=newsletter&utm_medium=email&utm_campaign=spring2024
```

**Supported UTM Parameters:**
- `utm_source` - Traffic source (newsletter, google, facebook)
- `utm_medium` - Marketing medium (email, social, cpc)
- `utm_campaign` - Campaign name (spring2024, black_friday)
- `utm_term` - Paid search keywords
- `utm_content` - A/B test content variation

**Real-World Use Cases:**
- **Email Marketing**: MailChimp automatically adds UTM tags → track email performance
- **Social Media**: Hootsuite/Buffer campaigns → measure social ROI
- **Paid Advertising**: Google Ads/Facebook → attribution across platforms
- **Cross-Channel**: Compare performance across email, social, and paid channels

**Analytics Dashboard:**
1. View "Campaign Performance" widget on dashboard
2. See top campaigns, sources, and mediums with click counts
3. Filter by date range (today, this week, this month)
4. Click into individual links to see detailed UTM breakdown

**Advanced Features:**
- **Parameter Merging**: UTM parameters merge with existing URL query parameters
- **Override Protection**: New UTM parameters take precedence over existing ones
- **Validation**: Only valid UTM parameters are processed and stored
- **Geo-Targeting Compatible**: Works seamlessly with location-based redirects

**Default UTM Parameters (Built-in Solution):**
You don't need a separate "default UTM parameters" feature - just include them in your destination URLs! The system intelligently merges parameters:

```
Destination URL: https://shop.com/sale?utm_source=website&utm_campaign=spring2024
Newsletter Link: https://short.ly/sale?utm_source=newsletter&utm_medium=email
Final Result: https://shop.com/sale?utm_source=newsletter&utm_campaign=spring2024&utm_medium=email
```

This approach is more flexible and follows standard marketing practices. Campaign-specific UTM parameters override defaults while preserving other values.

### Link Health Monitoring

**Automated Health Checks:**
```bash
# Check links that need it (based on smart scheduling)
php artisan links:check-health

# Process 100 links at a time
php artisan links:check-health --batch=100

# Force check all links
php artisan links:check-health --all

# Check only error links
php artisan links:check-health --status=error
```

**Setting up Automated Checks:**
Add to your crontab:
```cron
# Run health checks daily at 2 AM
0 2 * * * cd /path/to/project && php artisan links:check-health >> /dev/null 2>&1
```

**Processing Jobs:**
The queue worker will automatically process both click tracking and health check jobs:
```bash
php artisan queue:work --queue=default,clicks,health-checks
```

### Role Permission Management

**Set up default permissions for all roles:**
```bash
php artisan roles:setup
```

**Reset and reconfigure specific roles:**
```bash
# Reset admin role permissions and apply defaults
php artisan roles:setup --reset --role=admin

# Set up only user and panel_user roles
php artisan roles:setup --role=user --role=panel_user
```

**Default Permission Assignments:**
- **super_admin**: All permissions (automatic, cannot be changed)
- **admin**: Full link management, groups, API keys, all dashboard widgets
- **user**: Basic link management, view groups, limited dashboard widgets
- **panel_user**: View-only access to own links and profile

**Manual Permission Management:**
You can always customize permissions in the admin panel at "Settings" → "Roles".

### API Key Management

**Creating API Keys:**
1. Login to admin panel at `/admin`
2. Navigate to "System" → "API Keys"
3. Click "Create API Key"
4. Set name, permissions, and optional expiration
5. Click on the key in the table to copy it (keys remain visible for easy access)

**Available Permissions:**
- `links:create` - Create new short links
- `links:read` - View existing links
- `links:update` - Modify existing links  
- `links:delete` - Delete links
- `stats:read` - Access click statistics
- `groups:create` - Create new groups
- `groups:read` - View existing groups
- `groups:update` - Modify existing groups
- `groups:delete` - Delete groups

**Note:** Leave permissions empty for full access to all endpoints.

**API Authentication Methods:**
```bash
# Method 1: Authorization Header (Recommended)
curl -X GET 'https://example.com/api/links' \
  -H 'Authorization: Bearer sk_your_api_key'

# Method 2: X-API-Key Header  
curl -X GET 'https://example.com/api/links' \
  -H 'X-API-Key: sk_your_api_key'

# Method 3: Query Parameter (Easy for testing)
curl -X GET 'https://example.com/api/links?api_key=sk_your_api_key'
```

### API Endpoints

**Links API:**
| Method | Endpoint | Description | Permissions Required |
|--------|----------|-------------|---------------------|
| `POST` | `/api/links` | Create a new short link | `links:create` |
| `GET` | `/api/links` | List your links | `links:read` |
| `GET` | `/api/links/{id}` | Get link details | `links:read` |
| `PUT` | `/api/links/{id}` | Update a link | `links:update` |
| `DELETE` | `/api/links/{id}` | Delete a link | `links:delete` |
| `GET` | `/api/links/{id}/stats` | Get click statistics | `stats:read` |

**Groups API:**
| Method | Endpoint | Description | Permissions Required |
|--------|----------|-------------|---------------------|
| `GET` | `/api/groups` | List all groups | `groups:read` |
| `GET` | `/api/groups/{id}` | Get group details | `groups:read` |
| `POST` | `/api/groups` | Create a new group | `groups:create` |
| `PUT` | `/api/groups/{id}` | Update a group | `groups:update` |
| `DELETE` | `/api/groups/{id}` | Delete a group | `groups:delete` |

**Special Parameters:**
- `GET /api/groups?simple=true` - Returns simplified list for dropdowns
- `POST/PUT` with `"is_default": true` - Sets group as default for new links

## Development

This project serves as a learning exercise for:
- **Laravel 12.x** - Latest framework features and best practices
- **Filament 3.x** - Modern admin panel development
- **Performance Optimization** - Raw SQL for redirects, caching strategies
- **Geographic Services** - IP geolocation and mapping
- **API Design** - RESTful APIs with proper authentication

### Key Architecture Decisions

- **Database Flexibility** - Supports both MySQL and SQLite for different deployment scenarios
- **File-based Caching** - Fast access to frequently used links
- **Raw SQL for Redirects** - Maximum performance for the core feature
- **Async Click Logging** - Non-blocking analytics collection via queues
- **Graceful Geolocation** - Works with or without MaxMind database

### File Structure

```
app/
├── Http/Controllers/
│   ├── Api/LinkController.php      # API endpoints
│   └── RedirectController.php      # Fast redirect handler
├── Filament/
│   ├── Resources/                  # Admin panel resources
│   └── Widgets/                    # Dashboard widgets
├── Models/
│   ├── Link.php                    # Core link model
│   ├── Click.php                   # Analytics model
│   └── LinkGroup.php               # Categories
├── Jobs/
│   └── LogClickJob.php             # Async click logging
└── Services/
    ├── GeolocationService.php      # IP to location mapping
    └── LinkShortenerService.php    # URL generation
```

## Queue Processing

The application uses Laravel's queue system to process click tracking asynchronously, ensuring fast redirect performance. Click data is logged in the background without slowing down the redirect.

### Configuration Options

#### 1. Synchronous Processing (No Setup Required)
```env
QUEUE_CONNECTION=sync
```
- Jobs execute immediately during the request
- No additional processes needed
- Suitable for low-traffic sites
- Adds ~50-100ms to redirect time

#### 2. Database Queue (Recommended)
```env
QUEUE_CONNECTION=database
```

**Running the Worker:**
```bash
# Process all queue jobs (clicks, health checks, etc.)
php artisan queue:work --queue=default,clicks,health-checks --sleep=3 --tries=3
```

#### 3. Production Deployment Options

**Supervisor (VPS/Dedicated Servers):**
```ini
[program:redirection-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=default,clicks,health-checks --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/logs/worker.log
```

**Systemd Service (Modern Linux):**
```ini
[Unit]
Description=Redirection Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /path/to/artisan queue:work --queue=default,clicks,health-checks --sleep=3 --tries=3

[Install]
WantedBy=multi-user.target
```

**Cron Job (Shared Hosting):**
```cron
# Runs every minute (processes all queues)
* * * * * cd /path/to/project && php artisan queue:work --queue=default,clicks,health-checks --stop-when-empty --max-time=59 >> /dev/null 2>&1

# Alternative for limited hosting (every 5 minutes, max 10 jobs)
*/5 * * * * cd /path/to/project && php artisan queue:work --queue=default,clicks,health-checks --stop-when-empty --max-jobs=10 >> /dev/null 2>&1

# ISPConfig format (adjust path as needed)
* * * * * php /var/www/clients/client1/web1/web/artisan queue:work --queue=default,clicks,health-checks --tries=3 --stop-when-empty
```

### Monitoring Queue Health

```bash
# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear old jobs
php artisan queue:flush
```

## Performance

- **Sub-100ms redirects** using optimized database queries
- **File-based caching** for frequently accessed links
- **Async analytics** via queue system (adds only ~5-10ms to redirects)
- **Database indexing** on short codes and active status
- **Rate limiting** to prevent abuse

## Testing

The project includes a comprehensive test suite covering:

- **Feature Tests** - End-to-end redirect functionality, homepage, and user flows
- **Unit Tests** - Individual service classes, models, and business logic
- **Integration Tests** - Database relationships and geographic data handling

**Running Tests:**
```bash
# Run all tests
php artisan test

# Run specific test files
php artisan test --filter=RedirectTest
php artisan test --filter=LinkShortenerServiceTest

# Run with coverage (requires Xdebug)
php artisan test --coverage
```

**Test Coverage:**
- 80+ tests with 420+ assertions
- Core redirect functionality
- Complete API endpoint testing (links and groups)
- Link generation and validation
- Geographic data processing and geo-targeting rules
- UTM parameter pass-through and analytics tracking
- Click tracking and analytics
- API authentication and permissions
- User profile functionality
- Dashboard widgets and analytics
- QR code generation and downloads
- Model relationships and business logic
- Default group functionality
- Queue job processing
- Link health checking functionality
- Role permission management
- Custom Artisan commands
- Geo-targeting rule evaluation and priority handling

## Future Enhancements

### High Value & Quick Wins
- **CSV Import System** - Bulk link creation and migration from other services (comprehensive design complete, ready for implementation)
- **Google Analytics Integration** - Server-side event tracking

### Advanced Features
- **Link Scheduling** - Auto-activate/deactivate at specific times
- **Bulk Operations** - Mass edit/delete links
- **Webhooks** - Real-time click event notifications
- **Team Management** - Workspaces and collaboration features

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Acknowledgments

- **Laravel** - The amazing PHP framework
- **Filament** - Beautiful admin panel package
- **MaxMind** - GeoLite2 geographic database
- **Heroicons** - Clean, modern icons

---
