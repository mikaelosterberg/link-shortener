# Link Shortener Laravel Project

## Project Overview
A modern URL shortening service built with Laravel and Filament, featuring advanced analytics, geographic targeting, and a powerful drag-and-drop report builder.

## Quick Links
- 📖 **[Complete Documentation](docs/README.md)** - Comprehensive docs organized by topic
- 🚀 **[Getting Started](docs/overview.md)** - Project overview and technology stack
- ⚙️ **[Environment Setup](docs/environment.md)** - Development and production configuration
- 🗄️ **[Database Schema](docs/database.md)** - Complete database structure
- 📊 **[Report Builder](docs/reports.md)** - Advanced reporting system documentation

## Key Features
- **Fast URL Shortening** with custom slugs and QR codes
- **Advanced Analytics** with geographic tracking and campaign analysis
- **Drag-and-Drop Report Builder** with container-based layouts
- **A/B Testing System** for conversion optimization
- **Geographic Targeting** with location-based redirects
- **RESTful API** with permission-based authentication
- **High-Performance Caching** with Redis support for email campaigns
- **Database Agnostic** - works with SQLite and MySQL

## Technology Stack
- Laravel 12.x with Filament 3.x admin panel
- Spatie Permission system with role-based access
- MaxMind GeoLite2 for geographic data
- Chart.js for data visualization
- SortableJS for drag-and-drop interfaces
- Redis for high-performance caching (optional)

## Current Status ✅
All core features are implemented and production-ready:
- ✅ Complete URL shortening system with health monitoring
- ✅ Geographic analytics with MaxMind GeoLite2 integration
- ✅ A/B testing with weighted traffic distribution
- ✅ UTM campaign tracking and analytics
- ✅ Advanced report builder with cross-container drag-and-drop
- ✅ Password protection and click limits
- ✅ Redis-based performance optimization for high traffic
- ✅ Comprehensive test suite (130+ tests)
- ✅ Database-agnostic design (SQLite + MySQL)

## Quick Commands
```bash
# Run migrations
php artisan migrate

# Set up permissions
php artisan shield:generate --all
php artisan roles:setup

# Update GeoIP database
php artisan geoip:update

# Run health checks
php artisan links:check-health

# Process Redis click batches (high traffic)
php artisan clicks:process-batch
```

## Environment Configuration
The application works with both SQLite (development) and MySQL (production). Key environment variables:

```env
# Database (SQLite or MySQL)
DB_CONNECTION=sqlite  # or mysql
DB_DATABASE=/path/to/database.sqlite

# Optional: Redis for high performance
CACHE_STORE=redis
CLICK_TRACKING_METHOD=redis

# Optional: MaxMind for geolocation
MAXMIND_LICENSE_KEY=your_license_key
```

## Performance Features
- **Redis Click Tracking** - Zero database writes during redirects for email campaigns
- **Database-Agnostic SQL** - Compatible date formatting for SQLite and MySQL
- **Smart Caching** - File-based (dev) or Redis (production) caching
- **Queue Processing** - Background job processing for analytics
- **Health Monitoring** - Automatic link health checks with smart scheduling

## Documentation Structure
All detailed documentation is organized in the `docs/` folder:
- [Core Features](docs/features.md) - Link management and basic functionality
- [Analytics](docs/analytics.md) - Click tracking and dashboard widgets
- [API System](docs/api.md) - RESTful endpoints and authentication
- [A/B Testing](docs/ab-testing.md) - Conversion optimization features
- [Geo-Targeting](docs/geo-targeting.md) - Location-based redirects
- [Performance](docs/performance.md) - Redis optimization and scaling
- [Deployment](docs/deployment.md) - Production setup and maintenance

This structure keeps the main CLAUDE.md concise while providing comprehensive documentation for all features and use cases.