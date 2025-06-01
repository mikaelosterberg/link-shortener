<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Homepage Behavior
    |--------------------------------------------------------------------------
    |
    | Configure how the root URL (/) should behave when accessed.
    |
    */
    'homepage' => [
        // Redirect to admin panel instead of showing landing page
        'redirect_to_admin' => false,
        
        // Custom redirect URL (takes precedence over redirect_to_admin)
        'redirect_url' => null,
        
        // Custom view to show (default: welcome)
        'view' => 'welcome',
    ],

    /*
    |--------------------------------------------------------------------------
    | Not Found Behavior
    |--------------------------------------------------------------------------
    |
    | Configure what happens when a short code doesn't exist.
    |
    */
    'not_found' => [
        // URL to redirect to instead of showing 404
        'redirect_url' => null,
        
        // Whether to track 404 attempts for analytics
        'track_attempts' => true,
        
        // Custom 404 view
        'view' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Link Processing
    |--------------------------------------------------------------------------
    |
    | Configure how links are processed during creation and access.
    |
    */
    'link_processing' => [
        // Validate SSL certificates on destination URLs
        'validate_ssl' => false,
        
        // Maximum redirects to follow when checking URLs
        'max_redirects' => 5,
        
        // Timeout for URL validation (seconds)
        'validation_timeout' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    |
    | Configure analytics and tracking behavior.
    |
    */
    'analytics' => [
        // Whether to track clicks asynchronously
        'async_tracking' => true,
        
        // Custom analytics providers to notify
        'providers' => [
            // 'custom_provider' => App\Analytics\CustomProvider::class,
        ],
    ],
];