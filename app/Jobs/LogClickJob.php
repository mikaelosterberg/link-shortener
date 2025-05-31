<?php

namespace App\Jobs;

use App\Models\Click;
use App\Services\GeolocationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        // Get geolocation data if available
        $location = $this->getGeolocation($this->clickData['ip_address']);
        
        // Create click record
        Click::create([
            'link_id' => $this->clickData['link_id'],
            'ip_address' => $this->clickData['ip_address'],
            'user_agent' => $this->clickData['user_agent'],
            'referer' => $this->clickData['referer'],
            'country' => $location['country'] ?? null,
            'city' => $location['city'] ?? null,
            'clicked_at' => $this->clickData['clicked_at'],
        ]);
    }
    
    /**
     * Get geolocation data for IP address
     */
    private function getGeolocation(string $ip): array
    {
        // Check if GeolocationService exists
        if (class_exists(GeolocationService::class)) {
            $service = new GeolocationService();
            return $service->getLocation($ip);
        }
        
        return ['country' => null, 'city' => null];
    }
}
