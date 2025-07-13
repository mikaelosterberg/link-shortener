<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>System Alert</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { color: white; padding: 20px; text-align: center; }
        .header.high { background: #dc3545; }
        .header.medium { background: #fd7e14; }
        .header.low { background: #ffc107; color: #333; }
        .content { padding: 20px; background: #f8f9fa; }
        .alert-message { background: white; padding: 20px; border-radius: 5px; margin: 15px 0; }
        .severity { font-weight: bold; text-transform: uppercase; }
        .timestamp { color: #6c757d; font-size: 0.9em; }
        .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header {{ $severity }}">
            <h1>⚠️ System Alert</h1>
            <p class="severity">{{ ucfirst($severity) }} Priority</p>
        </div>

        <div class="content">
            <div class="alert-message">
                <h3>Alert Details</h3>
                <p><strong>Message:</strong> {{ $message }}</p>
                <p><strong>Severity:</strong> <span class="severity">{{ ucfirst($severity) }}</span></p>
                <p><strong>Time:</strong> <span class="timestamp">{{ $timestamp->format('Y-m-d H:i:s T') }}</span></p>
                
                @if (!empty($affected_components))
                    <p><strong>Affected Components:</strong></p>
                    <ul>
                        @foreach ($affected_components as $component)
                            <li>{{ $component }}</li>
                        @endforeach
                    </ul>
                @endif

                @if (!empty($recommended_actions))
                    <p><strong>Recommended Actions:</strong></p>
                    <ul>
                        @foreach ($recommended_actions as $action)
                            <li>{{ $action }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if ($severity === 'high')
                <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 15px 0;">
                    <strong>Immediate Action Required:</strong> This is a high-priority alert that may affect system functionality.
                </div>
            @endif
        </div>

        <div class="footer">
            <p>This is an automated system alert notification.</p>
            <p>Please take appropriate action based on the severity level.</p>
        </div>
    </div>
</body>
</html>