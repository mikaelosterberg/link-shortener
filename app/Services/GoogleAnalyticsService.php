<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleAnalyticsService
{
    private const GA4_ENDPOINT = 'https://www.google-analytics.com/mp/collect';

    /**
     * Check if Google Analytics integration is enabled and configured
     */
    public function isEnabled(): bool
    {
        return Cache::remember('ga_enabled', 300, function () {
            return IntegrationSetting::get('google_analytics', 'enabled', false);
        }) &&
               ! empty($this->getMeasurementId()) &&
               ! empty($this->getApiSecret());
    }

    /**
     * Send a click event to Google Analytics 4
     */
    public function sendClickEvent(array $clickData): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        try {
            $payload = $this->buildEventPayload($clickData);

            $response = Http::timeout(5)
                ->withOptions([
                    'verify' => config('app.env') === 'production',
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                    ],
                ])
                ->post($this->buildEndpointUrl(), $payload);

            if ($response->successful()) {
                return true;
            } else {
                Log::error('GA4 event failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'click_id' => $clickData['click_id'] ?? null,
                ]);

                return false;
            }
        } catch (\Exception $e) {
            Log::error('GA4 event exception', [
                'error' => $e->getMessage(),
                'click_id' => $clickData['click_id'] ?? null,
            ]);

            return false;
        }
    }

    /**
     * Build the GA4 Measurement Protocol endpoint URL
     */
    private function buildEndpointUrl(): string
    {
        return self::GA4_ENDPOINT.'?'.http_build_query([
            'measurement_id' => $this->getMeasurementId(),
            'api_secret' => $this->getApiSecret(),
        ]);
    }

    /**
     * Generate a proper GA4 client ID
     */
    private function generateClientId(): string
    {
        return sprintf('%09d.%010d', mt_rand(100000000, 999999999), time());
    }

    /**
     * Build the event payload for GA4 - using 'params' not 'parameters'
     */
    private function buildEventPayload(array $clickData): array
    {
        // Generate client_id
        $clientId = $clickData['session_id'] ?? $this->generateClientId();

        // Use the short link URL as page_location
        $pageLocation = config('app.url').'/'.($clickData['link_slug'] ?? 'unknown');

        // Build event with 'params' (not 'parameters')
        $eventParams = [
            'page_location' => $pageLocation,
            'page_title' => ($clickData['link_slug'] ?? 'Unknown').' - Link Redirect',
            'engagement_time_msec' => 100,
            'custom_link_id' => $clickData['link_id'] ?? null,
            'custom_link_slug' => $clickData['link_slug'] ?? null,
            'custom_destination_url' => $clickData['destination_url'] ?? null,
        ];

        // Add optional parameters only if they exist
        $referrer = $clickData['referrer'] ?? $clickData['referer'] ?? null;
        if (! empty($referrer)) {
            $eventParams['page_referrer'] = $referrer;
        }

        if (! empty($clickData['session_id'])) {
            $eventParams['session_id'] = $clickData['session_id'];
        } elseif (! empty($clientId)) {
            $eventParams['session_id'] = $clientId;
        }

        // Geographic data will be automatically determined by GA4 from ip_override

        // Add UTM parameters
        $utmMapping = [
            'utm_source' => 'source',
            'utm_medium' => 'medium',
            'utm_campaign' => 'campaign',
            'utm_term' => 'term',
            'utm_content' => 'content',
        ];

        foreach ($utmMapping as $utmParam => $gaParam) {
            if (! empty($clickData[$utmParam])) {
                $eventParams[$gaParam] = $clickData[$utmParam];
            }
        }

        // Add A/B test data
        if (! empty($clickData['ab_test_id'])) {
            $eventParams['ab_test_id'] = $clickData['ab_test_id'];
            $eventParams['ab_variant_id'] = $clickData['ab_variant_id'] ?? null;
        }

        // Add device/browser info
        if (! empty($clickData['device_type'])) {
            $eventParams['device_type'] = $clickData['device_type'];
        }
        if (! empty($clickData['browser'])) {
            $eventParams['browser'] = $clickData['browser'];
        }
        if (! empty($clickData['os'])) {
            $eventParams['operating_system'] = $clickData['os'];
        }

        // Add debug_mode for test events
        if (! empty($clickData['is_debug'])) {
            $eventParams['debug_mode'] = 1;
        }

        // Build the complete event
        $event = [
            'name' => 'page_view',
            'params' => $eventParams,
        ];

        // Build final payload
        $payload = [
            'client_id' => $clientId,
            'events' => [$event],
        ];

        // Include the original client IP so GA4 can determine correct geography
        if (! empty($clickData['ip_address'])) {
            $payload['ip_override'] = $clickData['ip_address'];
        }

        // Add timestamp if available
        if (! empty($clickData['clicked_at'])) {
            $clickedAt = is_string($clickData['clicked_at'])
                ? \Carbon\Carbon::parse($clickData['clicked_at'])
                : $clickData['clicked_at'];

            $payload['timestamp_micros'] = $clickedAt->getPreciseTimestamp(6);
        }

        return $payload;
    }

    /**
     * Get the GA4 Measurement ID
     */
    private function getMeasurementId(): ?string
    {
        return Cache::remember('ga_measurement_id', 3600, function () {
            return IntegrationSetting::get('google_analytics', 'measurement_id');
        });
    }

    /**
     * Get the GA4 API Secret
     */
    private function getApiSecret(): ?string
    {
        return Cache::remember('ga_api_secret', 3600, function () {
            return IntegrationSetting::get('google_analytics', 'api_secret');
        });
    }

    /**
     * Clear all cached Google Analytics settings
     */
    public static function clearCache(): void
    {
        Cache::forget('ga_enabled');
        Cache::forget('ga_measurement_id');
        Cache::forget('ga_api_secret');
    }

    /**
     * Test the GA4 connection
     */
    public function testConnection(): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'Google Analytics integration is not properly configured.',
            ];
        }

        // Check DNS resolution
        $host = 'www.google-analytics.com';
        $ip = gethostbyname($host);

        if ($ip === $host) {
            return [
                'success' => false,
                'message' => 'Cannot resolve DNS for Google Analytics. Check your internet connection.',
            ];
        }

        try {
            $testClientId = $this->generateClientId();

            $testData = [
                'link_id' => 'test',
                'link_slug' => 'ga4-test-connection',
                'destination_url' => config('app.url').'/ga4-integration-test',
                'referrer' => config('app.url'),
                'session_id' => $testClientId,
                'is_debug' => true,
            ];

            $payload = $this->buildEventPayload($testData);

            // Test with validation endpoint first
            $validationEndpoint = str_replace('/mp/collect', '/debug/mp/collect', $this->buildEndpointUrl());

            $validationResponse = Http::timeout(10)
                ->withOptions([
                    'verify' => config('app.env') === 'production',
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                    ],
                ])
                ->post($validationEndpoint, $payload);

            if (! $validationResponse->successful()) {
                return [
                    'success' => false,
                    'message' => 'Connection test failed (Status: '.$validationResponse->status().'): '.$validationResponse->body(),
                ];
            }

            $validationBody = $validationResponse->json();
            if (! empty($validationBody['validationMessages'])) {
                $errors = collect($validationBody['validationMessages'])->pluck('description')->implode('; ');

                return [
                    'success' => false,
                    'message' => 'Payload validation errors: '.$errors,
                ];
            }

            // Send to production endpoint
            $productionEndpoint = $this->buildEndpointUrl();

            $response = Http::timeout(10)
                ->withOptions([
                    'verify' => config('app.env') === 'production',
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                    ],
                ])
                ->post($productionEndpoint, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Google Analytics connection test successful! Events should appear in your GA4 Real-time reports within a few seconds.',
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Production test failed: '.$response->body(),
                ];
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            
            // Format specific error types for backward compatibility
            if (str_contains($message, 'cURL error') || str_contains($message, 'Could not resolve host')) {
                $message = 'Network error: '.$message;
            } else {
                $message = 'Connection test failed: '.$message;
            }
            
            return [
                'success' => false,
                'message' => $message,
            ];
        }
    }
}
