<?php

namespace App\Http\Controllers;

use App\Events\LinkClicked;
use App\Events\LinkNotFound;
use App\Jobs\LogClickJob;
use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RedirectController extends Controller
{
    public function redirect(string $shortCode)
    {
        // Check file cache first
        $cacheKey = "link_{$shortCode}";
        $link = Cache::remember($cacheKey, 3600, function () use ($shortCode) {
            return DB::selectOne(
                'SELECT id, original_url, redirect_type FROM links 
                WHERE short_code = ? AND is_active = 1 
                AND (expires_at IS NULL OR expires_at > ?)',
                [$shortCode, now()]
            );
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
        
        // Create Link model instance for event (more useful than raw DB result)
        $linkModel = Link::find($link->id);
        
        // Dispatch click event for analytics/plugins
        event(new LinkClicked($linkModel, request()));
        
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
        
        return redirect($link->original_url, $link->redirect_type);
    }
}
