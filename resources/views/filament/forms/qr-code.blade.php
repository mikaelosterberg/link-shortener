<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium">QR Code for {{ url($getRecord()->short_code) }}</h3>
    </div>
    
    <div class="flex flex-col sm:flex-row gap-6">
        <!-- QR Code Display -->
        <div class="flex-shrink-0">
            <div class="bg-white p-4 rounded-lg border shadow-sm">
                <img 
                    src="{{ route('qr.display', ['link' => $getRecord()->id, 'size' => 200]) }}" 
                    alt="QR Code for {{ $getRecord()->short_code }}"
                    class="w-48 h-48"
                    style="image-rendering: pixelated;"
                />
            </div>
        </div>
        
        <!-- Download Options -->
        <div class="flex-1 space-y-4">
            <div>
                <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Download Options</h4>
                <div class="flex flex-wrap gap-2">
                    <a 
                        href="{{ route('qr.download', ['link' => $getRecord()->id, 'format' => 'png', 'size' => 200]) }}"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        download
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        PNG (200x200)
                    </a>
                    <a 
                        href="{{ route('qr.download', ['link' => $getRecord()->id, 'format' => 'png', 'size' => 400]) }}"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        download
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        PNG (400x400)
                    </a>
                    <a 
                        href="{{ route('qr.download', ['link' => $getRecord()->id, 'format' => 'svg']) }}"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                        download
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        SVG (Vector)
                    </a>
                </div>
            </div>
            
            <div>
                <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Usage Instructions</h4>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li>• Scan with any QR code reader</li>
                    <li>• Links to: <span class="font-mono text-xs">{{ url($getRecord()->short_code) }}</span></li>
                    <li>• PNG format for print materials</li>
                    <li>• SVG format for scalable graphics</li>
                </ul>
            </div>
        </div>
    </div>
</div>