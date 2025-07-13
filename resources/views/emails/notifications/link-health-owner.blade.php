<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your Links Health Alert</title>
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
            <h1>🚨 Your Links Health Alert</h1>
            <p>{{ $total_count }} of your link{{ $total_count > 1 ? 's have' : ' has' }} failed health checks</p>
        </div>

        <div class="content">
            <p>Hello {{ $user->name }},</p>
            <p>We've detected issues with {{ $total_count }} of your shortened link{{ $total_count > 1 ? 's' : '' }}. Please review and fix the issues below:</p>

            <h3>Your Failed Links:</h3>
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
                            🔧 Fix This Link
                        </a>
                    </div>
                </div>
            @endforeach

            <h3>How to fix your links:</h3>
            <ul>
                <li><strong>Click "🔧 Fix This Link"</strong> to edit the destination URL directly</li>
                <li>Test the destination URL in a new browser tab to confirm it's working</li>
                <li>Update the destination URL if the content has moved to a new location</li>
                <li>Contact your website administrator if you need help</li>
            </ul>
        </div>

        <div class="footer">
            <p>This is an automated notification about your links.</p>
            <p>Need help? Contact your system administrator.</p>
        </div>
    </div>
</body>
</html>