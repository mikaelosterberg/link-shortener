<?php

namespace App\Jobs;

use App\Models\Click;
use App\Services\GeolocationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class LogClickJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The click data.
     *
     * @var array
     */
    protected $clickData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $clickData)
    {
        $this->clickData = $clickData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get geolocation data if not already present
        if (empty($this->clickData['country'])) {
            $location = $this->getGeolocation($this->clickData['ip_address']);
            $country = $location['country'] ?? null;
            $city = $location['city'] ?? null;
        } else {
            // Use existing geo data
            $country = $this->clickData['country'] ?? null;
            $city = $this->clickData['city'] ?? null;
        }

        // GA events are sent immediately during redirect, not during job processing

        // Create click record with UTM parameters and A/B test data
        Click::create([
            'link_id' => $this->clickData['link_id'],
            'ip_address' => $this->clickData['ip_address'],
            'user_agent' => $this->clickData['user_agent'],
            'referer' => $this->clickData['referer'],
            'country' => $country,
            'city' => $city,
            'clicked_at' => $this->clickData['clicked_at'],
            'utm_source' => $this->clickData['utm_source'] ?? null,
            'utm_medium' => $this->clickData['utm_medium'] ?? null,
            'utm_campaign' => $this->clickData['utm_campaign'] ?? null,
            'utm_term' => $this->clickData['utm_term'] ?? null,
            'utm_content' => $this->clickData['utm_content'] ?? null,
            'ab_test_variant_id' => $this->clickData['ab_test_variant_id'] ?? null,
        ]);

        // Increment click count if flag is set (for links without click limits)
        if (! empty($this->clickData['increment_click_count'])) {
            DB::table('links')
                ->where('id', $this->clickData['link_id'])
                ->increment('click_count');
        }
    }


    /**
     * Get geolocation data for IP address
     */
    private function getGeolocation(string $ip): array
    {
        // Check if GeolocationService exists
        if (class_exists(GeolocationService::class)) {
            $service = new GeolocationService;

            return $service->getLocation($ip);
        }

        return ['country' => null, 'city' => null];
    }
}
