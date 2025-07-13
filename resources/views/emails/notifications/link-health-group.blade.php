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
            <p>{{ $total_count }} link{{ $total_count > 1 ? 's have' : ' has' }} failed health checks</p>
        </div>

        <div class="content">
            <p><strong>Notification Group:</strong> {{ $group_name }}</p>
            <p><strong>Check Time:</strong> <span class="timestamp">{{ now()->format('Y-m-d H:i:s T') }}</span></p>

            <h3>Failed Links:</h3>
            @foreach ($failed_links as $link)
                <div class="link-item">
                    <div class="link-url">{{ $link->original_url }}</div>
                    <div><strong>Short URL:</strong> <a href="{{ $link->full_url }}" style="color: #007bff;">{{ $link->full_url }}</a></div>
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

            @if ($total_count > $failed_links->count())
                <p><em>... and {{ $total_count - $failed_links->count() }} more links</em></p>
            @endif
        </div>

        <div class="footer">
            <p>This is an automated notification from your Link Health Monitor.</p>
            <p>Please check your links and fix any issues.</p>
        </div>
    </div>
</body>
</html>