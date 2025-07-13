<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Report Builder - {{ $report->name }}</title>
    <link rel="stylesheet" href="{{ asset('css/reports.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 16px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .sidebar { 
            width: 300px; 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
            align-self: flex-start;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        .canvas { flex: 1; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-left: 20px; }
        .flex { display: flex; }
        .component-item { 
            padding: 12px; 
            border: 2px dashed #d1d5db; 
            border-radius: 8px; 
            margin-bottom: 8px; 
            cursor: pointer;
            text-align: center;
            transition: all 0.2s ease;
            user-select: none;
        }
        .component-item:hover { 
            border-color: #3b82f6; 
            background: #eff6ff; 
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .component-item:active { 
            transform: translateY(0);
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .btn { 
            padding: 8px 16px; 
            border-radius: 6px; 
            border: none; 
            cursor: pointer; 
            margin-right: 8px; 
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .btn-primary { 
            background: #3b82f6; 
            color: white; 
        }
        .btn-primary:hover { 
            background: #2563eb; 
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .btn-primary:disabled { 
            background: #9ca3af; 
            cursor: not-allowed; 
            transform: none;
            box-shadow: none;
        }
        .btn-secondary { 
            background: #f3f4f6; 
            color: #374151; 
        }
        .btn-secondary:hover { 
            background: #e5e7eb; 
            transform: translateY(-1px);
        }
        .drag-active { 
            border-color: #3b82f6 !important; 
            background: #eff6ff !important; 
        }
        .component-wrapper { 
            transition: all 0.2s ease; 
        }
        .component-wrapper:hover { 
            transform: translateY(-1px); 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); 
        }
        
        /* Sortable styles */
        .sortable-ghost {
            opacity: 0.4;
            background: #f3f4f6 !important;
        }
        .sortable-chosen {
            cursor: grabbing;
            transform: rotate(2deg);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25) !important;
        }
        .sortable-drag {
            opacity: 0.8;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25) !important;
        }
        
        /* Container styles */
        .report-container {
            background: white;
            border: 2px dashed #e5e7eb;
            border-radius: 8px;
            margin-bottom: 16px;
            transition: all 0.2s ease;
            min-height: 120px;
        }
        
        .report-container.drag-over {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        
        .report-container.has-components {
            border-style: solid;
            border-color: #d1d5db;
        }
        
        .container-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
            border-radius: 6px 6px 0 0;
        }
        
        .container-content {
            padding: 16px;
            display: flex;
            flex-wrap: wrap;
            min-height: 80px;
        }
        
        .container-placeholder {
            width: 100%;
            text-align: center;
            padding: 32px;
            color: #9ca3af;
            font-size: 14px;
        }
        
        .component-in-container {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
            margin: 4px;
            transition: all 0.2s ease;
        }
        
        /* Cross-container drag feedback */
        .container-content.drag-target {
            background: linear-gradient(45deg, #eff6ff 25%, transparent 25%, transparent 75%, #eff6ff 75%), 
                        linear-gradient(45deg, #eff6ff 25%, transparent 25%, transparent 75%, #eff6ff 75%);
            background-size: 10px 10px;
            background-position: 0 0, 5px 5px;
            border: 2px dashed #3b82f6;
            border-radius: 6px;
        }
        
        .container-content.empty-container {
            background: #f9fafb;
            border: 2px dashed #d1d5db;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100px;
        }
        
        .container-content.empty-container:hover {
            border-color: #9ca3af;
            background: #f3f4f6;
        }
        
        .empty-container-message {
            color: #6b7280;
            font-size: 14px;
            text-align: center;
            pointer-events: none;
        }
        
        .component-in-container:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }
    </style>
</head>
<body style="background: #f9fafb; margin: 0;">
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 24px; color: #111827;">{{ $report->name }} - Report Builder</h1>
            <p style="margin: 8px 0 0 0; color: #6b7280;">Drag and drop components to build your report</p>
        </div>
        
        <div class="flex">
            <div class="sidebar">
                <h3 style="margin: 0 0 16px 0; font-size: 18px;">Layout Builder</h3>
                
                <!-- Container/Layout Section -->
                <div style="margin-bottom: 24px;">
                    <h4 style="margin: 0 0 12px 0; font-size: 14px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Layout</h4>
                    <div class="component-item" draggable="true" data-type="container" data-layout="default" onclick="addContainer('default')">
                        📦 Add Row
                    </div>
                    <div class="component-item" draggable="true" data-type="container" data-layout="centered" onclick="addContainer('centered')">
                        🎯 Centered Row
                    </div>
                    <div class="component-item" draggable="true" data-type="container" data-layout="column" onclick="addContainer('column')">
                        📋 Vertical Stack
                    </div>
                </div>

                <!-- Components Section -->
                <div>
                    <h4 style="margin: 0 0 12px 0; font-size: 14px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Components</h4>
                    <div class="component-item" draggable="true" data-type="metric_card">
                        📊 Metric Card
                    </div>
                    <div class="component-item" draggable="true" data-type="line_chart">
                        📈 Line Chart
                    </div>
                    <div class="component-item" draggable="true" data-type="bar_chart">
                        📊 Bar Chart
                    </div>
                    <div class="component-item" draggable="true" data-type="pie_chart">
                        🥧 Pie Chart
                    </div>
                    <div class="component-item" draggable="true" data-type="data_table">
                        📋 Data Table
                    </div>
                    <div class="component-item" draggable="true" data-type="text_block">
                        📝 Text Block
                    </div>
                </div>
            </div>
            
            <div class="canvas">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; font-size: 18px;">Report Canvas</h3>
                    <div>
                        <button class="btn btn-secondary" onclick="previewReport()">Preview</button>
                        <button id="save-btn" class="btn btn-primary" onclick="saveReport()">Save Report</button>
                    </div>
                </div>
                
                <div id="drop-zone" style="
                    min-height: 400px; 
                    border: 2px dashed #d1d5db; 
                    border-radius: 8px; 
                    padding: 40px; 
                    text-align: center;
                    background: #f9fafb;
                ">
                    <div id="placeholder">
                        <div style="font-size: 48px; margin-bottom: 16px;">📊</div>
                        <h4 style="margin: 0 0 8px 0; color: #374151;">Get Started</h4>
                        <p style="margin: 0; color: #6b7280;">
                            <strong>Step 1:</strong> Click "📦 Add Row" to create a layout container<br>
                            <strong>Step 2:</strong> Drag components into the containers to build your report
                        </p>
                    </div>
                    <div id="components-container"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        console.log('Report Builder loaded for report:', @json($report));
        
        let components = [];
        let containers = [];
        let draggedType = null;
        let draggedLayout = null;

        // Add drag and drop functionality
        function initializeDragAndDrop() {
            document.querySelectorAll('.component-item').forEach(item => {
                item.addEventListener('dragstart', function(e) {
                    draggedType = this.dataset.type;
                    draggedLayout = this.dataset.layout || null;
                    console.log('Dragging:', draggedType, draggedLayout ? `(${draggedLayout} layout)` : '');
                    e.dataTransfer.effectAllowed = 'copy';
                });
                
                item.addEventListener('dragend', function(e) {
                    // Clean up drag state
                    setTimeout(() => {
                        draggedType = null;
                        draggedLayout = null;
                    }, 100);
                });
            });
        }

        const dropZone = document.getElementById('drop-zone');
        
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
            this.style.borderColor = '#3b82f6';
            this.style.background = '#eff6ff';
        });

        dropZone.addEventListener('dragleave', function(e) {
            // Only reset if we're actually leaving the drop zone
            if (!e.currentTarget.contains(e.relatedTarget)) {
                this.style.borderColor = '#d1d5db';
                this.style.background = '#f9fafb';
            }
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#d1d5db';
            this.style.background = '#f9fafb';
            
            if (draggedType) {
                addComponent(draggedType);
                console.log('Dropped:', draggedType);
            } else {
                console.warn('No draggedType set on drop');
            }
        });

        function addComponent(type) {
            if (type === 'container') {
                addContainer(draggedLayout || 'default');
            } else {
                // If there are no containers, add as standalone component
                // If there are containers, require user to drop into a container
                if (containers.length === 0) {
                    showConfigModal(type);
                } else {
                    showToast('Please drop components into a container (row), or create a new container first.', 'warning');
                }
            }
        }

        function showConfigModal(type) {
            const title = getComponentTitle(type);
            const config = getComponentConfig(type);
            
            const modal = document.createElement('div');
            modal.innerHTML = `
                <div style="
                    position: fixed; 
                    top: 0; 
                    left: 0; 
                    right: 0; 
                    bottom: 0; 
                    background: rgba(0,0,0,0.5); 
                    display: flex; 
                    align-items: center; 
                    justify-content: center;
                    z-index: 1000;
                ">
                    <div style="
                        background: white; 
                        padding: 24px; 
                        border-radius: 8px; 
                        width: 400px; 
                        max-height: 80vh;
                        overflow-y: auto;
                    ">
                        <h3 style="margin: 0 0 16px 0;">Configure ${title}</h3>
                        <div id="config-form">${config}</div>
                        <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px;">
                            <button onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                            <button onclick="addConfiguredComponent('${type}')" class="btn btn-primary">Add Component</button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            window.currentModal = modal;
        }

        function closeModal() {
            if (window.currentModal) {
                document.body.removeChild(window.currentModal);
                window.currentModal = null;
            }
        }

        function getComponentConfig(type) {
            switch (type) {
                case 'metric_card':
                    return `
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Title:</label>
                            <input type="text" id="comp-title" value="Total Clicks" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Metric:</label>
                            <select id="comp-metric" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="total_clicks">Total Clicks</option>
                                <option value="unique_clicks">Unique Visitors</option>
                                <option value="total_links">Total Links</option>
                                <option value="active_links">Active Links</option>
                                <option value="links_created">Links Created</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: flex; align-items: center;">
                                <input type="checkbox" id="comp-comparison" checked style="margin-right: 8px;">
                                Show comparison with previous period
                            </label>
                        </div>
                    `;
                case 'line_chart':
                case 'bar_chart':
                    return `
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Title:</label>
                            <input type="text" id="comp-title" value="${getComponentTitle(type)}" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Data Source:</label>
                            <select id="comp-metric" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="clicks_over_time">Clicks Over Time</option>
                                <option value="top_links">Top Performing Links</option>
                                <option value="clicks_by_country">Clicks by Country</option>
                                <option value="browser_stats">Browser Statistics</option>
                                <option value="utm_campaigns">UTM Campaigns</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Period:</label>
                            <select id="comp-period" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="hourly">Hourly</option>
                                <option value="daily" selected>Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                    `;
                case 'pie_chart':
                    return `
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Title:</label>
                            <input type="text" id="comp-title" value="Traffic Sources" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Data Source:</label>
                            <select id="comp-metric" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="traffic_sources">Traffic Sources</option>
                                <option value="clicks_by_device">Device Types</option>
                                <option value="clicks_by_country">Countries</option>
                                <option value="browser_stats">Browsers</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Show Top:</label>
                            <input type="number" id="comp-limit" value="5" min="3" max="15" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                    `;
                case 'data_table':
                    return `
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Title:</label>
                            <input type="text" id="comp-title" value="Link Performance" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Table Type:</label>
                            <select id="comp-metric" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="link_performance">Link Performance</option>
                                <option value="top_links">Top Links</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Default Sort Column:</label>
                            <select id="comp-sort-column" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="total_clicks">Total Clicks</option>
                                <option value="unique_clicks">Unique Clicks</option>
                                <option value="short_code">Link</option>
                                <option value="group_name">Group</option>
                                <option value="created_at">Created Date</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Sort Direction:</label>
                            <select id="comp-sort-direction" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="desc">Descending (High to Low)</option>
                                <option value="asc">Ascending (Low to High)</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Rows Limit:</label>
                            <input type="number" id="comp-limit" value="20" min="5" max="100" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                    `;
                case 'text_block':
                    return `
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Title:</label>
                            <input type="text" id="comp-title" value="Text Block" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Content:</label>
                            <textarea id="comp-content" rows="4" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;" placeholder="Enter your text content..."></textarea>
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Text Type:</label>
                            <select id="comp-text-type" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="p">Paragraph (p)</option>
                                <option value="h1">Heading 1 (h1)</option>
                                <option value="h2">Heading 2 (h2)</option>
                                <option value="h3">Heading 3 (h3)</option>
                                <option value="h4">Heading 4 (h4)</option>
                                <option value="h5">Heading 5 (h5)</option>
                                <option value="h6">Heading 6 (h6)</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Text Alignment:</label>
                            <select id="comp-alignment" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="left">Left</option>
                                <option value="center">Center</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                    `;
                default:
                    return `
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Title:</label>
                            <input type="text" id="comp-title" value="${getComponentTitle(type)}" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                    `;
            }
        }

        function addConfiguredComponent(type) {
            const title = document.getElementById('comp-title')?.value || getComponentTitle(type);
            const metric = document.getElementById('comp-metric')?.value || 'total_clicks';
            
            const component = {
                id: Date.now(),
                type: type,
                title: title,
                config: {
                    metric: metric,
                    period: document.getElementById('comp-period')?.value || 'daily',
                    limit: document.getElementById('comp-limit')?.value || 10,
                    content: document.getElementById('comp-content')?.value || '',
                    text_type: document.getElementById('comp-text-type')?.value || 'p',
                    alignment: document.getElementById('comp-alignment')?.value || 'left',
                    sort_column: document.getElementById('comp-sort-column')?.value || 'total_clicks',
                    sort_direction: document.getElementById('comp-sort-direction')?.value || 'desc',
                    show_comparison: document.getElementById('comp-comparison')?.checked || false
                },
                flex_basis: 'auto',
                flex_grow: 1,
                flex_shrink: 1
            };
            
            // Check if we're adding to a specific container
            if (window.targetContainerId) {
                const container = containers.find(c => c.id === window.targetContainerId);
                if (container) {
                    container.components.push(component);
                    renderContainers();
                    showToast('Component added to container!', 'success');
                } else {
                    showToast('Container not found', 'error');
                }
                window.targetContainerId = null;
            } else {
                // Legacy behavior: add to standalone components
                components.push(component);
                renderComponents();
                showToast('Component added successfully!', 'success');
            }
            
            closeModal();
            console.log('Added configured component:', component);
        }

        function getComponentTitle(type) {
            const titles = {
                'metric_card': 'Metric Card',
                'line_chart': 'Line Chart', 
                'bar_chart': 'Bar Chart',
                'pie_chart': 'Pie Chart',
                'data_table': 'Data Table',
                'text_block': 'Text Block'
            };
            return titles[type] || type;
        }

        function renderComponents() {
            // If we have containers, use container rendering
            if (containers.length > 0) {
                renderContainers();
                return;
            }
            
            // Legacy rendering for standalone components
            const container = document.getElementById('components-container');
            const placeholder = document.getElementById('placeholder');
            
            if (components.length > 0) {
                placeholder.style.display = 'none';
                container.innerHTML = components.map(comp => `
                    <div class="component-item" data-component-id="${comp.id}" style="
                        background: white; 
                        border: 1px solid #e5e7eb; 
                        border-radius: 8px; 
                        padding: 16px; 
                        margin-bottom: 16px;
                    ">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div class="drag-handle" style="
                                    cursor: move; 
                                    color: #9ca3af; 
                                    padding: 8px;
                                    border-radius: 4px;
                                    display: flex;
                                    align-items: center;
                                    gap: 4px;
                                    background: #f9fafb;
                                    border: 1px solid #e5e7eb;
                                    font-size: 14px;
                                    user-select: none;
                                " title="Drag to reorder">
                                    ⋮⋮ Drag
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 16px;">${comp.title}</h4>
                                    <p style="margin: 4px 0 0 0; font-size: 12px; color: #6b7280;">
                                        ${getComponentDescription(comp)}
                                    </p>
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button onclick="editComponent(${comp.id})" style="
                                    background: #f0f9ff; 
                                    color: #0369a1; 
                                    border: none; 
                                    padding: 4px 8px; 
                                    border-radius: 4px; 
                                    cursor: pointer;
                                    font-size: 12px;
                                ">Edit</button>
                                <button onclick="removeComponent(${comp.id})" style="
                                    background: #fef2f2; 
                                    color: #dc2626; 
                                    border: none; 
                                    padding: 4px 8px; 
                                    border-radius: 4px; 
                                    cursor: pointer;
                                    font-size: 12px;
                                ">Remove</button>
                            </div>
                        </div>
                        <div style="
                            background: #f8fafc; 
                            border: 1px solid #e2e8f0;
                            border-radius: 6px; 
                            padding: 16px;
                            text-align: center;
                            color: #64748b;
                        ">
                            ${getComponentPreview(comp)}
                        </div>
                    </div>
                `).join('');
            } else {
                placeholder.style.display = 'block';
                container.innerHTML = '';
            }
            
            // Render preview charts after DOM update
            setTimeout(() => {
                renderPreviewCharts();
                initializeSortable();
            }, 100);
        }

        function renderPreviewCharts() {
            components.forEach(comp => {
                if (['line_chart', 'bar_chart', 'pie_chart'].includes(comp.type)) {
                    const canvas = document.getElementById(`preview-chart-${comp.id}`);
                    if (canvas) {
                        renderPreviewChart(canvas, comp);
                    }
                }
            });
        }

        function renderPreviewChart(canvas, comp) {
            // Destroy existing chart if any
            if (canvas.chart) {
                canvas.chart.destroy();
            }

            const ctx = canvas.getContext('2d');
            
            // Fetch real data for preview
            fetchPreviewData(comp.config.metric || 'total_clicks', comp.config)
                .then(data => {
                    const chartData = formatDataForChart(data, comp.type);
                    
                    let chartConfig = {
                        type: comp.type === 'line_chart' ? 'line' : comp.type === 'bar_chart' ? 'bar' : 'pie',
                        data: chartData,
                        options: {
                            responsive: false,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: comp.type === 'pie_chart',
                                    position: 'right',
                                    labels: {
                                        boxWidth: 8,
                                        fontSize: 10
                                    }
                                },
                                tooltip: {
                                    enabled: false // Disable tooltips in preview
                                }
                            },
                            scales: comp.type !== 'pie_chart' ? {
                                x: {
                                    display: false
                                },
                                y: {
                                    display: false,
                                    beginAtZero: true
                                }
                            } : {},
                            interaction: {
                                intersect: false
                            }
                        }
                    };

                    canvas.chart = new Chart(ctx, chartConfig);
                })
                .catch(error => {
                    console.error('Error fetching preview data:', error);
                    // Fallback to sample data
                    const sampleData = getSampleData(comp.type, comp.config);
                    canvas.chart = new Chart(ctx, {
                        type: comp.type === 'line_chart' ? 'line' : comp.type === 'bar_chart' ? 'bar' : 'pie',
                        data: sampleData,
                        options: {
                            responsive: false,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: comp.type === 'pie_chart' } },
                            scales: comp.type !== 'pie_chart' ? { x: { display: false }, y: { display: false, beginAtZero: true } } : {}
                        }
                    });
                });
        }

        function fetchPreviewData(metric, config) {
            const url = `/reports/{{ $report->id }}/preview-data?metric=${encodeURIComponent(metric)}&period=${encodeURIComponent(config.period || 'daily')}&limit=${encodeURIComponent(config.limit || 10)}`;
            
            return fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                }
                return response.json();
            });
        }

        function formatDataForChart(data, chartType) {
            if (!data || !data.labels || !data.data) {
                return getSampleData(chartType, {});
            }

            switch (chartType) {
                case 'line_chart':
                    return {
                        labels: data.labels.slice(0, 7), // Limit for preview
                        datasets: [{
                            data: data.data.slice(0, 7),
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 2
                        }]
                    };
                case 'bar_chart':
                    return {
                        labels: data.labels.slice(0, 5), // Limit for preview
                        datasets: [{
                            data: data.data.slice(0, 5),
                            backgroundColor: [
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(16, 185, 129, 0.8)',
                                'rgba(245, 158, 11, 0.8)',
                                'rgba(239, 68, 68, 0.8)',
                                'rgba(139, 92, 246, 0.8)'
                            ],
                            borderWidth: 0
                        }]
                    };
                case 'pie_chart':
                    return {
                        labels: data.labels.slice(0, 4), // Limit for preview
                        datasets: [{
                            data: data.data.slice(0, 4),
                            backgroundColor: [
                                '#3b82f6',
                                '#10b981',
                                '#f59e0b',
                                '#ef4444'
                            ],
                            borderWidth: 1,
                            borderColor: '#fff'
                        }]
                    };
                default:
                    return getSampleData(chartType, {});
            }
        }

        function getSampleData(chartType, config) {
            switch (chartType) {
                case 'line_chart':
                    return {
                        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        datasets: [{
                            data: [12, 19, 3, 17, 28, 24, 7],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 2
                        }]
                    };
                case 'bar_chart':
                    return {
                        labels: ['Link A', 'Link B', 'Link C', 'Link D'],
                        datasets: [{
                            data: [45, 32, 28, 15],
                            backgroundColor: [
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(16, 185, 129, 0.8)',
                                'rgba(245, 158, 11, 0.8)',
                                'rgba(239, 68, 68, 0.8)'
                            ],
                            borderWidth: 0
                        }]
                    };
                case 'pie_chart':
                    return {
                        labels: ['Direct', 'Social', 'Email', 'Search'],
                        datasets: [{
                            data: [35, 25, 20, 20],
                            backgroundColor: [
                                '#3b82f6',
                                '#10b981',
                                '#f59e0b',
                                '#ef4444'
                            ],
                            borderWidth: 1,
                            borderColor: '#fff'
                        }]
                    };
                default:
                    return { labels: [], datasets: [] };
            }
        }

        function getComponentDescription(comp) {
            switch (comp.type) {
                case 'metric_card':
                    return `Metric: ${comp.config.metric || 'total_clicks'}${comp.config.show_comparison ? ' (with comparison)' : ''}`;
                case 'line_chart':
                case 'bar_chart':
                    return `${comp.config.metric || 'clicks_over_time'} - ${comp.config.period || 'daily'}`;
                case 'pie_chart':
                    return `${comp.config.metric || 'traffic_sources'} (top ${comp.config.limit || 5})`;
                case 'data_table':
                    return `${comp.config.metric || 'link_performance'} (${comp.config.limit || 20} rows)`;
                case 'text_block':
                    return `Text alignment: ${comp.config.alignment || 'left'}`;
                default:
                    return `${comp.type} component`;
            }
        }

        function getComponentPreview(comp) {
            switch (comp.type) {
                case 'metric_card':
                    const metricId = `metric-${comp.id}`;
                    // Fetch real data for metric cards too
                    setTimeout(() => {
                        fetchPreviewData(comp.config.metric || 'total_clicks', comp.config)
                            .then(data => {
                                const metricElement = document.getElementById(metricId);
                                if (metricElement && data.formatted_value) {
                                    metricElement.textContent = data.formatted_value;
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching metric data:', error);
                            });
                    }, 100);
                    
                    return `
                        <div style="text-align: center;">
                            <div id="${metricId}" style="font-size: 32px; font-weight: bold; color: #1f2937; margin-bottom: 8px;">...</div>
                            <div style="font-size: 14px; color: #6b7280;">${comp.config.metric || 'Total Clicks'}</div>
                            ${comp.config.show_comparison ? '<div style="font-size: 12px; color: #059669; margin-top: 4px;">↗ Loading comparison...</div>' : ''}
                        </div>
                    `;
                case 'line_chart':
                    return `
                        <div style="text-align: center;">
                            <canvas id="preview-chart-${comp.id}" width="200" height="100" style="max-width: 100%; margin-bottom: 8px;"></canvas>
                            <div style="font-size: 12px;">${comp.config.metric || 'clicks_over_time'} - ${comp.config.period || 'daily'}</div>
                        </div>
                    `;
                case 'bar_chart':
                    return `
                        <div style="text-align: center;">
                            <canvas id="preview-chart-${comp.id}" width="200" height="100" style="max-width: 100%; margin-bottom: 8px;"></canvas>
                            <div style="font-size: 12px;">${comp.config.metric || 'top_links'} - ${comp.config.period || 'daily'}</div>
                        </div>
                    `;
                case 'pie_chart':
                    return `
                        <div style="text-align: center;">
                            <canvas id="preview-chart-${comp.id}" width="150" height="100" style="max-width: 100%; margin-bottom: 8px;"></canvas>
                            <div style="font-size: 12px;">${comp.config.metric || 'traffic_sources'} (top ${comp.config.limit || 5})</div>
                        </div>
                    `;
                case 'data_table':
                    const tableId = `table-${comp.id}`;
                    // Fetch real data for tables
                    setTimeout(() => {
                        fetchPreviewData(comp.config.metric || 'link_performance', comp.config)
                            .then(data => {
                                const tableElement = document.getElementById(tableId);
                                if (tableElement && data.data && data.data.length > 0) {
                                    const rows = data.data.slice(0, 3).map(row => `
                                        <tr>
                                            <td style="padding: 4px; border: 1px solid #e5e7eb;">${row.short_code || row.label || 'N/A'}</td>
                                            <td style="padding: 4px; border: 1px solid #e5e7eb;">${row.total_clicks || row.clicks || 'N/A'}</td>
                                            <td style="padding: 4px; border: 1px solid #e5e7eb;">${row.is_active || 'Active'}</td>
                                        </tr>
                                    `).join('');
                                    tableElement.innerHTML = rows;
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching table data:', error);
                            });
                    }, 100);
                    
                    return `
                        <div style="padding: 8px;">
                            <table style="width: 100%; font-size: 10px; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f3f4f6;">
                                        <th style="padding: 4px; text-align: left; border: 1px solid #e5e7eb;">Link</th>
                                        <th style="padding: 4px; text-align: left; border: 1px solid #e5e7eb;">Clicks</th>
                                        <th style="padding: 4px; text-align: left; border: 1px solid #e5e7eb;">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="${tableId}">
                                    <tr>
                                        <td style="padding: 4px; border: 1px solid #e5e7eb;" colspan="3">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div style="text-align: center; font-size: 11px; margin-top: 8px; color: #6b7280;">
                                ${comp.config.metric || 'link_performance'} (${comp.config.limit || 20} rows)
                            </div>
                        </div>
                    `;
                case 'text_block':
                    return `
                        <div style="text-align: ${comp.config.alignment || 'left'};">
                            <div style="font-size: 24px; margin-bottom: 8px;">📝</div>
                            <div>${comp.config.content || 'Your text content will appear here...'}</div>
                        </div>
                    `;
                default:
                    return `<div>${comp.title} Preview</div>`;
            }
        }

        function editComponent(id) {
            // First try to find in standalone components
            let comp = components.find(c => c.id === id);
            
            // If not found, search in container components
            if (!comp) {
                for (const container of containers) {
                    comp = container.components.find(c => c.id === id);
                    if (comp) break;
                }
            }
            
            if (!comp) {
                console.error('Component not found:', id);
                return;
            }
            
            // Store the component being edited
            window.editingComponent = comp;
            
            // Show config modal with existing values
            showEditModal(comp);
        }

        function showEditModal(comp) {
            const title = getComponentTitle(comp.type);
            const config = getEditComponentConfig(comp);
            
            const modal = document.createElement('div');
            modal.innerHTML = `
                <div style="
                    position: fixed; 
                    top: 0; 
                    left: 0; 
                    right: 0; 
                    bottom: 0; 
                    background: rgba(0,0,0,0.5); 
                    display: flex; 
                    align-items: center; 
                    justify-content: center;
                    z-index: 1000;
                ">
                    <div style="
                        background: white; 
                        padding: 24px; 
                        border-radius: 8px; 
                        width: 400px; 
                        max-height: 80vh;
                        overflow-y: auto;
                    ">
                        <h3 style="margin: 0 0 16px 0;">Edit ${title}</h3>
                        <div id="config-form">${config}</div>
                        <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px;">
                            <button onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                            <button onclick="saveEditedComponent()" class="btn btn-primary">Save Changes</button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            window.currentModal = modal;
        }

        function getEditComponentConfig(comp) {
            switch (comp.type) {
                case 'metric_card':
                    return `
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Title:</label>
                            <input type="text" id="comp-title" value="${comp.title}" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Metric:</label>
                            <select id="comp-metric" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="total_clicks" ${comp.config.metric === 'total_clicks' ? 'selected' : ''}>Total Clicks</option>
                                <option value="unique_clicks" ${comp.config.metric === 'unique_clicks' ? 'selected' : ''}>Unique Visitors</option>
                                <option value="total_links" ${comp.config.metric === 'total_links' ? 'selected' : ''}>Total Links</option>
                                <option value="active_links" ${comp.config.metric === 'active_links' ? 'selected' : ''}>Active Links</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: flex; align-items: center;">
                                <input type="checkbox" id="comp-comparison" ${comp.config.show_comparison ? 'checked' : ''} style="margin-right: 8px;">
                                Show comparison with previous period
                            </label>
                        </div>
                    `;
                case 'line_chart':
                case 'bar_chart':
                    return `
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Title:</label>
                            <input type="text" id="comp-title" value="${comp.title}" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Metric:</label>
                            <select id="comp-metric" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="clicks_over_time" ${comp.config.metric === 'clicks_over_time' ? 'selected' : ''}>Clicks Over Time</option>
                                <option value="clicks_by_country" ${comp.config.metric === 'clicks_by_country' ? 'selected' : ''}>Clicks by Country</option>
                                <option value="browser_stats" ${comp.config.metric === 'browser_stats' ? 'selected' : ''}>Browser Statistics</option>
                                <option value="utm_campaigns" ${comp.config.metric === 'utm_campaigns' ? 'selected' : ''}>UTM Campaigns</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Time Period:</label>
                            <select id="comp-period" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="hourly" ${comp.config.period === 'hourly' ? 'selected' : ''}>Hourly</option>
                                <option value="daily" ${comp.config.period === 'daily' ? 'selected' : ''}>Daily</option>
                                <option value="weekly" ${comp.config.period === 'weekly' ? 'selected' : ''}>Weekly</option>
                                <option value="monthly" ${comp.config.period === 'monthly' ? 'selected' : ''}>Monthly</option>
                            </select>
                        </div>
                    `;
                case 'pie_chart':
                    return `
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Title:</label>
                            <input type="text" id="comp-title" value="${comp.title}" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Metric:</label>
                            <select id="comp-metric" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="traffic_sources" ${comp.config.metric === 'traffic_sources' ? 'selected' : ''}>Traffic Sources</option>
                                <option value="clicks_by_device" ${comp.config.metric === 'clicks_by_device' ? 'selected' : ''}>Clicks by Device</option>
                                <option value="clicks_by_country" ${comp.config.metric === 'clicks_by_country' ? 'selected' : ''}>Clicks by Country</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Items Limit:</label>
                            <input type="number" id="comp-limit" value="${comp.config.limit || 5}" min="3" max="20" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                    `;
                case 'data_table':
                    return `
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Title:</label>
                            <input type="text" id="comp-title" value="${comp.title}" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Metric:</label>
                            <select id="comp-metric" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="link_performance" ${comp.config.metric === 'link_performance' ? 'selected' : ''}>Link Performance</option>
                                <option value="top_links" ${comp.config.metric === 'top_links' ? 'selected' : ''}>Top Links</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Default Sort Column:</label>
                            <select id="comp-sort-column" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="total_clicks" ${comp.config.sort_column === 'total_clicks' ? 'selected' : ''}>Total Clicks</option>
                                <option value="unique_clicks" ${comp.config.sort_column === 'unique_clicks' ? 'selected' : ''}>Unique Clicks</option>
                                <option value="short_code" ${comp.config.sort_column === 'short_code' ? 'selected' : ''}>Link</option>
                                <option value="group_name" ${comp.config.sort_column === 'group_name' ? 'selected' : ''}>Group</option>
                                <option value="created_at" ${comp.config.sort_column === 'created_at' ? 'selected' : ''}>Created Date</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Sort Direction:</label>
                            <select id="comp-sort-direction" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="desc" ${comp.config.sort_direction === 'desc' ? 'selected' : ''}>Descending (High to Low)</option>
                                <option value="asc" ${comp.config.sort_direction === 'asc' ? 'selected' : ''}>Ascending (Low to High)</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Rows Limit:</label>
                            <input type="number" id="comp-limit" value="${comp.config.limit || 20}" min="5" max="100" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                    `;
                case 'text_block':
                    return `
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Title:</label>
                            <input type="text" id="comp-title" value="${comp.title}" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Content:</label>
                            <textarea id="comp-content" rows="4" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">${comp.config.content || ''}</textarea>
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Text Type:</label>
                            <select id="comp-text-type" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="p" ${(comp.config.text_type || 'p') === 'p' ? 'selected' : ''}>Paragraph (p)</option>
                                <option value="h1" ${comp.config.text_type === 'h1' ? 'selected' : ''}>Heading 1 (h1)</option>
                                <option value="h2" ${comp.config.text_type === 'h2' ? 'selected' : ''}>Heading 2 (h2)</option>
                                <option value="h3" ${comp.config.text_type === 'h3' ? 'selected' : ''}>Heading 3 (h3)</option>
                                <option value="h4" ${comp.config.text_type === 'h4' ? 'selected' : ''}>Heading 4 (h4)</option>
                                <option value="h5" ${comp.config.text_type === 'h5' ? 'selected' : ''}>Heading 5 (h5)</option>
                                <option value="h6" ${comp.config.text_type === 'h6' ? 'selected' : ''}>Heading 6 (h6)</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Text Alignment:</label>
                            <select id="comp-alignment" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="left" ${comp.config.alignment === 'left' ? 'selected' : ''}>Left</option>
                                <option value="center" ${comp.config.alignment === 'center' ? 'selected' : ''}>Center</option>
                                <option value="right" ${comp.config.alignment === 'right' ? 'selected' : ''}>Right</option>
                            </select>
                        </div>
                    `;
                default:
                    return `
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Title:</label>
                            <input type="text" id="comp-title" value="${comp.title}" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                    `;
            }
        }

        function saveEditedComponent() {
            if (!window.editingComponent) return;
            
            const comp = window.editingComponent;
            comp.title = document.getElementById('comp-title')?.value || comp.title;
            
            // Update config based on component type
            if (comp.type === 'metric_card') {
                comp.config.metric = document.getElementById('comp-metric')?.value || comp.config.metric;
                comp.config.show_comparison = document.getElementById('comp-comparison')?.checked || false;
            } else if (comp.type === 'text_block') {
                comp.config.content = document.getElementById('comp-content')?.value || comp.config.content;
                comp.config.text_type = document.getElementById('comp-text-type')?.value || comp.config.text_type;
                comp.config.alignment = document.getElementById('comp-alignment')?.value || comp.config.alignment;
            } else if (['line_chart', 'bar_chart', 'pie_chart'].includes(comp.type)) {
                comp.config.metric = document.getElementById('comp-metric')?.value || comp.config.metric;
                if (document.getElementById('comp-period')) {
                    comp.config.period = document.getElementById('comp-period')?.value || comp.config.period;
                }
                if (document.getElementById('comp-limit')) {
                    comp.config.limit = parseInt(document.getElementById('comp-limit')?.value) || comp.config.limit;
                }
            } else if (comp.type === 'data_table') {
                comp.config.metric = document.getElementById('comp-metric')?.value || comp.config.metric;
                comp.config.limit = parseInt(document.getElementById('comp-limit')?.value) || comp.config.limit;
                comp.config.sort_column = document.getElementById('comp-sort-column')?.value || comp.config.sort_column;
                comp.config.sort_direction = document.getElementById('comp-sort-direction')?.value || comp.config.sort_direction;
            }
            
            // Re-render the appropriate view
            if (containers.length > 0) {
                renderContainers();
            } else {
                renderComponents();
            }
            
            closeModal();
            window.editingComponent = null;
            console.log('Updated component:', comp);
        }

        function removeComponent(id) {
            // First try to remove from standalone components
            const originalLength = components.length;
            components = components.filter(c => c.id !== id);
            
            // If no component was removed from standalone, try containers
            if (components.length === originalLength) {
                for (let container of containers) {
                    const index = container.components.findIndex(c => c.id === id);
                    if (index !== -1) {
                        container.components.splice(index, 1);
                        renderContainers();
                        console.log('Removed component from container:', id);
                        return;
                    }
                }
            } else {
                renderComponents();
                console.log('Removed standalone component:', id);
            }
        }

        function addContainer(layoutType = 'default') {
            const layoutPresets = {
                'default': {
                    name: 'Row Container',
                    flex_direction: 'row',
                    justify_content: 'flex-start',
                    align_items: 'stretch',
                    gap: '16px',
                    min_height: 'auto',
                },
                'centered': {
                    name: 'Centered Row',
                    flex_direction: 'row',
                    justify_content: 'center',
                    align_items: 'center',
                    gap: '24px',
                    min_height: '200px',
                },
                'column': {
                    name: 'Vertical Stack',
                    flex_direction: 'column',
                    justify_content: 'flex-start',
                    align_items: 'stretch',
                    gap: '16px',
                    min_height: 'auto',
                },
                'spaced': {
                    name: 'Spaced Apart',
                    flex_direction: 'row',
                    justify_content: 'space-between',
                    align_items: 'stretch',
                    gap: '16px',
                    min_height: 'auto',
                }
            };

            const preset = layoutPresets[layoutType] || layoutPresets.default;
            
            const container = {
                id: Date.now(),
                name: preset.name,
                layout: preset,
                components: []
            };
            
            containers.push(container);
            renderContainers();
            showToast('Container added successfully!', 'success');
            console.log('Added container:', container);
        }

        function removeContainer(id) {
            const container = containers.find(c => c.id === id);
            if (container && container.components.length > 0) {
                if (!confirm('This container has components. Remove anyway?')) {
                    return;
                }
            }
            
            containers = containers.filter(c => c.id !== id);
            renderContainers();
            showToast('Container removed', 'info');
            console.log('Removed container:', id);
        }

        function renderContainers() {
            const container = document.getElementById('components-container');
            const placeholder = document.getElementById('placeholder');
            
            if (containers.length > 0) {
                placeholder.style.display = 'none';
                container.innerHTML = containers.map(cont => `
                    <div class="report-container ${cont.components.length > 0 ? 'has-components' : ''}" 
                         data-container-id="${cont.id}">
                        <div class="container-header">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div class="drag-handle" style="
                                    cursor: move; 
                                    color: #9ca3af; 
                                    padding: 4px 8px;
                                    border-radius: 4px;
                                    background: #f3f4f6;
                                    border: 1px solid #e5e7eb;
                                    font-size: 12px;
                                " title="Drag to reorder">⋮⋮</div>
                                <div>
                                    <h4 style="margin: 0; font-size: 14px; font-weight: 600;">${cont.name}</h4>
                                    <p style="margin: 2px 0 0 0; font-size: 11px; color: #6b7280;">
                                        ${cont.layout.flex_direction} • ${cont.layout.justify_content} • ${cont.components.length} component(s)
                                    </p>
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button onclick="editContainer(${cont.id})" style="
                                    background: #f0f9ff; 
                                    color: #0369a1; 
                                    border: none; 
                                    padding: 4px 8px; 
                                    border-radius: 4px; 
                                    cursor: pointer;
                                    font-size: 11px;
                                ">Settings</button>
                                <button onclick="removeContainer(${cont.id})" style="
                                    background: #fef2f2; 
                                    color: #dc2626; 
                                    border: none; 
                                    padding: 4px 8px; 
                                    border-radius: 4px; 
                                    cursor: pointer;
                                    font-size: 11px;
                                ">Remove</button>
                            </div>
                        </div>
                        <div class="container-content ${cont.components.length === 0 ? 'empty-container' : ''}" 
                             style="${getContainerStyles(cont.layout)}"
                             ondrop="dropInContainer(event, ${cont.id})"
                             ondragover="allowDrop(event)"
                             ondragleave="dragLeaveContainer(event)">
                            ${cont.components.length === 0 ? `
                                <div class="empty-container-message">
                                    <div style="margin-bottom: 8px; font-weight: 500;">Empty Container</div>
                                    <div>Drag components here from the library above</div>
                                    <div style="font-size: 12px; margin-top: 4px; color: #9ca3af;">or from other containers</div>
                                </div>
                            ` : cont.components.map(comp => renderComponentInContainer(comp, cont)).join('')}
                        </div>
                    </div>
                `).join('');
            } else {
                placeholder.style.display = 'block';
                container.innerHTML = '';
            }
            
            setTimeout(() => {
                initializeContainerSortable();
            }, 100);
        }

        function getContainerStyles(layout) {
            return `
                display: flex;
                flex-direction: ${layout.flex_direction};
                justify-content: ${layout.justify_content};
                align-items: ${layout.align_items};
                gap: ${layout.gap};
                min-height: ${layout.min_height};
                flex-wrap: wrap;
            `;
        }

        function renderComponentInContainer(comp, container) {
            return `
                <div class="component-in-container" data-component-id="${comp.id}" style="
                    ${comp.flex_basis ? `flex-basis: ${comp.flex_basis}; flex-grow: ${container.layout.justify_content === 'center' && container.components.length > 1 ? '0' : comp.flex_grow}; flex-shrink: ${comp.flex_shrink};` : 'flex: 1;'}
                ">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <div style="display: flex; align-items: center; gap: 6px; flex: 1;">
                            <div class="component-drag-handle" style="
                                cursor: move; 
                                color: #9ca3af; 
                                padding: 2px 4px;
                                border-radius: 3px;
                                background: #f3f4f6;
                                border: 1px solid #e5e7eb;
                                font-size: 10px;
                                user-select: none;
                            " title="Drag to reorder or move to another container">⋮⋮</div>
                            <div style="flex: 1;">
                                <h5 style="margin: 0; font-size: 13px; font-weight: 600;">${comp.title}</h5>
                                <p style="margin: 2px 0 0 0; font-size: 10px; color: #6b7280;">
                                    ${getComponentDescription(comp)}
                                </p>
                            </div>
                        </div>
                        <div style="display: flex; gap: 4px; margin-left: 8px;">
                            <button onclick="editComponent(${comp.id})" style="
                                background: #f0f9ff; 
                                color: #0369a1; 
                                border: none; 
                                padding: 2px 6px; 
                                border-radius: 3px; 
                                cursor: pointer;
                                font-size: 10px;
                            ">Edit</button>
                            <button onclick="removeComponentFromContainer(${comp.id})" style="
                                background: #fef2f2; 
                                color: #dc2626; 
                                border: none; 
                                padding: 2px 6px; 
                                border-radius: 3px; 
                                cursor: pointer;
                                font-size: 10px;
                            ">×</button>
                        </div>
                    </div>
                    <div style="
                        background: #f8fafc; 
                        border: 1px solid #e2e8f0;
                        border-radius: 4px; 
                        padding: 8px;
                        text-align: center;
                        color: #64748b;
                        font-size: 11px;
                    ">
                        ${getComponentPreview(comp)}
                    </div>
                </div>
            `;
        }

        function allowDrop(event) {
            event.preventDefault();
            event.currentTarget.classList.add('drag-target');
        }

        function dragLeaveContainer(event) {
            // Only remove the class if we're actually leaving the container
            // (not just moving to a child element)
            if (!event.currentTarget.contains(event.relatedTarget)) {
                event.currentTarget.classList.remove('drag-target');
            }
        }

        function dropInContainer(event, containerId) {
            event.preventDefault();
            event.currentTarget.classList.remove('drag-target');
            
            if (draggedType && draggedType !== 'container') {
                showConfigModalForContainer(draggedType, containerId);
                draggedType = null;
            }
        }

        function showConfigModalForContainer(type, containerId) {
            window.targetContainerId = containerId;
            showConfigModal(type);
        }

        function removeComponentFromContainer(componentId) {
            // Find and remove component from its container
            for (let container of containers) {
                const index = container.components.findIndex(c => c.id === componentId);
                if (index !== -1) {
                    container.components.splice(index, 1);
                    break;
                }
            }
            renderContainers();
            showToast('Component removed from container', 'info');
        }

        function editContainer(containerId) {
            const container = containers.find(c => c.id === containerId);
            if (!container) return;
            
            window.editingContainer = container;
            showContainerEditModal(container);
        }

        function showContainerEditModal(container) {
            const modal = document.createElement('div');
            modal.innerHTML = `
                <div style="
                    position: fixed; 
                    top: 0; 
                    left: 0; 
                    right: 0; 
                    bottom: 0; 
                    background: rgba(0,0,0,0.5); 
                    display: flex; 
                    align-items: center; 
                    justify-content: center;
                    z-index: 1000;
                ">
                    <div style="
                        background: white; 
                        padding: 24px; 
                        border-radius: 8px; 
                        width: 500px; 
                        max-height: 80vh;
                        overflow-y: auto;
                    ">
                        <h3 style="margin: 0 0 16px 0;">Edit Container: ${container.name}</h3>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Container Name:</label>
                            <input type="text" id="container-name" value="${container.name}" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Layout Direction:</label>
                            <select id="container-direction" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="row" ${container.layout.flex_direction === 'row' ? 'selected' : ''}>Horizontal (Row)</option>
                                <option value="column" ${container.layout.flex_direction === 'column' ? 'selected' : ''}>Vertical (Column)</option>
                            </select>
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Justify Content:</label>
                            <select id="container-justify" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="flex-start" ${container.layout.justify_content === 'flex-start' ? 'selected' : ''}>Start</option>
                                <option value="center" ${container.layout.justify_content === 'center' ? 'selected' : ''}>Center</option>
                                <option value="flex-end" ${container.layout.justify_content === 'flex-end' ? 'selected' : ''}>End</option>
                                <option value="space-between" ${container.layout.justify_content === 'space-between' ? 'selected' : ''}>Space Between</option>
                                <option value="space-around" ${container.layout.justify_content === 'space-around' ? 'selected' : ''}>Space Around</option>
                                <option value="space-evenly" ${container.layout.justify_content === 'space-evenly' ? 'selected' : ''}>Space Evenly</option>
                            </select>
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Align Items:</label>
                            <select id="container-align" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                                <option value="stretch" ${container.layout.align_items === 'stretch' ? 'selected' : ''}>Stretch</option>
                                <option value="flex-start" ${container.layout.align_items === 'flex-start' ? 'selected' : ''}>Start</option>
                                <option value="center" ${container.layout.align_items === 'center' ? 'selected' : ''}>Center</option>
                                <option value="flex-end" ${container.layout.align_items === 'flex-end' ? 'selected' : ''}>End</option>
                                <option value="baseline" ${container.layout.align_items === 'baseline' ? 'selected' : ''}>Baseline</option>
                            </select>
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Gap (spacing):</label>
                            <input type="text" id="container-gap" value="${container.layout.gap}" placeholder="16px" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Minimum Height:</label>
                            <input type="text" id="container-min-height" value="${container.layout.min_height}" placeholder="auto" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                        
                        <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px;">
                            <button onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                            <button onclick="saveContainerChanges()" class="btn btn-primary">Save Changes</button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            window.currentModal = modal;
        }

        function saveContainerChanges() {
            if (!window.editingContainer) return;
            
            const container = window.editingContainer;
            container.name = document.getElementById('container-name')?.value || container.name;
            container.layout.flex_direction = document.getElementById('container-direction')?.value || container.layout.flex_direction;
            container.layout.justify_content = document.getElementById('container-justify')?.value || container.layout.justify_content;
            container.layout.align_items = document.getElementById('container-align')?.value || container.layout.align_items;
            container.layout.gap = document.getElementById('container-gap')?.value || container.layout.gap;
            container.layout.min_height = document.getElementById('container-min-height')?.value || container.layout.min_height;
            
            renderContainers();
            closeModal();
            window.editingContainer = null;
            showToast('Container settings updated!', 'success');
            console.log('Updated container:', container);
        }

        function initializeContainerSortable() {
            const mainContainer = document.getElementById('components-container');
            
            if (typeof Sortable === 'undefined') {
                console.error('SortableJS not loaded');
                return;
            }

            // Initialize container reordering (only if we have multiple containers)
            if (mainContainer && containers.length >= 2) {
                // Destroy existing sortable instance if any
                if (mainContainer.sortableInstance) {
                    mainContainer.sortableInstance.destroy();
                }

                mainContainer.sortableInstance = Sortable.create(mainContainer, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    onEnd: function(evt) {
                        // Reorder the containers array
                        const oldIndex = evt.oldIndex;
                        const newIndex = evt.newIndex;
                        
                        if (oldIndex !== newIndex) {
                            // Move the container in the array
                            const movedContainer = containers.splice(oldIndex, 1)[0];
                            containers.splice(newIndex, 0, movedContainer);
                            
                            // Show feedback
                            showToast('Container reordered! Remember to save your changes.', 'success');
                            
                            console.log('Reordered containers:', containers.map(c => c.name));
                        }
                    }
                });

                console.log('Container sortable initialized for', containers.length, 'containers');
            }

            // Initialize component reordering within each container (including empty ones for cross-container dragging)
            containers.forEach((container, containerIndex) => {
                const containerElement = document.querySelector('[data-container-id="' + container.id + '"] .container-content');
                
                if (containerElement) {
                    // Destroy existing sortable instance if any
                    if (containerElement.componentSortableInstance) {
                        containerElement.componentSortableInstance.destroy();
                    }

                    containerElement.componentSortableInstance = Sortable.create(containerElement, {
                        animation: 150,
                        handle: '.component-drag-handle',
                        group: 'components', // Allow cross-container dragging
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        dragClass: 'sortable-drag',
                        onEnd: function(evt) {
                            const oldIndex = evt.oldIndex;
                            const newIndex = evt.newIndex;
                            const fromContainer = evt.from;
                            const toContainer = evt.to;
                            
                            // Find source and target containers
                            const fromContainerId = fromContainer.closest('[data-container-id]').getAttribute('data-container-id');
                            const toContainerId = toContainer.closest('[data-container-id]').getAttribute('data-container-id');
                            
                            const sourceContainer = containers.find(c => c.id === fromContainerId);
                            const targetContainer = containers.find(c => c.id === toContainerId);
                            
                            if (sourceContainer && targetContainer) {
                                // Handle cross-container move
                                if (fromContainerId !== toContainerId) {
                                    // Move component from one container to another
                                    const movedComponent = sourceContainer.components.splice(oldIndex, 1)[0];
                                    targetContainer.components.splice(newIndex, 0, movedComponent);
                                    
                                    showToast('Component moved between containers! Remember to save.', 'success');
                                    console.log('Moved component between containers:', movedComponent.title, 'from', sourceContainer.name, 'to', targetContainer.name);
                                    
                                    // Re-render containers to update component counts and handles
                                    renderContainers();
                                } else {
                                    // Same container reordering
                                    if (oldIndex !== newIndex) {
                                        const movedComponent = sourceContainer.components.splice(oldIndex, 1)[0];
                                        sourceContainer.components.splice(newIndex, 0, movedComponent);
                                        
                                        showToast('Component reordered! Remember to save.', 'success');
                                        console.log('Reordered components in container:', sourceContainer.name, sourceContainer.components.map(c => c.title));
                                    }
                                }
                            }
                        }
                    });

                    console.log('Component sortable initialized with cross-container dragging');
                }
            });
        }


        function previewReport() {
            // Save first, then preview
            if (containers.length === 0 && components.length === 0) {
                showToast('Please add at least one component or container before previewing.', 'warning');
                return;
            }

            showToast('Saving and opening preview...', 'info');
            
            // Save report first
            saveReportData().then(() => {
                // Open preview in new tab
                const previewUrl = `/reports/{{ $report->id }}/view`;
                window.open(previewUrl, '_blank');
            }).catch(error => {
                console.error('Error saving before preview:', error);
                showToast('Error saving report before preview: ' + error.message, 'error');
            });
        }

        function saveReportData() {
            return new Promise((resolve, reject) => {
                if (containers.length === 0 && components.length === 0) {
                    reject(new Error('No components to save'));
                    return;
                }

                let data;
                if (containers.length > 0) {
                    // Save container-based structure
                    data = {
                        containers: containers.map((container, index) => ({
                            name: container.name,
                            layout: container.layout,
                            order_index: index,
                            components: container.components.map(comp => ({
                                type: comp.type,
                                title: comp.title,
                                config: comp.config || { metric: 'total_clicks' },
                                flex_basis: comp.flex_basis || 'auto',
                                flex_grow: comp.flex_grow || 1,
                                flex_shrink: comp.flex_shrink || 1
                            }))
                        })),
                        _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    };
                } else {
                    // Legacy standalone components
                    data = {
                        components: components.map((comp, index) => ({
                            component_type: comp.type,
                            title: comp.title,
                            config: comp.config || { metric: 'total_clicks' },
                            position_x: 0,
                            position_y: index * 200,
                            width: 12,
                            height: 4,
                            order_index: index
                        })),
                        _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    };
                }

                fetch(`/reports/{{ $report->id }}/components`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': data._token
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        resolve(result);
                    } else {
                        reject(new Error(result.message || 'Save failed'));
                    }
                })
                .catch(error => {
                    reject(error);
                });
            });
        }

        function saveReport() {
            console.log('=== SAVE REPORT START ===');
            console.log('Containers to save:', containers);
            console.log('Standalone components to save:', components);
            console.log('Report ID:', {{ $report->id }});
            
            if (containers.length === 0 && components.length === 0) {
                showToast('Please add at least one component or container before saving.', 'warning');
                return;
            }

            // Show loading state
            const saveBtn = document.getElementById('save-btn');
            if (!saveBtn) {
                console.error('Save button not found!');
                return;
            }
            
            const originalText = saveBtn.textContent;
            saveBtn.textContent = 'Saving...';
            saveBtn.disabled = true;

            // Use the new saveReportData function
            saveReportData()
                .then(result => {
                    console.log('=== SAVE SUCCESS ===');
                    console.log('Save result:', result);
                    showToast('Report saved successfully! You can now view the report with live data.', 'success');
                    updateLastSavedTime();
                })
                .catch(error => {
                    console.error('=== SAVE ERROR ===');
                    console.error('Save error:', error);
                    showToast('Error saving report: ' + error.message, 'error');
                })
                .finally(() => {
                    // Restore button state
                    saveBtn.textContent = originalText;
                    saveBtn.disabled = false;
                });
        }

        function loadExistingComponents() {
            console.log('Loading existing components...');
            
            fetch(`/reports/{{ $report->id }}/data`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log('Loaded report data:', data);
                
                // Check if we have containers (new structure)
                if (data.containers && data.containers.length > 0) {
                    try {
                        containers = data.containers.map(cont => {
                            if (!cont) {
                                console.warn('Null container found, skipping');
                                return null;
                            }
                            
                            return {
                                id: cont.id || Date.now() + Math.random(),
                                name: cont.name || 'Container',
                                layout: {
                                    flex_direction: cont.flex_direction || 'row',
                                    justify_content: cont.justify_content || 'flex-start',
                                    align_items: cont.align_items || 'stretch',
                                    gap: cont.gap || '16px',
                                    min_height: cont.min_height || 'auto'
                                },
                                components: (cont.components || []).map(comp => {
                                    if (!comp) {
                                        console.warn('Null component found, skipping');
                                        return null;
                                    }
                                    
                                    return {
                                        id: comp.id || Date.now() + Math.random(),
                                        type: comp.type || comp.component_type || 'metric_card',
                                        title: comp.title || 'Untitled Component',
                                        config: comp.config || { metric: 'total_clicks' },
                                        flex_basis: comp.flex_basis || 'auto',
                                        flex_grow: comp.flex_grow || 1,
                                        flex_shrink: comp.flex_shrink || 1
                                    };
                                }).filter(comp => comp !== null)
                            };
                        }).filter(cont => cont !== null);
                        
                        renderContainers();
                        console.log('Loaded containers:', containers);
                        
                        const totalComponents = containers.reduce((sum, cont) => sum + cont.components.length, 0);
                        showToast('Loaded ' + containers.length + ' container(s) with ' + totalComponents + ' component(s)', 'info');
                    } catch (error) {
                        console.error('Error processing containers:', error);
                        showToast('Error loading containers: ' + error.message, 'error');
                        containers = [];
                    }
                } 
                // Fallback to legacy components (old structure)
                else if (data.components && data.components.length > 0) {
                    components = data.components.map(comp => ({
                        id: comp.id || Date.now() + Math.random(),
                        type: comp.type,
                        title: comp.title,
                        config: comp.config || {}
                    }));
                    
                    renderComponents();
                    console.log('Loaded legacy components:', components);
                    showToast('Loaded ' + components.length + ' existing component(s) (legacy format)', 'info');
                } else {
                    console.log('No existing components or containers found');
                }
            })
            .catch(error => {
                console.error('Error loading components:', error);
                showToast('Could not load existing components: ' + error.message, 'warning');
            });
        }

        function showToast(message, type = 'info') {
            // Create toast container if it doesn't exist
            let toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                `;
                document.body.appendChild(toastContainer);
            }

            // Create toast element
            const toast = document.createElement('div');
            const toastId = 'toast-' + Date.now();
            toast.id = toastId;
            
            const colors = {
                success: { bg: '#10b981', border: '#059669', icon: '✓' },
                error: { bg: '#ef4444', border: '#dc2626', icon: '✕' },
                warning: { bg: '#f59e0b', border: '#d97706', icon: '⚠' },
                info: { bg: '#3b82f6', border: '#2563eb', icon: 'ℹ' }
            };
            
            const color = colors[type] || colors.info;
            
            toast.style.cssText = `
                background: white;
                border-left: 4px solid ${color.border};
                border-radius: 8px;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                padding: 16px;
                max-width: 400px;
                display: flex;
                align-items: flex-start;
                gap: 12px;
                transform: translateX(100%);
                transition: transform 0.3s ease-in-out;
            `;
            
            toast.innerHTML = `
                <div style="
                    width: 20px;
                    height: 20px;
                    border-radius: 50%;
                    background: ${color.bg};
                    color: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 12px;
                    font-weight: bold;
                    flex-shrink: 0;
                ">
                    ${color.icon}
                </div>
                <div style="flex: 1; padding-top: 1px;">
                    <p style="margin: 0; color: #374151; font-size: 14px; line-height: 1.5;">
                        ${message}
                    </p>
                </div>
                <button onclick="removeToast('${toastId}')" style="
                    background: none;
                    border: none;
                    color: #9ca3af;
                    cursor: pointer;
                    font-size: 16px;
                    padding: 0;
                    width: 20px;
                    height: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">×</button>
            `;
            
            toastContainer.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
            }, 100);
            
            // Auto remove after 5 seconds (8 seconds for success messages)
            const duration = type === 'success' ? 8000 : 5000;
            setTimeout(() => {
                removeToast(toastId);
            }, duration);
        }

        function removeToast(toastId) {
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }
        }

        function updateLastSavedTime() {
            // Add a "last saved" indicator to the header
            let indicator = document.getElementById('last-saved-indicator');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'last-saved-indicator';
                indicator.style.cssText = `
                    font-size: 12px;
                    color: #6b7280;
                    margin-left: 8px;
                `;
                document.querySelector('h1').appendChild(indicator);
            }
            
            const now = new Date();
            const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            indicator.textContent = '(saved at ' + timeString + ')';
        }

        // Initialize sortable functionality for reordering components
        function initializeSortable() {
            const container = document.getElementById('components-container');
            
            if (!container || components.length < 2) {
                return;
            }

            if (typeof Sortable === 'undefined') {
                console.error('SortableJS not loaded');
                return;
            }

            // Destroy existing sortable instance if any
            if (container.sortableInstance) {
                container.sortableInstance.destroy();
            }

            container.sortableInstance = Sortable.create(container, {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onEnd: function(evt) {
                    // Reorder the components array
                    const oldIndex = evt.oldIndex;
                    const newIndex = evt.newIndex;
                    
                    if (oldIndex !== newIndex) {
                        // Move the component in the array
                        const movedComponent = components.splice(oldIndex, 1)[0];
                        components.splice(newIndex, 0, movedComponent);
                        
                        // Show feedback
                        showToast('Component reordered! Remember to save your changes.', 'success');
                        
                        console.log('Reordered components:', components.map(c => c.title));
                    }
                }
            });

            console.log('Sortable initialized for', components.length, 'components');
        }

        // Initialize keyboard shortcuts
        function initializeKeyboardShortcuts() {
            document.addEventListener('keydown', function(e) {
                // Ctrl + S to save (Windows/Linux) or Cmd + S (Mac)
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    saveReport();
                    showToast('Saved with keyboard shortcut! (Ctrl + S)', 'info');
                }
                
                // Escape to close modal
                if (e.key === 'Escape' && window.currentModal) {
                    e.preventDefault();
                    closeModal();
                }
                
                // Enter to submit modal (except in textareas)
                if (e.key === 'Enter' && window.currentModal && !e.target.matches('textarea')) {
                    const addButton = document.querySelector('[onclick*="addConfiguredComponent"]');
                    const saveButton = document.querySelector('[onclick*="saveEditedComponent"]');
                    const targetButton = addButton || saveButton;
                    
                    if (targetButton) {
                        e.preventDefault();
                        targetButton.click();
                    }
                }
            });
        }

        // Initialize everything when page loads
        // Helper function for safe string output in console/toast messages
        function safeString(str) {
            return str ? str.toString().replace(/'/g, "\\'").replace(/"/g, '\\"') : '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            initializeKeyboardShortcuts();
            initializeDragAndDrop();
            console.log('Report Builder initialized with keyboard shortcuts and drag/drop');
            
            // Load existing components from server
            loadExistingComponents();
            
            // Show helpful tips
            setTimeout(() => {
                showToast('💡 Tip: Click layout buttons to add containers, or drag components!', 'info');
            }, 2000);
        });
    </script>
</body>
</html>