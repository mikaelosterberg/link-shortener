<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Link Health Alert</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .link-item { background: white; margin: 10px 0; padding: 15px; border-left: 4px solid #dc3545; }
        .link-url { font-weight: bold; color: #007bff; }
        .error-message { color: #dc3545; margin-top: 5px; }
        .timestamp { color: #6c757d; font-size: 0.9em; }
        .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 Link Health Alert</h1>
            @if($new_count > 0 && $previous_count > 0)
                <p>{{ $new_count }} new failure{{ $new_count > 1 ? 's' : '' }} + {{ $previous_count }} previously failed link{{ $previous_count > 1 ? 's' : '' }}</p>
            @elseif($new_count > 0)
                <p>{{ $new_count }} link{{ $new_count > 1 ? 's have' : ' has' }} failed health checks</p>
            @else
                <p>{{ $previous_count }} link{{ $previous_count > 1 ? 's' : '' }} still failing</p>
            @endif
        </div>

        <div class="content">
            <p><strong>Notification Group:</strong> {{ $group_name }}</p>
            <p><strong>Check Time:</strong> <span class="timestamp">{{ now()->format('Y-m-d H:i:s T') }}</span></p>

            @if($failed_links->isNotEmpty())
                <h3>🆕 Newly Failed Links ({{ $failed_links->count() }}):</h3>
                @foreach ($failed_links as $link)
                <div class="link-item">
                    <div class="link-url">{{ $link->original_url }}</div>
                    <div><strong>Short URL:</strong> <a href="{{ $link->full_url }}" style="color: #007bff;">{{ $link->full_url }}</a></div>
                    @if ($link->creator)
                        <div><strong>Owner:</strong> {{ $link->creator->name }} ({{ $link->creator->email }})</div>
                    @endif
                    @if ($link->group)
                        <div><strong>Group:</strong> {{ $link->group->name }}</div>
                    @endif
                    <div class="error-message">
                        <strong>Error:</strong> {{ $link->health_check_message ?? 'Health check failed' }}
                        @if ($link->http_status_code)
                            (HTTP {{ $link->http_status_code }})
                        @endif
                    </div>
                    @if ($link->last_checked_at)
                        <div class="timestamp">Last checked: {{ $link->last_checked_at->format('Y-m-d H:i:s T') }}</div>
                    @endif
                    <div style="margin-top: 10px;">
                        <a href="{{ url('/admin/links/' . $link->id . '/edit') }}" 
                           style="background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-size: 14px;">
                            🔧 Edit Link
                        </a>
                    </div>
                </div>
                @endforeach
            @endif

            @if(isset($previously_failed_links) && $previously_failed_links->isNotEmpty())
                <h3>⚠️ Previously Failed Links (still broken):</h3>
                <p style="color: #6c757d; font-size: 0.9em;">These links have already been notified {{ $previously_failed_links->first()->notification_count ?? 'multiple' }} time(s)</p>
                @foreach ($previously_failed_links->take(10) as $link)
                    <div class="link-item" style="border-left-color: #ffc107;">
                        <div class="link-url">{{ $link->original_url }}</div>
                        <div><strong>Short URL:</strong> <a href="{{ $link->full_url }}" style="color: #007bff;">{{ $link->full_url }}</a></div>
                        @if ($link->creator)
                            <div><strong>Owner:</strong> {{ $link->creator->name }} ({{ $link->creator->email }})</div>
                        @endif
                        @if ($link->group)
                            <div><strong>Group:</strong> {{ $link->group->name }}</div>
                        @endif
                        <div class="error-message">
                            <strong>Error:</strong> {{ $link->health_check_message ?? 'Health check failed' }}
                            @if ($link->http_status_code)
                                (HTTP {{ $link->http_status_code }})
                            @endif
                        </div>
                        @if ($link->first_failure_detected_at)
                            <div class="timestamp">First failed: {{ $link->first_failure_detected_at->format('Y-m-d H:i:s T') }}</div>
                        @endif
                        <div style="margin-top: 10px;">
                            <a href="{{ url('/admin/links/' . $link->id . '/edit') }}" 
                               style="background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-size: 14px;">
                                🔧 Edit Link
                            </a>
                        </div>
                    </div>
                @endforeach
                @if ($previously_failed_links->count() > 10)
                    <p><em>... and {{ $previously_failed_links->count() - 10 }} more previously failed links</em></p>
                @endif
            @endif
        </div>

        <div class="footer">
            <p>This is an automated notification from your Link Health Monitor.</p>
            <p>Please check your links and fix any issues.</p>
        </div>
    </div>
</body>
</html>