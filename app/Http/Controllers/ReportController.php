<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\ReportMetricsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ReportMetricsService $metricsService
    ) {}

    public function view(Report $report)
    {
        $this->authorize('view', $report);

        $reportData = $this->generateReportData($report);

        return view('reports.view', compact('report', 'reportData'));
    }

    public function builder(Report $report)
    {
        $this->authorize('update', $report);

        return view('reports.test-builder', compact('report'));
    }

    public function data(Report $report)
    {
        $this->authorize('view', $report);

        return response()->json($this->generateReportData($report));
    }

    public function updateComponents(Request $request, Report $report)
    {
        $this->authorize('update', $report);

        $validated = $request->validate([
            // Legacy components support
            'components' => 'sometimes|array',
            'components.*.component_type' => 'required_with:components|string',
            'components.*.title' => 'nullable|string',
            'components.*.config' => 'required_with:components|array',
            'components.*.position_x' => 'required_with:components|integer',
            'components.*.position_y' => 'required_with:components|integer',
            'components.*.width' => 'required_with:components|integer',
            'components.*.height' => 'required_with:components|integer',
            'components.*.order_index' => 'required_with:components|integer',

            // New container-based structure
            'containers' => 'sometimes|array',
            'containers.*.name' => 'required_with:containers|string',
            'containers.*.layout' => 'required_with:containers|array',
            'containers.*.components' => 'required_with:containers|array',
            'containers.*.order_index' => 'required_with:containers|integer',
        ]);

        // Delete existing containers and components
        $report->containers()->delete();
        $report->components()->delete();

        if (isset($validated['containers'])) {
            // Save container-based structure
            foreach ($validated['containers'] as $index => $containerData) {
                $container = $report->containers()->create([
                    'name' => $containerData['name'],
                    'order_index' => $containerData['order_index'] ?? $index,
                    'flex_direction' => $containerData['layout']['flex_direction'] ?? 'row',
                    'justify_content' => $containerData['layout']['justify_content'] ?? 'flex-start',
                    'align_items' => $containerData['layout']['align_items'] ?? 'stretch',
                    'gap' => $containerData['layout']['gap'] ?? '16px',
                    'min_height' => $containerData['layout']['min_height'] ?? 'auto',
                ]);

                // Create components within this container
                foreach ($containerData['components'] as $compIndex => $componentData) {
                    $container->components()->create([
                        'report_id' => $report->id,
                        'component_type' => $componentData['type'],
                        'title' => $componentData['title'],
                        'config' => $componentData['config'] ?? [],
                        'position_x' => 0,
                        'position_y' => 0,
                        'width' => 6,
                        'height' => 4,
                        'order_index' => $compIndex,
                        'flex_basis' => $componentData['flex_basis'] ?? 'auto',
                        'flex_grow' => $componentData['flex_grow'] ?? 1,
                        'flex_shrink' => $componentData['flex_shrink'] ?? 1,
                    ]);
                }
            }
        } elseif (isset($validated['components'])) {
            // Legacy standalone components support
            foreach ($validated['components'] as $componentData) {
                $report->components()->create($componentData);
            }
        }

        $report->update(['last_generated_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function previewData(Request $request, Report $report)
    {
        $this->authorize('view', $report);

        $metric = $request->get('metric', 'total_clicks');
        $period = $request->get('period', 'daily');
        $limit = (int) $request->get('limit', 10);

        $config = [
            'period' => $period,
            'limit' => $limit,
            'show_comparison' => false, // Disable comparison for previews
        ];

        $data = $this->metricsService->getMetricData($metric, $config, $report->global_filters ?? []);

        return response()->json($data);
    }

    public function reorderComponents(Request $request, Report $report)
    {
        $this->authorize('update', $report);

        $validated = $request->validate([
            'component_ids' => 'required|array',
            'component_ids.*' => 'required|integer|exists:report_components,id',
        ]);

        // Verify all components belong to this report
        $componentIds = $validated['component_ids'];
        $componentsCount = $report->components()->whereIn('id', $componentIds)->count();

        if ($componentsCount !== count($componentIds)) {
            return response()->json(['success' => false, 'message' => 'Invalid component IDs'], 400);
        }

        // Update order_index for each component
        foreach ($componentIds as $index => $componentId) {
            $report->components()->where('id', $componentId)->update([
                'order_index' => $index,
            ]);
        }

        $report->update(['last_generated_at' => now()]);

        return response()->json(['success' => true]);
    }

    private function generateReportData(Report $report): array
    {
        $components = [];
        $containers = [];

        // Load containers and their components
        foreach ($report->containers()->orderBy('order_index')->get() as $container) {
            $containerComponents = [];

            foreach ($container->components()->orderBy('order_index')->get() as $component) {
                $data = $this->metricsService->getMetricData(
                    $component->config['metric'] ?? 'total_clicks',
                    $component->config,
                    $report->global_filters ?? []
                );

                $containerComponents[] = [
                    'id' => $component->id,
                    'type' => $component->component_type,
                    'title' => $component->title ?: $component->getComponentTypeDisplayName(),
                    'data' => $data,
                    'config' => $component->config,
                    'flex_basis' => $component->flex_basis,
                    'flex_grow' => $component->flex_grow,
                    'flex_shrink' => $component->flex_shrink,
                ];
            }

            $containers[] = [
                'id' => $container->id,
                'name' => $container->name,
                'flex_direction' => $container->flex_direction,
                'justify_content' => $container->justify_content,
                'align_items' => $container->align_items,
                'gap' => $container->gap,
                'min_height' => $container->min_height,
                'components' => $containerComponents,
            ];
        }

        // Load standalone components (legacy support)
        foreach ($report->components()->whereNull('container_id')->orderBy('order_index')->get() as $component) {
            $data = $this->metricsService->getMetricData(
                $component->config['metric'] ?? 'total_clicks',
                $component->config,
                $report->global_filters ?? []
            );

            $components[] = [
                'id' => $component->id,
                'type' => $component->component_type,
                'title' => $component->title ?: $component->getComponentTypeDisplayName(),
                'data' => $data,
                'config' => $component->config,
                'position' => [
                    'x' => $component->position_x,
                    'y' => $component->position_y,
                    'width' => $component->width,
                    'height' => $component->height,
                ],
            ];
        }

        $totalComponents = count($components) + collect($containers)->sum(fn ($c) => count($c['components']));

        return [
            'components' => $components,
            'containers' => $containers,
            'dateRange' => $report->date_range,
            'totalComponents' => $totalComponents,
        ];
    }
}
