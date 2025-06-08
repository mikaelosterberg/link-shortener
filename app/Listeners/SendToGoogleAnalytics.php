<?php

namespace App\Listeners;

use App\Events\LinkClicked;

/**
 * Example Google Analytics Listener
 *
 * This is an example of how users can create custom event listeners
 * to send data to external analytics services.
 *
 * To use this listener:
 * 1. Add it to the $listen array in EventServiceProvider
 * 2. Implement your actual Google Analytics integration
 * 3. Consider using packages like spatie/laravel-google-analytics
 */
class SendToGoogleAnalytics
{
    /**
     * Handle the event.
     */
    public function handle(LinkClicked $event): void
    {
        // Example implementation - replace with actual GA integration
        //
        // $analytics = app('google.analytics');
        // $analytics->track('event', [
        //     'eventCategory' => 'Link',
        //     'eventAction' => 'Click',
        //     'eventLabel' => $event->link->short_code,
        //     'customDimensions' => [
        //         'destination_url' => $event->link->original_url,
        //         'group_name' => $event->link->group?->name,
        //     ]
        // ]);

        // For now, just log to demonstrate the concept
        logger('Link clicked - would send to GA', [
            'link_id' => $event->link->id,
            'short_code' => $event->link->short_code,
            'destination' => $event->link->original_url,
            'user_agent' => $event->request->userAgent(),
            'referer' => $event->request->header('referer'),
        ]);
    }
}
