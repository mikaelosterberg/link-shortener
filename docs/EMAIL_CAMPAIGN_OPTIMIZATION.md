# Email Campaign Optimization Guide

This guide explains how to configure the link shortener for high-traffic email campaigns using Redis-based click tracking to prevent database overload.

## Quick Setup for Email Campaigns

### 1. Install Redis Dependencies
```bash
# Install predis for PHP Redis client
composer require predis/predis
```

### 2. Configure Environment Variables
Add these to your `.env` file:
```env
# Enable Redis-based click tracking
CLICK_TRACKING_METHOD=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis

# Configure batch processing
REDIS_TRIGGER_THRESHOLD=500  # Start processing at 500 clicks
REDIS_BATCH_SIZE=2000        # Process up to 2000 clicks per batch

# Standard Redis connection settings
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

### 3. Clear Configuration Cache
```bash
php artisan config:clear
```

### 4. Set Up Laravel Scheduler (Required)
Add this line to your crontab to enable automatic click processing:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

This ensures pending clicks are processed every 5 minutes even if they don't reach the threshold.

## Performance Improvements

### Before Optimization (Traditional Queue Method)
- **Redirect Time**: ~48.5ms
- **Database Writes**: 1-2 writes per click
- **Cache Operations**: File-based (slower)
- **Campaign Impact**: Database overload with thousands of simultaneous clicks

### After Redis Optimization
- **Redirect Time**: ~13.3ms (70% faster)
- **Database Writes**: 0 writes during redirect (batch processed later)
- **Cache Operations**: Redis (10x faster than file cache)
- **Campaign Impact**: Zero database pressure during email blasts

## How It Works

### Click Processing Flow
1. **User clicks link** → Redis stores click data (2.35ms)
2. **Counter reaches threshold** → Batch job processes all pending clicks
3. **Laravel scheduler** → Processes remaining clicks every 5 minutes

### Batch Processing Logic
```
REDIS_TRIGGER_THRESHOLD=500  # When to start processing
REDIS_BATCH_SIZE=2000        # How many to process at once

Example scenario:
- Clicks 1-499: Stored in Redis only
- Click 500: Triggers batch job to process ALL pending clicks
- If 600 pending: Processes 500, leaves 100 for next batch
- If only 100 pending: Processes all 100
```

### Special Handling for Click Limits
Links with click limits still use synchronous tracking to ensure accurate enforcement. This maintains security while optimizing performance for unlimited links.

## Email Campaign Workflow

### Pre-Campaign Setup
```bash
# 1. Switch to Redis mode
echo "CLICK_TRACKING_METHOD=redis" >> .env
echo "CACHE_STORE=redis" >> .env
echo "QUEUE_CONNECTION=redis" >> .env
php artisan config:clear

# 2. Pre-warm important links (optional)
php artisan tinker
>>> App\Models\Link::where('group_id', $campaignGroupId)->each(fn($link) => cache()->put("link_data_{$link->short_code}", $link, 3600))
```

### During Campaign Monitoring
```bash
# Check pending clicks in Redis
redis-cli llen clicks:pending

# Process clicks manually if needed
php artisan clicks:process-batch

# Monitor Redis memory usage
redis-cli info memory

# Check queue status
php artisan queue:monitor
```

### Post-Campaign Cleanup
```bash
# Process any remaining clicks
php artisan clicks:process-batch --limit=10000

# Optionally switch back to normal mode
echo "CLICK_TRACKING_METHOD=queue" >> .env
php artisan config:clear
```

## Testing Redis Setup

### Verify Redis Connection
```bash
php artisan tinker
>>> Redis::ping()
# Should return: "+PONG"
```

### Test Click Tracking
```bash
# Generate test clicks
php artisan tinker
>>> $link = App\Models\Link::first()
>>> for($i = 0; $i < 10; $i++) { 
    app('App\Services\ClickTrackingService')->trackClick($link, [
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test',
        'clicked_at' => now()->format('Y-m-d H:i:s')
    ]);
}

# Check Redis queue
>>> Redis::llen('clicks:pending')
# Should return: 10
```

## Troubleshooting

### Redis Connection Issues
**Problem**: "Class Redis not found" or connection errors

**Solution**:
```bash
# Ensure predis is installed
composer require predis/predis

# Check Redis is running
redis-cli ping

# Verify configuration
php artisan config:clear
php artisan tinker
>>> config('database.redis.default')
```

### High Memory Usage
**Problem**: Redis consuming too much memory

**Solutions**:
```bash
# Monitor memory usage
redis-cli info memory

# Process clicks more frequently
php artisan clicks:process-batch --limit=100

# Reduce batch trigger threshold
echo "REDIS_TRIGGER_THRESHOLD=100" >> .env
```

### Clicks Not Processing
**Problem**: Pending clicks not being processed

**Check**:
```bash
# Verify cron is running Laravel scheduler
crontab -l | grep schedule:run

# Check for failed jobs
php artisan queue:failed

# Manual processing
php artisan clicks:process-pending --force
```

## Performance Monitoring

### Key Metrics to Watch
```bash
# Redis queue length
redis-cli llen clicks:pending

# Redis memory usage
redis-cli info memory | grep used_memory_human

# Failed queue jobs
php artisan queue:failed

# Queue worker status
php artisan queue:monitor
```

### Recommended Thresholds
- **Small campaigns** (< 1,000 clicks): Default settings work fine
- **Medium campaigns** (1,000-10,000 clicks): Consider `REDIS_TRIGGER_THRESHOLD=200`
- **Large campaigns** (10,000+ clicks): Use `REDIS_TRIGGER_THRESHOLD=500` and `REDIS_BATCH_SIZE=2000`

## Comparison with Other Solutions

### Traditional Database Queue
- ✅ Simple setup
- ❌ Database writes during redirect
- ❌ Can overload database with high traffic
- ⚠️ 48.5ms average redirect time

### Redis Optimization
- ✅ Zero database writes during redirect
- ✅ 70% faster redirects (13.3ms)
- ✅ Batch processing reduces load
- ✅ Automatic fallback to queue method
- ⚠️ Requires Redis server

### None Tracking
- ✅ Fastest possible (7.3ms)
- ✅ Minimal resource usage
- ❌ No detailed analytics
- ❌ Only basic click counts

## Conclusion

The Redis optimization provides the best balance of performance and features for email campaigns. It eliminates database pressure during high-traffic periods while maintaining full analytics capabilities through batch processing.

For typical email campaigns sending to thousands of recipients, this optimization can mean the difference between a responsive application and database timeouts.