<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Maintenance Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #17a2b8; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .maintenance-info { background: white; padding: 20px; border-radius: 5px; margin: 15px 0; }
        .timestamp { color: #6c757d; font-size: 0.9em; }
        .schedule-box { background: #e7f3ff; border: 1px solid #b8daff; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Maintenance Notification</h1>
            <p>Scheduled System Maintenance</p>
        </div>

        <div class="content">
            <div class="maintenance-info">
                <h3>Maintenance Details</h3>
                <p><strong>Message:</strong> {{ $message }}</p>
                
                @if ($scheduled_time)
                    <div class="schedule-box">
                        <h4>⏰ Scheduled Time</h4>
                        <p><strong>{{ $scheduled_time->format('Y-m-d H:i:s T') }}</strong></p>
                        <p><em>{{ $scheduled_time->diffForHumans() }}</em></p>
                    </div>
                @endif

                <p><strong>Notification Time:</strong> <span class="timestamp">{{ $timestamp->format('Y-m-d H:i:s T') }}</span></p>
                
                @if (!empty($affected_services))
                    <p><strong>Affected Services:</strong></p>
                    <ul>
                        @foreach ($affected_services as $service)
                            <li>{{ $service }}</li>
                        @endforeach
                    </ul>
                @endif

                @if (!empty($expected_duration))
                    <p><strong>Expected Duration:</strong> {{ $expected_duration }}</p>
                @endif

                @if (!empty($impact_description))
                    <p><strong>Expected Impact:</strong> {{ $impact_description }}</p>
                @endif
            </div>

            <div style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 15px 0;">
                <strong>What to expect:</strong> 
                <ul style="margin: 10px 0 0 20px;">
                    <li>Some services may be temporarily unavailable</li>
                    <li>Link redirects will continue to work normally</li>
                    <li>Analytics collection may be briefly interrupted</li>
                    <li>We'll send an update when maintenance is complete</li>
                </ul>
            </div>
        </div>

        <div class="footer">
            <p>This is an automated maintenance notification.</p>
            <p>Thank you for your patience during our maintenance window.</p>
        </div>
    </div>
</body>
</html>