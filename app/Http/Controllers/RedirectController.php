<?php

namespace App\Http\Controllers;

use App\Events\LinkClicked;
use App\Events\LinkNotFound;
use App\Jobs\LogClickJob;
use App\Models\Link;
use App\Services\GeolocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RedirectController extends Controller
{
    public function redirect(string $shortCode, GeolocationService $geoService)
    {
        // Cache the link data, but not the redirect decision
        $cacheKey = "link_data_{$shortCode}";
        $link = Cache::remember($cacheKey, 3600, function () use ($shortCode) {
            return Link::with(['geoRules' => function($query) {
                $query->where('is_active', true)->orderBy('priority');
            }])
                ->where('short_code', $shortCode)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->first();
        });
        
        if (!$link) {
            // Dispatch event for plugins/listeners to handle
            event(new LinkNotFound($shortCode, request()));
            
            // Track 404 attempts if configured
            if (config('shortener.not_found.track_attempts', true)) {
                // Could log to analytics or database here
                logger('Link not found: ' . $shortCode, [
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'referer' => request()->header('referer'),
                ]);
            }
            
            // Custom redirect instead of 404
            if ($redirectUrl = config('shortener.not_found.redirect_url')) {
                return redirect($redirectUrl);
            }
            
            // Custom 404 view
            if ($customView = config('shortener.not_found.view')) {
                return response()->view($customView, [
                    'short_code' => $shortCode
                ], 404);
            }
            
            abort(404);
        }
        
        // Determine target URL based on geo rules
        $targetUrl = $link->original_url;
        
        // Check geo rules if any exist
        if ($link->geoRules->isNotEmpty() && $geoService->isAvailable()) {
            $location = $geoService->getFullLocation(request()->ip());
            
            // Find the first matching rule (already sorted by priority)
            foreach ($link->geoRules as $rule) {
                if ($rule->matchesLocation($location)) {
                    $targetUrl = $rule->redirect_url;
                    break;
                }
            }
        }
        
        // Dispatch click event for analytics/plugins
        event(new LinkClicked($link, request()));
        
        // Log click asynchronously
        if (config('shortener.analytics.async_tracking', true)) {
            LogClickJob::dispatch([
                'link_id' => $link->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'referer' => request()->header('referer'),
                'clicked_at' => now()
            ])->onQueue('clicks');
        }
        
        // Increment counter asynchronously
        DB::table('links')->where('id', $link->id)->increment('click_count');
        
        return redirect($targetUrl, $link->redirect_type);
    }
}
