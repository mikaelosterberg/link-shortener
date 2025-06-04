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
        
        // Extract UTM parameters from the request
        $utmParams = $this->extractUtmParameters(request());
        
        // Append UTM parameters to target URL
        if (!empty($utmParams)) {
            $targetUrl = $this->appendUtmParameters($targetUrl, $utmParams);
        }
        
        // Dispatch click event for analytics/plugins
        event(new LinkClicked($link, request()));
        
        // Log click asynchronously with UTM parameters
        if (config('shortener.analytics.async_tracking', true)) {
            LogClickJob::dispatch([
                'link_id' => $link->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'referer' => request()->header('referer'),
                'clicked_at' => now(),
                'utm_source' => $utmParams['utm_source'] ?? null,
                'utm_medium' => $utmParams['utm_medium'] ?? null,
                'utm_campaign' => $utmParams['utm_campaign'] ?? null,
                'utm_term' => $utmParams['utm_term'] ?? null,
                'utm_content' => $utmParams['utm_content'] ?? null,
            ])->onQueue('clicks');
        }
        
        // Increment counter asynchronously
        DB::table('links')->where('id', $link->id)->increment('click_count');
        
        return redirect($targetUrl, $link->redirect_type);
    }
    
    /**
     * Extract UTM parameters from the request
     */
    private function extractUtmParameters($request): array
    {
        $utmParams = [];
        
        // Valid UTM parameter names
        $validUtmParams = [
            'utm_source',
            'utm_medium', 
            'utm_campaign',
            'utm_term',
            'utm_content'
        ];
        
        foreach ($validUtmParams as $param) {
            if ($request->has($param) && !empty($request->get($param))) {
                $utmParams[$param] = $request->get($param);
            }
        }
        
        return $utmParams;
    }
    
    /**
     * Append UTM parameters to the target URL
     */
    private function appendUtmParameters(string $url, array $utmParams): string
    {
        if (empty($utmParams)) {
            return $url;
        }
        
        // Parse the URL to handle existing query parameters
        $parsedUrl = parse_url($url);
        
        // Get existing query parameters
        $existingParams = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $existingParams);
        }
        
        // Merge UTM parameters (UTM parameters take precedence)
        $allParams = array_merge($existingParams, $utmParams);
        
        // Rebuild the URL
        $newUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        
        if (isset($parsedUrl['port'])) {
            $newUrl .= ':' . $parsedUrl['port'];
        }
        
        if (isset($parsedUrl['path'])) {
            $newUrl .= $parsedUrl['path'];
        }
        
        if (!empty($allParams)) {
            $newUrl .= '?' . http_build_query($allParams);
        }
        
        if (isset($parsedUrl['fragment'])) {
            $newUrl .= '#' . $parsedUrl['fragment'];
        }
        
        return $newUrl;
    }
}
