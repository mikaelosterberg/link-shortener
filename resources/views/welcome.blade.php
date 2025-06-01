<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Link Shortener') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon" sizes="any">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            background: #0a0a0a;
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 600px;
            padding: 2rem;
            text-align: center;
        }
        
        h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(to right, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        p {
            font-size: 1.25rem;
            color: #a0a0a0;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .button {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
            margin: 0 0.5rem;
        }
        
        .button:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }
        
        .button-secondary {
            background: transparent;
            border: 2px solid #3b82f6;
        }
        
        .button-secondary:hover {
            background: #3b82f6;
        }
        
        .stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 4rem;
            padding-top: 3rem;
            border-top: 1px solid #222;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #3b82f6;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Link Shortener</h1>
        <p>Shorten your URLs, track clicks, and manage your links with our powerful platform.</p>
        
        <div style="margin-top: 2rem;">
            @auth
                <a href="/admin" class="button">Go to Dashboard</a>
            @else
                <a href="/admin/login" class="button">Login</a>
                <a href="https://github.com/robwent/link-shortener" class="button button-secondary">View on GitHub</a>
            @endauth
        </div>
        
        <div class="stats">
            <div class="stat">
                <div class="stat-number">{{ \App\Models\Link::count() }}</div>
                <div class="stat-label">Links Created</div>
            </div>
            <div class="stat">
                <div class="stat-number">{{ \App\Models\Click::count() }}</div>
                <div class="stat-label">Total Clicks</div>
            </div>
            <div class="stat">
                <div class="stat-number">{{ \App\Models\User::count() }}</div>
                <div class="stat-label">Active Users</div>
            </div>
        </div>
    </div>
</body>
</html>