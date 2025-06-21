@if($component['type'] === 'metric_card')
    <div class="metric-card">
        <div class="text-3xl font-bold text-gray-900 mb-2">
            {{ $component['data']['formatted_value'] ?? $component['data']['value'] ?? 'N/A' }}
        </div>
        <div class="text-sm text-gray-500 mb-4">{{ $component['data']['label'] ?? 'Metric' }}</div>
        @if(isset($component['data']['comparison']) && $component['data']['comparison'])
            <div class="flex items-center text-sm">
                @if($component['data']['comparison']['trend'] === 'up')
                    <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7m-10 0h10"></path>
                    </svg>
                    <span class="text-green-600">{{ $component['data']['comparison']['percent_change'] }}%</span>
                @else
                    <svg class="w-4 h-4 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13.5l-9.2 9.2M7 7v10m0 0h10m-10 0"></path>
                    </svg>
                    <span class="text-red-600">{{ $component['data']['comparison']['percent_change'] }}%</span>
                @endif
                <span class="text-gray-500 ml-1">vs previous period</span>
            </div>
        @endif
    </div>
@elseif(in_array($component['type'], ['line_chart', 'bar_chart', 'pie_chart']))
    <div class="chart-container" style="height: 300px; position: relative;">
        <canvas id="chart-{{ $component['id'] }}" style="max-height: 300px;"></canvas>
    </div>
@elseif($component['type'] === 'data_table')
    <div class="table-container overflow-x-auto">
        @if(isset($component['data']['data']) && count($component['data']['data']) > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @if(isset($component['data']['columns']))
                            @foreach($component['data']['columns'] as $column)
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ $column }}
                                </th>
                            @endforeach
                        @else
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($component['data']['data'] as $row)
                        <tr>
                            @if(is_array($row))
                                @foreach($row as $cell)
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $cell }}
                                    </td>
                                @endforeach
                            @else
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" colspan="2">
                                    {{ $row }}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center py-8 text-gray-500">No data available</div>
        @endif
    </div>
@elseif($component['type'] === 'text_block')
    <div class="text-block prose max-w-none">
        {{ $component['config']['content'] ?? 'No content available' }}
    </div>
@else
    <div class="text-center py-8 text-gray-500">
        Unknown component type: {{ $component['type'] }}
    </div>
@endif