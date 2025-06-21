<?php

namespace App\Services;

use App\Models\IntegrationSetting;
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
        return IntegrationSetting::get('google_analytics', 'enabled', false) &&
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
                    'verify' => config('app.env') === 'production', // Only verify SSL in production
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // Force IPv4
                    ],
                ])
                ->post($this->buildEndpointUrl(), $payload);

            if ($response->successful()) {
                Log::debug('GA4 event sent successfully', ['click_id' => $clickData['click_id'] ?? null]);

                return true;
            } else {
                Log::warning('GA4 event failed', [
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
     * Build the event payload for GA4
     */
    private function buildEventPayload(array $clickData): array
    {
        // Generate a unique client_id for this click (GA4 requirement)
        // For debug mode, use a consistent test client ID
        $clientId = $clickData['session_id'] ?? 'test-client-'.time();

        // Build the base event as page_view for standard GA4 reports
        // Use the short link URL as page_location since GA4 only accepts same-domain URLs
        $shortLinkUrl = config('app.url').'/'.($clickData['link_slug'] ?? 'unknown');

        $event = [
            'name' => 'page_view',
            'parameters' => [
                'page_location' => $shortLinkUrl,
                'page_title' => ($clickData['link_slug'] ?? 'Unknown').' - Link Redirect',
                'page_referrer' => $clickData['referrer'] ?? null,
                // Recommended GA4 parameters
                'session_id' => $clickData['session_id'] ?? $clientId,
                'engagement_time_msec' => 100, // Minimal engagement time for page views
                // Custom parameters for link tracking
                'custom_link_id' => $clickData['link_id'] ?? null,
                'custom_link_slug' => $clickData['link_slug'] ?? null,
                'custom_destination_url' => $clickData['destination_url'] ?? null,
            ],
        ];

        // Add geographic data if available
        if (! empty($clickData['country'])) {
            $event['parameters']['country'] = $clickData['country'];
        }
        if (! empty($clickData['region'])) {
            $event['parameters']['region'] = $clickData['region'];
        }
        if (! empty($clickData['city'])) {
            $event['parameters']['city'] = $clickData['city'];
        }

        // Add UTM parameters if available (map to GA4 standard parameter names)
        $utmMapping = [
            'utm_source' => 'source',
            'utm_medium' => 'medium',
            'utm_campaign' => 'campaign',
            'utm_term' => 'term',
            'utm_content' => 'content',
        ];

        foreach ($utmMapping as $utmParam => $gaParam) {
            if (! empty($clickData[$utmParam])) {
                $event['parameters'][$gaParam] = $clickData[$utmParam];
            }
        }

        // Add A/B test data if available
        if (! empty($clickData['ab_test_id'])) {
            $event['parameters']['ab_test_id'] = $clickData['ab_test_id'];
            $event['parameters']['ab_variant_id'] = $clickData['ab_variant_id'] ?? null;
        }

        // Add device/browser info if available
        if (! empty($clickData['device_type'])) {
            $event['parameters']['device_type'] = $clickData['device_type'];
        }
        if (! empty($clickData['browser'])) {
            $event['parameters']['browser'] = $clickData['browser'];
        }
        if (! empty($clickData['os'])) {
            $event['parameters']['operating_system'] = $clickData['os'];
        }

        $payload = [
            'client_id' => $clientId,
            'events' => [$event],
        ];

        // Add session_id at payload level if available (GA4 recommendation)
        if (! empty($clickData['session_id'])) {
            $payload['session_id'] = $clickData['session_id'];
        }

        // Add timestamp if click time is available (important for queued processing)
        if (! empty($clickData['clicked_at'])) {
            // Convert clicked_at to timestamp_micros (microseconds since Unix epoch)
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
        return IntegrationSetting::get('google_analytics', 'measurement_id');
    }

    /**
     * Get the GA4 API Secret
     */
    private function getApiSecret(): ?string
    {
        return IntegrationSetting::get('google_analytics', 'api_secret');
    }

    /**
     * Test the GA4 connection by sending a test event
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
                'message' => 'Cannot resolve DNS for Google Analytics (www.google-analytics.com). Check your internet connection or DNS settings.',
            ];
        }

        try {
            // Use a properly formatted client_id for DebugView compatibility
            $testClientId = 'test-client-'.substr(md5(uniqid()), 0, 8).'.'.time();

            $testData = [
                'link_id' => 'test',
                'link_slug' => 'ga4-test-connection',
                'destination_url' => 'https://example.com/ga4-integration-test',
                'user_agent' => 'LinkShortener-Test/1.0',
                'referrer' => config('app.url'),
                'session_id' => $testClientId, // Use consistent client_id for debug tracking
            ];

            // Use validation endpoint for testing (doesn't count in analytics)
            $endpoint = $this->buildEndpointUrl().'&debug_mode=1';

            $payload = $this->buildEventPayload($testData);

            // Log the endpoint URL and payload for debugging
            Log::debug('GA4 test request', [
                'url' => $endpoint,
                'payload' => $payload,
                'measurement_id' => $this->getMeasurementId(),
            ]);

            $response = Http::timeout(10)
                ->withOptions([
                    'verify' => config('app.env') === 'production', // Only verify SSL in production
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // Force IPv4
                    ],
                ])
                ->post($endpoint, $payload);

            // Log the response for debugging
            Log::debug('GA4 test response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers(),
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Google Analytics connection test successful. Check your GA4 DebugView for the test event.',
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Connection test failed (Status: '.$response->status().'): '.$response->body(),
                ];
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();

            // Show the actual error without making assumptions
            if (str_contains($message, 'Could not resolve host') || str_contains($message, 'cURL error')) {
                return [
                    'success' => false,
                    'message' => 'Network error: '.$message,
                ];
            }

            return [
                'success' => false,
                'message' => 'Connection test failed: '.$message,
            ];
        }
    }
}
