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
            <x-filament::button
                tag="a"
                href="{{ route('qr.download', ['link' => $link->id, 'format' => 'png', 'size' => 200]) }}"
                icon="heroicon-m-arrow-down-tray"
                size="sm"
                download
            >
                PNG (200px)
            </x-filament::button>
            <x-filament::button
                tag="a"
                href="{{ route('qr.download', ['link' => $link->id, 'format' => 'png', 'size' => 400]) }}"
                icon="heroicon-m-arrow-down-tray"
                size="sm"
                download
            >
                PNG (400px)
            </x-filament::button>
            <x-filament::button
                tag="a"
                href="{{ route('qr.download', ['link' => $link->id, 'format' => 'svg']) }}"
                icon="heroicon-m-arrow-down-tray"
                size="sm"
                color="success"
                download
            >
                SVG (Vector)
            </x-filament::button>
        </div>
    </div>
</div>