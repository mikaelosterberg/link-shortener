<?php

namespace App\Services;

use App\Models\Click;
use App\Models\Link;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportMetricsService
{
    public function getMetricData(string $metric, array $config = [], array $globalFilters = []): array
    {
        $dateRange = $this->getDateRange($globalFilters);
        $linkFilter = $this->getLinkFilter($globalFilters);

        return match ($metric) {
            'total_clicks' => $this->getTotalClicks($dateRange, $linkFilter, $config),
            'unique_clicks' => $this->getUniqueClicks($dateRange, $linkFilter, $config),
            'total_links' => $this->getTotalLinks($dateRange, $linkFilter, $config),
            'active_links' => $this->getActiveLinks($dateRange, $linkFilter, $config),
            'clicks_over_time' => $this->getClicksOverTime($dateRange, $linkFilter, $config),
            'top_links' => $this->getTopLinks($dateRange, $linkFilter, $config),
            'clicks_by_country' => $this->getClicksByCountry($dateRange, $linkFilter, $config),
            'clicks_by_device' => $this->getClicksByDevice($dateRange, $linkFilter, $config),
            'traffic_sources' => $this->getTrafficSources($dateRange, $linkFilter, $config),
            'link_performance' => $this->getLinkPerformance($dateRange, $linkFilter, $config),
            'browser_stats' => $this->getBrowserStats($dateRange, $linkFilter, $config),
            'utm_campaigns' => $this->getUtmCampaigns($dateRange, $linkFilter, $config),
            default => ['error' => "Unknown metric: {$metric}"],
        };
    }

    private function getDateRange(array $filters): array
    {
        return [
            'start' => Carbon::parse($filters['start_date'] ?? now()->subDays(30)),
            'end' => Carbon::parse($filters['end_date'] ?? now())->endOfDay(),
        ];
    }

    private function getLinkFilter(array $filters): ?array
    {
        $filterType = $filters['link_filter_type'] ?? 'all';

        if ($filterType === 'all') {
            return null;
        }

        $linkIds = [];

        if ($filterType === 'specific_links' && isset($filters['link_ids'])) {
            $linkIds = $filters['link_ids'];
        } elseif ($filterType === 'link_groups' && isset($filters['link_group_ids'])) {
            // Get all links in the specified groups
            $linkIds = Link::whereIn('group_id', $filters['link_group_ids'])->pluck('id')->toArray();
        }

        if (empty($linkIds)) {
            return null;
        }

        return ['link_ids' => $linkIds];
    }

    private function getTotalClicks(array $dateRange, ?array $linkFilter, array $config): array
    {
        $query = Click::whereBetween('clicked_at', [$dateRange['start'], $dateRange['end']]);

        if ($linkFilter) {
            $query = $this->applyLinkFilter($query, $linkFilter);
        }

        $current = $query->count();

        $comparison = null;
        if ($config['show_comparison'] ?? true) {
            $comparison = $this->getComparisonValue('total_clicks', $dateRange, $linkFilter, $config);
        }

        return [
            'value' => $current,
            'formatted_value' => number_format($current),
            'comparison' => $comparison,
            'label' => 'Total Clicks',
        ];
    }

    private function getUniqueClicks(array $dateRange, ?array $linkFilter, array $config): array
    {
        $query = Click::whereBetween('clicked_at', [$dateRange['start'], $dateRange['end']])
            ->distinct('ip_address');

        if ($linkFilter) {
            $query = $this->applyLinkFilter($query, $linkFilter);
        }

        $current = $query->count('ip_address');

        $comparison = null;
        if ($config['show_comparison'] ?? true) {
            $comparison = $this->getComparisonValue('unique_clicks', $dateRange, $linkFilter, $config);
        }

        return [
            'value' => $current,
            'formatted_value' => number_format($current),
            'comparison' => $comparison,
            'label' => 'Unique Visitors',
        ];
    }

    private function getTotalLinks(array $dateRange, ?array $linkFilter, array $config): array
    {
        $query = Link::whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

        if ($linkFilter && isset($linkFilter['groupIds'])) {
            $query->whereIn('group_id', $linkFilter['groupIds']);
        }

        $current = $query->count();

        return [
            'value' => $current,
            'formatted_value' => number_format($current),
            'label' => 'Total Links',
        ];
    }

    private function getActiveLinks(array $dateRange, ?array $linkFilter, array $config): array
    {
        $query = Link::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });

        if ($linkFilter && isset($linkFilter['groupIds'])) {
            $query->whereIn('group_id', $linkFilter['groupIds']);
        }

        $current = $query->count();

        return [
            'value' => $current,
            'formatted_value' => number_format($current),
            'label' => 'Active Links',
        ];
    }

    private function getClicksOverTime(array $dateRange, ?array $linkFilter, array $config): array
    {
        $period = $config['period'] ?? 'daily';

        // Use database-agnostic date formatting
        $dbDriver = DB::connection()->getDriverName();

        if ($dbDriver === 'sqlite') {
            $format = match ($period) {
                'hourly' => '%Y-%m-%d %H:00:00',
                'daily' => '%Y-%m-%d',
                'weekly' => '%Y-%W',  // SQLite uses %W for week
                'monthly' => '%Y-%m',
                default => '%Y-%m-%d',
            };
            $dateExpression = "strftime('{$format}', clicked_at)";
        } else {
            // MySQL format
            $format = match ($period) {
                'hourly' => '%Y-%m-%d %H:00:00',
                'daily' => '%Y-%m-%d',
                'weekly' => '%Y-%u',
                'monthly' => '%Y-%m',
                default => '%Y-%m-%d',
            };
            $dateExpression = "DATE_FORMAT(clicked_at, '{$format}')";
        }

        $query = DB::table('clicks')
            ->select(DB::raw("{$dateExpression} as period"), DB::raw('COUNT(*) as clicks'))
            ->whereBetween('clicked_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('period')
            ->orderBy('period');

        if ($linkFilter) {
            $query = $this->applyLinkFilterToQuery($query, $linkFilter);
        }

        $data = $query->get();

        return [
            'labels' => $data->pluck('period')->toArray(),
            'data' => $data->pluck('clicks')->toArray(),
            'type' => 'line',
            'title' => 'Clicks Over Time',
        ];
    }

    private function getTopLinks(array $dateRange, ?array $linkFilter, array $config): array
    {
        $limit = $config['limit'] ?? 10;

        $query = DB::table('clicks')
            ->join('links', 'clicks.link_id', '=', 'links.id')
            ->select(
                'links.short_code',
                'links.original_url',
                DB::raw('COUNT(*) as total_clicks'),
                DB::raw('COUNT(DISTINCT clicks.ip_address) as unique_clicks')
            )
            ->whereBetween('clicks.clicked_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('links.id', 'links.short_code', 'links.original_url')
            ->orderBy('total_clicks', 'desc')
            ->limit($limit);

        if ($linkFilter) {
            $query = $this->applyLinkFilterToQuery($query, $linkFilter, 'links');
        }

        $data = $query->get();

        return [
            'data' => $data->map(function ($item) {
                return [
                    'label' => $item->short_code,
                    'url' => $item->original_url,
                    'total_clicks' => $item->total_clicks,
                    'unique_clicks' => $item->unique_clicks,
                ];
            })->toArray(),
            'type' => 'table',
            'title' => 'Top Performing Links',
        ];
    }

    private function getClicksByCountry(array $dateRange, ?array $linkFilter, array $config): array
    {
        $limit = $config['limit'] ?? 10;

        $query = DB::table('clicks')
            ->select('country', DB::raw('COUNT(*) as clicks'))
            ->whereBetween('clicked_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderBy('clicks', 'desc')
            ->limit($limit);

        if ($linkFilter) {
            $query = $this->applyLinkFilterToQuery($query, $linkFilter);
        }

        $data = $query->get();

        return [
            'labels' => $data->pluck('country')->toArray(),
            'data' => $data->pluck('clicks')->toArray(),
            'type' => 'bar',
            'title' => 'Clicks by Country',
        ];
    }

    private function getClicksByDevice(array $dateRange, ?array $linkFilter, array $config): array
    {
        $query = DB::table('clicks')
            ->select(
                DB::raw("CASE
                    WHEN user_agent LIKE '%Mobile%' OR user_agent LIKE '%Android%' OR user_agent LIKE '%iPhone%' THEN 'Mobile'
                    WHEN user_agent LIKE '%Tablet%' OR user_agent LIKE '%iPad%' THEN 'Tablet'
                    ELSE 'Desktop'
                END as device_type"),
                DB::raw('COUNT(*) as clicks')
            )
            ->whereBetween('clicked_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('device_type');

        if ($linkFilter) {
            $query = $this->applyLinkFilterToQuery($query, $linkFilter);
        }

        $data = $query->get();

        return [
            'labels' => $data->pluck('device_type')->toArray(),
            'data' => $data->pluck('clicks')->toArray(),
            'type' => 'pie',
            'title' => 'Clicks by Device Type',
        ];
    }

    private function getTrafficSources(array $dateRange, ?array $linkFilter, array $config): array
    {
        $limit = $config['limit'] ?? 8;

        $query = DB::table('clicks')
            ->select(
                DB::raw("CASE
                    WHEN referer IS NULL OR referer = '' THEN 'Direct'
                    WHEN referer LIKE '%google%' THEN 'Google'
                    WHEN referer LIKE '%facebook%' THEN 'Facebook'
                    WHEN referer LIKE '%twitter%' OR referer LIKE '%t.co%' THEN 'Twitter'
                    WHEN referer LIKE '%linkedin%' THEN 'LinkedIn'
                    WHEN referer LIKE '%instagram%' THEN 'Instagram'
                    WHEN referer LIKE '%youtube%' THEN 'YouTube'
                    ELSE 'Other'
                END as source"),
                DB::raw('COUNT(*) as clicks')
            )
            ->whereBetween('clicked_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('source')
            ->orderBy('clicks', 'desc')
            ->limit($limit);

        if ($linkFilter) {
            $query = $this->applyLinkFilterToQuery($query, $linkFilter);
        }

        $data = $query->get();

        return [
            'labels' => $data->pluck('source')->toArray(),
            'data' => $data->pluck('clicks')->toArray(),
            'type' => 'pie',
            'title' => 'Traffic Sources',
        ];
    }

    private function getLinkPerformance(array $dateRange, ?array $linkFilter, array $config): array
    {
        $limit = $config['limit'] ?? 20;

        $query = DB::table('links')
            ->leftJoin('clicks', function ($join) use ($dateRange) {
                $join->on('links.id', '=', 'clicks.link_id')
                    ->whereBetween('clicks.clicked_at', [$dateRange['start'], $dateRange['end']]);
            })
            ->leftJoin('link_groups', 'links.group_id', '=', 'link_groups.id')
            ->select(
                'links.short_code',
                'links.original_url',
                'link_groups.name as group_name',
                'links.is_active',
                'links.created_at',
                DB::raw('COUNT(clicks.id) as total_clicks'),
                DB::raw('COUNT(DISTINCT clicks.ip_address) as unique_clicks')
            )
            ->groupBy('links.id', 'links.short_code', 'links.original_url', 'link_groups.name', 'links.is_active', 'links.created_at')
            ->orderBy('total_clicks', 'desc')
            ->limit($limit);

        if ($linkFilter) {
            if (isset($linkFilter['linkIds'])) {
                $query->whereIn('links.id', $linkFilter['linkIds']);
            }
            if (isset($linkFilter['groupIds'])) {
                $query->whereIn('links.group_id', $linkFilter['groupIds']);
            }
        }

        $data = $query->get();

        return [
            'columns' => ['Link', 'Group', 'Total Clicks', 'Unique Clicks', 'Status', 'Created'],
            'data' => $data->map(function ($item) {
                return [
                    'short_code' => $item->short_code,
                    'group_name' => $item->group_name ?? 'Uncategorized',
                    'total_clicks' => $item->total_clicks,
                    'unique_clicks' => $item->unique_clicks,
                    'is_active' => $item->is_active ? 'Active' : 'Inactive',
                    'created_at' => Carbon::parse($item->created_at)->format('M j, Y'),
                    'url' => $item->original_url,
                ];
            })->toArray(),
            'type' => 'table',
            'title' => 'Link Performance',
        ];
    }

    private function getBrowserStats(array $dateRange, ?array $linkFilter, array $config): array
    {
        $limit = $config['limit'] ?? 8;

        $query = DB::table('clicks')
            ->select(
                DB::raw("CASE
                    WHEN user_agent LIKE '%Chrome%' AND user_agent NOT LIKE '%Edge%' THEN 'Chrome'
                    WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
                    WHEN user_agent LIKE '%Safari%' AND user_agent NOT LIKE '%Chrome%' THEN 'Safari'
                    WHEN user_agent LIKE '%Edge%' THEN 'Edge'
                    WHEN user_agent LIKE '%Opera%' THEN 'Opera'
                    ELSE 'Other'
                END as browser"),
                DB::raw('COUNT(*) as clicks')
            )
            ->whereBetween('clicked_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('browser')
            ->orderBy('clicks', 'desc')
            ->limit($limit);

        if ($linkFilter) {
            $query = $this->applyLinkFilterToQuery($query, $linkFilter);
        }

        $data = $query->get();

        return [
            'labels' => $data->pluck('browser')->toArray(),
            'data' => $data->pluck('clicks')->toArray(),
            'type' => 'bar',
            'title' => 'Browser Statistics',
        ];
    }

    private function getUtmCampaigns(array $dateRange, ?array $linkFilter, array $config): array
    {
        $limit = $config['limit'] ?? 10;

        $query = DB::table('clicks')
            ->select('utm_campaign', DB::raw('COUNT(*) as clicks'))
            ->whereBetween('clicked_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('utm_campaign')
            ->where('utm_campaign', '!=', '')
            ->groupBy('utm_campaign')
            ->orderBy('clicks', 'desc')
            ->limit($limit);

        if ($linkFilter) {
            $query = $this->applyLinkFilterToQuery($query, $linkFilter);
        }

        $data = $query->get();

        return [
            'labels' => $data->pluck('utm_campaign')->toArray(),
            'data' => $data->pluck('clicks')->toArray(),
            'type' => 'bar',
            'title' => 'UTM Campaigns',
        ];
    }

    private function getComparisonValue(string $metric, array $dateRange, ?array $linkFilter, array $config): ?array
    {
        $period = $config['comparison_period'] ?? 'previous_period';

        if ($period !== 'previous_period') {
            return null;
        }

        $duration = $dateRange['end']->diffInDays($dateRange['start']);
        $previousStart = $dateRange['start']->copy()->subDays($duration + 1);
        $previousEnd = $dateRange['start']->copy()->subDay();

        $previousRange = ['start' => $previousStart, 'end' => $previousEnd];

        $previousData = match ($metric) {
            'total_clicks' => $this->getTotalClicks($previousRange, $linkFilter, ['show_comparison' => false]),
            'unique_clicks' => $this->getUniqueClicks($previousRange, $linkFilter, ['show_comparison' => false]),
            default => null,
        };

        if (! $previousData) {
            return null;
        }

        $current = $this->getMetricData($metric, array_merge($config, ['show_comparison' => false]), [
            'start_date' => $dateRange['start']->toDateString(),
            'end_date' => $dateRange['end']->toDateString(),
        ])['value'] ?? 0;

        $previous = $previousData['value'];

        if ($previous == 0) {
            $percentChange = $current > 0 ? 100 : 0;
        } else {
            $percentChange = (($current - $previous) / $previous) * 100;
        }

        return [
            'previous_value' => $previous,
            'change' => $current - $previous,
            'percent_change' => round($percentChange, 1),
            'trend' => $percentChange >= 0 ? 'up' : 'down',
        ];
    }

    private function applyLinkFilter($query, array $linkFilter)
    {
        if (isset($linkFilter['linkIds'])) {
            $query->whereIn('link_id', $linkFilter['linkIds']);
        }

        if (isset($linkFilter['groupIds'])) {
            $query->whereHas('link', function ($q) use ($linkFilter) {
                $q->whereIn('group_id', $linkFilter['groupIds']);
            });
        }

        return $query;
    }

    private function applyLinkFilterToQuery($query, array $linkFilter, string $tablePrefix = 'clicks')
    {
        if (isset($linkFilter['linkIds'])) {
            $query->whereIn($tablePrefix === 'clicks' ? 'link_id' : 'id', $linkFilter['linkIds']);
        }

        if (isset($linkFilter['groupIds']) && $tablePrefix !== 'clicks') {
            $query->whereIn($tablePrefix.'.group_id', $linkFilter['groupIds']);
        } elseif (isset($linkFilter['groupIds'])) {
            $query->join('links as filter_links', 'clicks.link_id', '=', 'filter_links.id')
                ->whereIn('filter_links.group_id', $linkFilter['groupIds']);
        }

        return $query;
    }
}
