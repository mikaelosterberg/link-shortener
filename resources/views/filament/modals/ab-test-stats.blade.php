<div class="space-y-6">
    {{-- Test Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</div>
            <div class="mt-1 flex items-center">
                @if($abTest->isActiveNow())
                    <x-filament::badge color="success">Active</x-filament::badge>
                @else
                    <x-filament::badge color="gray">Inactive</x-filament::badge>
                @endif
            </div>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Clicks</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                {{ number_format($totalClicks) }}
            </div>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Variants</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                {{ $variants->count() }}
            </div>
        </div>
    </div>

    {{-- Test Period --}}
    @if($abTest->starts_at || $abTest->ends_at)
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">Test Period</h4>
            <div class="text-sm text-blue-700 dark:text-blue-300">
                @if($abTest->starts_at)
                    <div>Start: {{ $abTest->starts_at->format('M j, Y g:i A') }}</div>
                @endif
                @if($abTest->ends_at)
                    <div>End: {{ $abTest->ends_at->format('M j, Y g:i A') }}</div>
                @else
                    <div>End: No end date specified</div>
                @endif
            </div>
        </div>
    @endif

    {{-- Variant Performance --}}
    <div>
        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Variant Performance</h4>
        
        <div class="space-y-4">
            @foreach($variants as $variant)
                @php
                    $clickPercentage = $totalClicks > 0 ? round(($variant->clicks_count / $totalClicks) * 100, 1) : 0;
                    $isWinning = $variants->where('clicks_count', '>', $variant->clicks_count)->isEmpty() && $variant->clicks_count > 0;
                @endphp
                
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center space-x-3">
                            <h5 class="font-medium text-gray-900 dark:text-white">{{ $variant->name }}</h5>
                            @if($isWinning && $totalClicks > 0)
                                <x-filament::badge color="success" size="sm">Leading</x-filament::badge>
                            @endif
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Weight: {{ $variant->weight }}%
                        </div>
                    </div>
                    
                    <div class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                        <div class="truncate">{{ $variant->url }}</div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Clicks</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ number_format($variant->clicks_count) }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Traffic Share</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $clickPercentage }}%
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                                    (Weight: {{ $variant->weight }}%)
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Click Distribution Bar --}}
                    @if($totalClicks > 0)
                        <div class="mt-3">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $clickPercentage }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Performance Insights --}}
    @if($totalClicks > 0)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
            <h4 class="text-sm font-medium text-yellow-900 dark:text-yellow-100 mb-2">Insights</h4>
            <div class="text-sm text-yellow-700 dark:text-yellow-300">
                @php
                    $leadingVariant = $variants->sortByDesc('clicks_count')->first();
                    $expectedClicks = $totalClicks * ($leadingVariant->weight / 100);
                    $actualClicks = $leadingVariant->clicks_count;
                    $performance = $expectedClicks > 0 ? round(($actualClicks / $expectedClicks) * 100, 1) : 0;
                @endphp
                
                @if($totalClicks < 100)
                    <p>• Collect more data for statistically significant results ({{ $totalClicks }}/100+ clicks recommended)</p>
                @endif
                
                @if($leadingVariant->clicks_count > 0)
                    <p>• "{{ $leadingVariant->name }}" is performing at {{ $performance }}% of expected rate</p>
                @endif
                
                @if($variants->where('clicks_count', 0)->count() > 0)
                    <p>• Some variants have not received any clicks yet</p>
                @endif
            </div>
        </div>
    @else
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-gray-500 dark:text-gray-400">
                No clicks recorded yet. Share your link to start collecting data!
            </div>
        </div>
    @endif
</div>