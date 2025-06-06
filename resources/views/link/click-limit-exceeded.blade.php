<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Expired - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full space-y-8 p-8">
        <div class="text-center">
            <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Link No Longer Available
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                This link has reached its maximum number of clicks and is no longer available.
            </p>
        </div>
        
        <div class="rounded-md bg-yellow-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">
                        Click Limit Reached
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>
                            This link was limited to {{ number_format($link->click_limit) }} clicks and has now been deactivated.
                            @if($link->click_count > $link->click_limit)
                                It received {{ number_format($link->click_count) }} total clicks.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center">
            <p class="text-xs text-gray-500">
                If you believe this is an error, please contact the link owner.
            </p>
            <p class="text-xs text-gray-400 mt-2">
                Powered by {{ config('app.name') }}
            </p>
        </div>
    </div>
</body>
</html>