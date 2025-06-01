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
- **Comprehensive Dashboard** - 4 custom widgets with real-time insights
- **Click Trends Chart** - Interactive line graphs with 7/30/90-day filters
- **Top Links Widget** - Most clicked links with performance metrics
- **Geographic Analytics** - Country and city tracking using MaxMind GeoLite2
- **Performance Metrics** - Click rates, averages, and growth tracking
- **Browser Detection** - Track user agents and devices
- **Referrer Tracking** - See where clicks are coming from

### 🛡️ Admin & Security
- **Filament Admin Panel** - Modern, responsive admin interface
- **Role-based Permissions** - Super Admin, Staff, and Viewer roles
- **User Management** - Complete user administration
- **User Profile Settings** - Password changes and preferences from user menu
- **API Key Management** - Secure key generation with visible keys and easy copying
- **RESTful API** - Complete REST API with permission-based authentication
- **Rate Limiting** - Protection against abuse

### 🌍 Geographic Features
- **IP Geolocation** - Automatic location detection for clicks
- **Country Statistics** - Top countries dashboard widget
- **Location Filtering** - Filter clicks by geographic data
- **Coverage Metrics** - Track geographic data availability

### 📱 QR Code Features
- **Instant Generation** - QR codes available in both table view and edit screens
- **Multiple Formats** - Download as PNG (200px, 400px) or SVG (vector)
- **Cross-Platform UX** - Clear download buttons work on all devices
- **Professional Quality** - High-resolution codes perfect for print materials

<!-- TODO: Add screenshots when available
## Screenshots
- Admin Dashboard with real-time statistics
- Link management with QR codes and analytics  
- Geographic analytics with country tracking
-->

## Installation

### Requirements
- PHP 8.3+
- Composer
- SQLite (or MySQL/PostgreSQL)
- MaxMind GeoLite2 license key (free)

### Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/robwent/link-shortener.git
   cd link-shortener
   ```

2. **Install dependencies**
   ```bash
   composer install
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
   ```bash
   touch database/database.sqlite
   php artisan migrate
   ```

5. **Create admin user**
   ```bash
   php artisan make:filament-user
   ```

6. **Configure MaxMind GeoLite2 (Optional but recommended)**
   - Sign up for a free account at [MaxMind](https://www.maxmind.com/en/geolite2/signup)
   - Add your license key to the `MAXMIND_LICENSE_KEY` field in `.env`
   - Download the database:
     ```bash
     php artisan geoip:update
     ```
   - **Note:** Geographic features will gracefully degrade without this setup

7. **Start the application**
   ```bash
   php artisan serve
   ```

Visit `http://localhost:8000` to see the homepage and `http://localhost:8000/admin` for the admin panel.

8. **Run tests (optional)**
   ```bash
   php artisan test
   ```

## Usage

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

### Managing Geographic Data

Update the GeoLite2 database monthly:
```bash
php artisan geoip:update
```

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

**Note:** Leave permissions empty for full access to all endpoints.

**API Authentication Methods:**
```bash
# Method 1: Authorization Header (Recommended)
curl -X GET 'https://redirection.test/api/links' \
  -H 'Authorization: Bearer sk_your_api_key'

# Method 2: X-API-Key Header  
curl -X GET 'https://redirection.test/api/links' \
  -H 'X-API-Key: sk_your_api_key'

# Method 3: Query Parameter (Easy for testing)
curl -X GET 'https://redirection.test/api/links?api_key=sk_your_api_key'
```

### API Endpoints

| Method | Endpoint | Description | Permissions Required |
|--------|----------|-------------|---------------------|
| `POST` | `/api/links` | Create a new short link | `links:create` |
| `GET` | `/api/links` | List your links | `links:read` |
| `GET` | `/api/links/{id}` | Get link details | `links:read` |
| `PUT` | `/api/links/{id}` | Update a link | `links:update` |
| `DELETE` | `/api/links/{id}` | Delete a link | `links:delete` |
| `GET` | `/api/links/{id}/stats` | Get click statistics | `stats:read` |

## Development

This project serves as a learning exercise for:
- **Laravel 12.x** - Latest framework features and best practices
- **Filament 3.x** - Modern admin panel development
- **Performance Optimization** - Raw SQL for redirects, caching strategies
- **Geographic Services** - IP geolocation and mapping
- **API Design** - RESTful APIs with proper authentication

### Key Architecture Decisions

- **SQLite Database** - Simpler setup for development and small deployments
- **File-based Caching** - Fast access to frequently used links
- **Raw SQL for Redirects** - Maximum performance for the core feature
- **Async Click Logging** - Non-blocking analytics collection
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

## Performance

- **Sub-100ms redirects** using optimized database queries
- **File-based caching** for frequently accessed links
- **Async analytics** to avoid blocking redirects
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
- 55+ tests with 285+ assertions
- Core redirect functionality
- Complete API endpoint testing
- Link generation and validation
- Geographic data processing
- Click tracking and analytics
- API authentication and permissions
- User profile functionality
- Dashboard widgets and analytics
- QR code generation and downloads
- Model relationships and business logic

## Future Enhancements

### High Value & Quick Wins
- **UTM Parameter Tracking** - Campaign and source tracking (high business value)
- **Database Backup/Download** - Admin backup functionality for data portability
- **Export Functionality** - CSV/JSON export for analytics data

### Advanced Features
- **Custom Domains** - Support for branded short domains
- **Password Protection** - Secure links with passwords
- **A/B Testing** - Multiple destination URLs with traffic splitting
- **Geo-targeting** - Location-based redirects
- **Webhooks** - Real-time click event notifications

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
