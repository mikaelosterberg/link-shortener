<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $report->name }} - Report</title>
    <link rel="stylesheet" href="{{ asset('css/reports.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b no-print">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center">
                        @auth
                            <a href="{{ route('filament.admin.resources.reports.index') }}" class="text-gray-500 hover:text-gray-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </a>
                            <h1 class="ml-4 text-xl font-semibold text-gray-900">{{ $report->name }}</h1>
                        @else
                            <h1 class="text-xl font-semibold text-gray-900">{{ $report->name }}</h1>
                        @endauth
                        @if($report->description)
                            <span class="ml-2 text-sm text-gray-500">- {{ $report->description }}</span>
                        @endif
                    </div>
                    <div class="flex items-center space-x-4">
                        @if(isset($reportData['dateRange']))
                            <div class="text-sm text-gray-500">
                                {{ $reportData['dateRange']['start_date'] }} to {{ $reportData['dateRange']['end_date'] }}
                            </div>
                        @endif
                        @can('update', $report)
                            <a href="{{ route('reports.builder', $report) }}" class="btn btn-blue">
                                Edit Report
                            </a>
                        @endcan
                        <button onclick="window.print()" class="btn btn-gray">
                            Print / PDF
                        </button>
                        <button id="refresh-btn" class="btn btn-green">
                            Refresh Data
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @if(($reportData['totalComponents'] ?? 0) == 0)
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Empty Report</h3>
                    <p class="mt-1 text-sm text-gray-500">This report has no components yet.</p>
                    @can('update', $report)
                        <div class="mt-6">
                            <a href="{{ route('reports.builder', $report) }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <svg class="mr-2 -ml-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Components
                            </a>
                        </div>
                    @endcan
                </div>
            @else
                <div id="report-components" class="space-y-8">
                    {{-- Render containers with their components --}}
                    @if(isset($reportData['containers']) && count($reportData['containers']) > 0)
                        @foreach($reportData['containers'] as $container)
                            <div class="container bg-white rounded-lg shadow-sm overflow-hidden mb-8">
                                <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                                    <h2 class="text-lg font-medium text-gray-900">{{ $container['name'] }}</h2>
                                </div>
                                <div class="p-6">
                                    <div class="components-grid" style="
                                        display: flex;
                                        flex-direction: {{ $container['flex_direction'] }};
                                        justify-content: {{ $container['justify_content'] }};
                                        align-items: {{ $container['align_items'] }};
                                        gap: {{ $container['gap'] }};
                                        min-height: {{ $container['min_height'] }};
                                        flex-wrap: wrap;
                                    ">
                                        @foreach($container['components'] as $component)
                                            <div class="component bg-gray-50 rounded-lg p-4" style="
                                                flex-basis: {{ $component['flex_basis'] ?? 'auto' }};
                                                flex-grow: {{ $component['flex_grow'] ?? 1 }};
                                                flex-shrink: {{ $component['flex_shrink'] ?? 1 }};
                                            " data-component-id="{{ $component['id'] }}" data-component-type="{{ $component['type'] }}">
                                                <div class="mb-3">
                                                    <h3 class="text-lg font-medium text-gray-900">{{ $component['title'] }}</h3>
                                                </div>
                                                <div class="component-content" data-component-data="{{ json_encode($component['data']) }}">
                                                    @include('reports.partials.component-content', ['component' => $component])
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif

                    {{-- Render standalone components (legacy support) --}}
                    @if(isset($reportData['components']) && count($reportData['components']) > 0)
                        @foreach($reportData['components'] as $component)
                        <div class="component bg-white rounded-lg shadow-sm overflow-hidden" data-component-id="{{ $component['id'] }}" data-component-type="{{ $component['type'] }}">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-medium text-gray-900">{{ $component['title'] }}</h3>
                            </div>
                            <div class="p-6">
                                <div class="component-content" data-component-data="{{ json_encode($component['data']) }}">
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
                                                        <span class="text-green-600">+{{ $component['data']['comparison']['percent_change'] }}%</span>
                                                    @else
                                                        <svg class="w-4 h-4 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17l-9.2-9.2M7 7v10m10-10H7"></path>
                                                        </svg>
                                                        <span class="text-red-600">{{ $component['data']['comparison']['percent_change'] }}%</span>
                                                    @endif
                                                    <span class="text-gray-500 ml-2">vs previous period</span>
                                                </div>
                                            @endif
                                        </div>
                                    @elseif($component['type'] === 'data_table')
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        @if(isset($component['data']['columns']))
                                                            @foreach($component['data']['columns'] as $column)
                                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $column }}</th>
                                                            @endforeach
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    @if(isset($component['data']['data']))
                                                        @foreach($component['data']['data'] as $row)
                                                            <tr>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $row['short_code'] ?? '' }}</td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row['group_name'] ?? '' }}</td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row['total_clicks'] ?? '' }}</td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row['unique_clicks'] ?? '' }}</td>
                                                                <td class="px-6 py-4 whitespace-nowrap">
                                                                    @if(($row['is_active'] ?? '') === 'Active')
                                                                        <span class="status-badge bg-green-100 text-green-800">Active</span>
                                                                    @else
                                                                        <span class="status-badge bg-red-100 text-red-800">Inactive</span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row['created_at'] ?? '' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    @elseif(in_array($component['type'], ['line_chart', 'bar_chart', 'pie_chart']))
                                        <div class="chart-container" style="height: 400px;">
                                            <canvas id="chart-{{ $component['id'] }}" class="w-full h-full"></canvas>
                                        </div>
                                    @elseif($component['type'] === 'text_block')
                                        <div class="text-block" style="text-align: {{ $component['config']['alignment'] ?? 'left' }}">
                                            {!! nl2br(e($component['data']['content'] ?? $component['config']['content'] ?? '')) !!}
                                        </div>
                                    @else
                                        <div class="text-center py-8 text-gray-500">
                                            Component type "{{ $component['type'] }}" not yet implemented
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>
            @endif

            <!-- Report Footer -->
            <div class="mt-12 pt-8 border-t border-gray-200 text-center text-sm text-gray-500">
                <p>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
                <p class="mt-1">Report: {{ $report->name }}</p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            renderCharts();
            
            // Refresh functionality
            document.getElementById('refresh-btn')?.addEventListener('click', function() {
                window.location.reload();
            });
        });

        function renderCharts() {
            @if(isset($reportData['containers']) && count($reportData['containers']) > 0)
                @foreach($reportData['containers'] as $container)
                    @foreach($container['components'] as $component)
                        @if(in_array($component['type'], ['line_chart', 'bar_chart', 'pie_chart']))
                            renderChart{{ $component['id'] }}();
                        @endif
                    @endforeach
                @endforeach
            @endif
            
            @if(isset($reportData['components']) && count($reportData['components']) > 0)
                @foreach($reportData['components'] as $component)
                    @if(in_array($component['type'], ['line_chart', 'bar_chart', 'pie_chart']))
                        renderChart{{ $component['id'] }}();
                    @endif
                @endforeach
            @endif
        }

        {{-- Chart functions for container components --}}
        @if(isset($reportData['containers']) && count($reportData['containers']) > 0)
            @foreach($reportData['containers'] as $container)
                @foreach($container['components'] as $component)
                    @if(in_array($component['type'], ['line_chart', 'bar_chart', 'pie_chart']))
                        function renderChart{{ $component['id'] }}() {
                            const ctx = document.getElementById('chart-{{ $component['id'] }}');
                            if (!ctx) return;

                            const data = @json($component['data']);
                            
                            @if($component['type'] === 'line_chart')
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: data.labels || [],
                                datasets: [{
                                    label: data.title || 'Data',
                                    data: data.data || [],
                                    borderColor: 'rgb(59, 130, 246)',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    tension: 0.1,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    @elseif($component['type'] === 'bar_chart')
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: data.labels || [],
                                datasets: [{
                                    label: data.title || 'Data',
                                    data: data.data || [],
                                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                                    borderColor: 'rgb(59, 130, 246)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    @elseif($component['type'] === 'pie_chart')
                        new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: data.labels || [],
                                datasets: [{
                                    data: data.data || [],
                                    backgroundColor: [
                                        'rgba(59, 130, 246, 0.8)',
                                        'rgba(16, 185, 129, 0.8)',
                                        'rgba(245, 158, 11, 0.8)',
                                        'rgba(239, 68, 68, 0.8)',
                                        'rgba(139, 92, 246, 0.8)',
                                        'rgba(236, 72, 153, 0.8)',
                                        'rgba(14, 165, 233, 0.8)',
                                        'rgba(34, 197, 94, 0.8)'
                                    ],
                                    borderColor: [
                                        'rgb(59, 130, 246)',
                                        'rgb(16, 185, 129)',
                                        'rgb(245, 158, 11)',
                                        'rgb(239, 68, 68)',
                                        'rgb(139, 92, 246)',
                                        'rgb(236, 72, 153)',
                                        'rgb(14, 165, 233)',
                                        'rgb(34, 197, 94)'
                                    ],
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'right'
                                    }
                                }
                            }
                        });
                            @endif
                        }
                    @endif
                @endforeach
            @endforeach
        @endif

        {{-- Chart functions for standalone components --}}
        @if(isset($reportData['components']) && count($reportData['components']) > 0)
            @foreach($reportData['components'] as $component)
                @if(in_array($component['type'], ['line_chart', 'bar_chart', 'pie_chart']))
                    function renderChart{{ $component['id'] }}() {
                        const ctx = document.getElementById('chart-{{ $component['id'] }}');
                        if (!ctx) return;

                        const data = @json($component['data']);
                        
                        @if($component['type'] === 'line_chart')
                            new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: data.labels || [],
                                    datasets: [{
                                        label: data.title || 'Data',
                                        data: data.data || [],
                                        borderColor: 'rgb(59, 130, 246)',
                                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                        tension: 0.1,
                                        fill: true
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true
                                        }
                                    }
                                }
                            });
                        @elseif($component['type'] === 'bar_chart')
                            new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: data.labels || [],
                                    datasets: [{
                                        label: data.title || 'Data',
                                        data: data.data || [],
                                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                                        borderColor: 'rgb(59, 130, 246)',
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true
                                        }
                                    }
                                }
                            });
                        @elseif($component['type'] === 'pie_chart')
                            new Chart(ctx, {
                                type: 'pie',
                                data: {
                                    labels: data.labels || [],
                                    datasets: [{
                                        data: data.data || [],
                                        backgroundColor: [
                                            'rgba(59, 130, 246, 0.8)',
                                            'rgba(16, 185, 129, 0.8)',
                                            'rgba(245, 158, 11, 0.8)',
                                            'rgba(239, 68, 68, 0.8)',
                                            'rgba(139, 92, 246, 0.8)',
                                            'rgba(236, 72, 153, 0.8)',
                                            'rgba(14, 165, 233, 0.8)',
                                            'rgba(34, 197, 94, 0.8)'
                                        ],
                                        borderColor: [
                                            'rgb(59, 130, 246)',
                                            'rgb(16, 185, 129)',
                                            'rgb(245, 158, 11)',
                                            'rgb(239, 68, 68)',
                                            'rgb(139, 92, 246)',
                                            'rgb(236, 72, 153)',
                                            'rgb(14, 165, 233)',
                                            'rgb(34, 197, 94)'
                                        ],
                                        borderWidth: 2
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'right'
                                        }
                                    }
                                }
                            });
                        @endif
                    }
                @endif
            @endforeach
        @endif
    </script>
</body>
</html>