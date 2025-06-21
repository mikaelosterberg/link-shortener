<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Report Builder - {{ $report->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
</head>
<body class="bg-gray-50">
    <div id="report-builder" class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center">
                        <a href="{{ route('filament.admin.resources.reports.index') }}" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </a>
                        <h1 class="ml-4 text-xl font-semibold text-gray-900">{{ $report->name }}</h1>
                        <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Builder</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button id="preview-btn" class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                            Preview
                        </button>
                        <button id="save-btn" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Save Report
                        </button>
                        <a href="{{ route('reports.view', $report) }}" target="_blank" class="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">
                            View Report
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex gap-6">
                <!-- Component Library Sidebar -->
                <div class="w-80 bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Component Library</h3>
                    
                    <div class="space-y-4">
                        <div class="component-category">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Metrics</h4>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="component-item" data-type="metric_card" data-title="Metric Card">
                                    <div class="p-3 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50">
                                        <div class="w-8 h-8 bg-blue-100 rounded mx-auto mb-2 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                        </div>
                                        <div class="text-xs text-center text-gray-600">Metric Card</div>
                                    </div>
                                </div>
                                
                                <div class="component-item" data-type="data_table" data-title="Data Table">
                                    <div class="p-3 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50">
                                        <div class="w-8 h-8 bg-green-100 rounded mx-auto mb-2 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <div class="text-xs text-center text-gray-600">Data Table</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="component-category">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Charts</h4>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="component-item" data-type="line_chart" data-title="Line Chart">
                                    <div class="p-3 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50">
                                        <div class="w-8 h-8 bg-purple-100 rounded mx-auto mb-2 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4"></path>
                                            </svg>
                                        </div>
                                        <div class="text-xs text-center text-gray-600">Line Chart</div>
                                    </div>
                                </div>
                                
                                <div class="component-item" data-type="bar_chart" data-title="Bar Chart">
                                    <div class="p-3 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50">
                                        <div class="w-8 h-8 bg-orange-100 rounded mx-auto mb-2 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                        </div>
                                        <div class="text-xs text-center text-gray-600">Bar Chart</div>
                                    </div>
                                </div>
                                
                                <div class="component-item" data-type="pie_chart" data-title="Pie Chart">
                                    <div class="p-3 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50">
                                        <div class="w-8 h-8 bg-red-100 rounded mx-auto mb-2 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                                            </svg>
                                        </div>
                                        <div class="text-xs text-center text-gray-600">Pie Chart</div>
                                    </div>
                                </div>

                                <div class="component-item" data-type="geographic_map" data-title="Geographic Map">
                                    <div class="p-3 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50">
                                        <div class="w-8 h-8 bg-teal-100 rounded mx-auto mb-2 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"></path>
                                            </svg>
                                        </div>
                                        <div class="text-xs text-center text-gray-600">Map</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="component-category">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Layout</h4>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="component-item" data-type="text_block" data-title="Text Block">
                                    <div class="p-3 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50">
                                        <div class="w-8 h-8 bg-gray-100 rounded mx-auto mb-2 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                                            </svg>
                                        </div>
                                        <div class="text-xs text-center text-gray-600">Text Block</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Canvas -->
                <div class="flex-1 bg-white rounded-lg shadow">
                    <div class="p-6 border-b">
                        <h3 class="text-lg font-medium text-gray-900">Report Canvas</h3>
                        <p class="text-sm text-gray-500">Drag components from the library to build your report</p>
                    </div>
                    
                    <div id="report-canvas" class="p-6 min-h-96 bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg mx-6 mb-6">
                        <div id="canvas-placeholder" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No components yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Drag components from the library to get started</p>
                        </div>
                    </div>
                </div>

                <!-- Properties Panel -->
                <div id="properties-panel" class="w-80 bg-white rounded-lg shadow p-6" style="display: none;">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Component Properties</h3>
                    <div id="properties-content">
                        <!-- Properties will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Component Modal -->
    <div id="component-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4" id="modal-title">Configure Component</h3>
                <div id="modal-content">
                    <!-- Modal content will be loaded here -->
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button id="modal-cancel" class="px-4 py-2 text-sm text-gray-500 bg-gray-100 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                    <button id="modal-save" class="px-4 py-2 text-sm text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        Add Component
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Report Builder JavaScript
        let currentReport = @json($report);
        let reportComponents = [];
        let draggedComponent = null;
        let selectedComponent = null;

        // Initialize drag and drop
        document.addEventListener('DOMContentLoaded', function() {
            initializeDragAndDrop();
            loadExistingComponents();
        });

        function initializeDragAndDrop() {
            // Make component items draggable
            document.querySelectorAll('.component-item').forEach(item => {
                item.addEventListener('dragstart', function(e) {
                    draggedComponent = {
                        type: this.dataset.type,
                        title: this.dataset.title
                    };
                });
                item.draggable = true;
            });

            // Setup drop zone
            const canvas = document.getElementById('report-canvas');
            canvas.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('border-blue-400', 'bg-blue-50');
            });

            canvas.addEventListener('dragleave', function(e) {
                this.classList.remove('border-blue-400', 'bg-blue-50');
            });

            canvas.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('border-blue-400', 'bg-blue-50');
                
                if (draggedComponent) {
                    showComponentModal(draggedComponent);
                    draggedComponent = null;
                }
            });
        }

        function showComponentModal(component) {
            document.getElementById('modal-title').textContent = `Configure ${component.title}`;
            document.getElementById('component-modal').classList.remove('hidden');
            
            // Load component configuration form
            const modalContent = document.getElementById('modal-content');
            modalContent.innerHTML = getComponentConfigForm(component.type);
            
            // Setup modal event handlers
            document.getElementById('modal-cancel').onclick = hideComponentModal;
            document.getElementById('modal-save').onclick = function() {
                addComponentToCanvas(component);
                hideComponentModal();
            };
        }

        function hideComponentModal() {
            document.getElementById('component-modal').classList.add('hidden');
        }

        function getComponentConfigForm(componentType) {
            const baseConfig = `
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <input type="text" id="component-title" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Component title">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Width</label>
                            <select id="component-width" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="4">Small (1/3)</option>
                                <option value="6">Medium (1/2)</option>
                                <option value="8">Large (2/3)</option>
                                <option value="12">Full Width</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Height</label>
                            <select id="component-height" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="2">Short</option>
                                <option value="4">Medium</option>
                                <option value="6">Tall</option>
                                <option value="8">Extra Tall</option>
                            </select>
                        </div>
                    </div>
            `;

            const specificConfig = getSpecificConfigForm(componentType);
            
            return baseConfig + specificConfig + '</div>';
        }

        function getSpecificConfigForm(componentType) {
            switch (componentType) {
                case 'metric_card':
                    return `
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Metric</label>
                            <select id="component-metric" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="total_clicks">Total Clicks</option>
                                <option value="unique_clicks">Unique Visitors</option>
                                <option value="total_links">Total Links</option>
                                <option value="active_links">Active Links</option>
                            </select>
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" id="show-comparison" class="mr-2" checked>
                                <span class="text-sm font-medium text-gray-700">Show comparison with previous period</span>
                            </label>
                        </div>
                    `;
                case 'line_chart':
                case 'bar_chart':
                    return `
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data Source</label>
                            <select id="component-metric" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="clicks_over_time">Clicks Over Time</option>
                                <option value="top_links">Top Performing Links</option>
                                <option value="clicks_by_country">Clicks by Country</option>
                                <option value="browser_stats">Browser Statistics</option>
                                <option value="utm_campaigns">UTM Campaigns</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                            <select id="component-period" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="hourly">Hourly</option>
                                <option value="daily" selected>Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                    `;
                case 'pie_chart':
                    return `
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data Source</label>
                            <select id="component-metric" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="traffic_sources">Traffic Sources</option>
                                <option value="clicks_by_device">Device Types</option>
                                <option value="clicks_by_country">Countries</option>
                                <option value="browser_stats">Browsers</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Limit</label>
                            <input type="number" id="component-limit" value="5" min="3" max="15" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    `;
                case 'data_table':
                    return `
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Table Type</label>
                            <select id="component-metric" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="link_performance">Link Performance</option>
                                <option value="top_links">Top Links</option>
                                <option value="clicks_by_country">Geographic Data</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rows Limit</label>
                            <input type="number" id="component-limit" value="20" min="5" max="100" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    `;
                case 'text_block':
                    return `
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                            <textarea id="component-content" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter your text content..."></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Text Alignment</label>
                            <select id="component-alignment" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="left">Left</option>
                                <option value="center">Center</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                    `;
                default:
                    return '';
            }
        }

        function addComponentToCanvas(componentTemplate) {
            const component = {
                id: Date.now(),
                component_type: componentTemplate.type,
                title: document.getElementById('component-title').value || componentTemplate.title,
                width: parseInt(document.getElementById('component-width').value),
                height: parseInt(document.getElementById('component-height').value),
                position_x: 0,
                position_y: reportComponents.length * 300,
                order_index: reportComponents.length,
                config: getComponentConfig(componentTemplate.type)
            };

            reportComponents.push(component);
            renderCanvas();
            hidePlaceholder();
        }

        function getComponentConfig(componentType) {
            const config = {};
            
            switch (componentType) {
                case 'metric_card':
                    config.metric = document.getElementById('component-metric').value;
                    config.show_comparison = document.getElementById('show-comparison').checked;
                    config.comparison_period = 'previous_period';
                    break;
                case 'line_chart':
                case 'bar_chart':
                    config.metric = document.getElementById('component-metric').value;
                    config.period = document.getElementById('component-period').value;
                    break;
                case 'pie_chart':
                    config.metric = document.getElementById('component-metric').value;
                    config.limit = parseInt(document.getElementById('component-limit').value);
                    break;
                case 'data_table':
                    config.metric = document.getElementById('component-metric').value;
                    config.limit = parseInt(document.getElementById('component-limit').value);
                    break;
                case 'text_block':
                    config.content = document.getElementById('component-content').value;
                    config.alignment = document.getElementById('component-alignment').value;
                    break;
            }
            
            return config;
        }

        function renderCanvas() {
            const canvas = document.getElementById('report-canvas');
            const componentsHtml = reportComponents.map(component => `
                <div class="component-wrapper bg-white rounded-lg shadow p-4 mb-4 border border-gray-200 relative" data-component-id="${component.id}">
                    <div class="component-header flex items-center justify-between mb-3">
                        <h4 class="font-medium text-gray-900">${component.title}</h4>
                        <div class="flex items-center space-x-2">
                            <button class="edit-component text-gray-400 hover:text-gray-600" data-component-id="${component.id}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                            </button>
                            <button class="delete-component text-gray-400 hover:text-red-600" data-component-id="${component.id}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="component-preview bg-gray-100 rounded h-32 flex items-center justify-center">
                        <span class="text-gray-500 text-sm">${getComponentPreviewText(component.component_type)}</span>
                    </div>
                </div>
            `).join('');
            
            canvas.innerHTML = componentsHtml;
            
            // Add event listeners
            document.querySelectorAll('.delete-component').forEach(btn => {
                btn.addEventListener('click', function() {
                    const componentId = parseInt(this.dataset.componentId);
                    deleteComponent(componentId);
                });
            });
        }

        function getComponentPreviewText(componentType) {
            switch (componentType) {
                case 'metric_card':
                    return '📊 Metric Card Preview';
                case 'line_chart':
                    return '📈 Line Chart Preview';
                case 'bar_chart':
                    return '📊 Bar Chart Preview';
                case 'pie_chart':
                    return '🥧 Pie Chart Preview';
                case 'data_table':
                    return '📋 Data Table Preview';
                case 'geographic_map':
                    return '🗺️ Geographic Map Preview';
                case 'text_block':
                    return '📝 Text Block Preview';
                default:
                    return 'Component Preview';
            }
        }

        function deleteComponent(componentId) {
            reportComponents = reportComponents.filter(c => c.id !== componentId);
            renderCanvas();
            
            if (reportComponents.length === 0) {
                showPlaceholder();
            }
        }

        function hidePlaceholder() {
            document.getElementById('canvas-placeholder').style.display = 'none';
        }

        function showPlaceholder() {
            document.getElementById('canvas-placeholder').style.display = 'block';
        }

        function loadExistingComponents() {
            // Load existing components if any
            // This would fetch from the server
        }

        // Save functionality
        document.getElementById('save-btn').addEventListener('click', function() {
            if (reportComponents.length === 0) {
                alert('Please add at least one component before saving.');
                return;
            }

            const data = {
                components: reportComponents,
                _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            };

            fetch(`/reports/${currentReport.id}/components`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': data._token
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Report saved successfully!');
                } else {
                    alert('Error saving report. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving report. Please try again.');
            });
        });
    </script>
</body>
</html>