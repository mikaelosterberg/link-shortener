<?php

namespace App\Http\Controllers;

use App\Jobs\LogClickJob;
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
}
