<div class="space-y-4">
    <div class="flex justify-center">
        <div class="bg-white p-6 rounded-lg border shadow-sm">
            <img 
                src="{{ route('qr.display', ['link' => $link->id, 'size' => 250]) }}" 
                alt="QR Code for {{ $link->short_code }}"
                class="w-64 h-64"
                style="image-rendering: pixelated;"
            />
        </div>
    </div>
    
    <div class="text-center space-y-2">
        <p class="font-medium">{{ url($link->short_code) }}</p>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Redirects to: {{ Str::limit($link->original_url, 60) }}
        </p>
    </div>
    
    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
        <h4 class="font-medium text-sm mb-2">Quick Download</h4>
        <p class="text-xs text-gray-600 dark:text-gray-400 mb-4">
            Use the buttons below to download the QR code, or right-click and "Save Image As..." on the QR code above.
        </p>
        <div class="flex flex-wrap gap-2 justify-center mt-4">
            <a 
                href="{{ route('qr.download', ['link' => $link->id, 'format' => 'png', 'size' => 200]) }}"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors"
                download
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                PNG (200px)
            </a>
            <a 
                href="{{ route('qr.download', ['link' => $link->id, 'format' => 'png', 'size' => 400]) }}"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors"
                download
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                PNG (400px)
            </a>
            <a 
                href="{{ route('qr.download', ['link' => $link->id, 'format' => 'svg']) }}"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-white bg-success-600 rounded-lg hover:bg-success-700 focus:ring-2 focus:ring-success-500 focus:ring-offset-2 transition-colors"
                download
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                SVG (Vector)
            </a>
        </div>
    </div>
</div>