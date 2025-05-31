<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Services\LinkShortenerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LinkController extends Controller
{
    protected LinkShortenerService $linkService;

    public function __construct(LinkShortenerService $linkService)
    {
        $this->linkService = $linkService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = Link::where('created_by', $user->id)
            ->with(['group:id,name,color', 'clicks' => function($query) {
                $query->select('link_id')
                    ->selectRaw('COUNT(*) as total_clicks')
                    ->groupBy('link_id');
            }]);

        // Apply filters
        if ($request->has('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $links = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $links->items(),
            'meta' => [
                'current_page' => $links->currentPage(),
                'last_page' => $links->lastPage(),
                'per_page' => $links->perPage(),
                'total' => $links->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'original_url' => 'required|url|max:2048',
            'custom_slug' => 'nullable|string|max:255|regex:/^[a-z0-9\-_]*$/|unique:links,short_code',
            'group_id' => 'nullable|exists:link_groups,id',
            'redirect_type' => 'nullable|in:301,302,307,308',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $shortCode = $this->linkService->generateUniqueCode($request->custom_slug);
            
            $link = Link::create([
                'short_code' => $shortCode,
                'original_url' => $request->original_url,
                'group_id' => $request->group_id,
                'redirect_type' => $request->redirect_type ?? 302,
                'is_active' => true,
                'expires_at' => $request->expires_at,
                'created_by' => auth()->id(),
                'custom_slug' => $request->custom_slug,
                'click_count' => 0,
            ]);

            $link->load('group:id,name,color');

            return response()->json([
                'data' => $link,
                'short_url' => url($link->short_code)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create link',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $link = Link::where('id', $id)
            ->where('created_by', auth()->id())
            ->with(['group:id,name,color'])
            ->first();

        if (!$link) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Link not found'
            ], 404);
        }

        return response()->json([
            'data' => $link,
            'short_url' => url($link->short_code)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $link = Link::where('id', $id)
            ->where('created_by', auth()->id())
            ->first();

        if (!$link) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Link not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'original_url' => 'sometimes|required|url|max:2048',
            'group_id' => 'nullable|exists:link_groups,id',
            'redirect_type' => 'sometimes|in:301,302,307,308',
            'is_active' => 'sometimes|boolean',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $link->update($request->only([
            'original_url', 'group_id', 'redirect_type', 'is_active', 'expires_at'
        ]));

        $link->load('group:id,name,color');

        return response()->json([
            'data' => $link,
            'short_url' => url($link->short_code)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $link = Link::where('id', $id)
            ->where('created_by', auth()->id())
            ->first();

        if (!$link) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Link not found'
            ], 404);
        }

        $link->delete();

        return response()->json([
            'message' => 'Link deleted successfully'
        ]);
    }

    /**
     * Get link statistics
     */
    public function stats(string $id)
    {
        $link = Link::where('id', $id)
            ->where('created_by', auth()->id())
            ->with(['clicks' => function($query) {
                $query->select('link_id', 'country', 'city', 'clicked_at')
                    ->orderBy('clicked_at', 'desc');
            }])
            ->first();

        if (!$link) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Link not found'
            ], 404);
        }

        // Get click statistics
        $totalClicks = $link->clicks->count();
        $todayClicks = $link->clicks->where('clicked_at', '>=', today())->count();
        $thisWeekClicks = $link->clicks->where('clicked_at', '>=', now()->startOfWeek())->count();
        
        // Get top countries
        $topCountries = $link->clicks
            ->whereNotNull('country')
            ->groupBy('country')
            ->map(function ($clicks) {
                return [
                    'country' => $clicks->first()->country,
                    'clicks' => $clicks->count()
                ];
            })
            ->sortByDesc('clicks')
            ->take(5)
            ->values();

        return response()->json([
            'data' => [
                'link' => $link->only(['id', 'short_code', 'original_url', 'click_count']),
                'short_url' => url($link->short_code),
                'stats' => [
                    'total_clicks' => $totalClicks,
                    'today_clicks' => $todayClicks,
                    'this_week_clicks' => $thisWeekClicks,
                    'top_countries' => $topCountries,
                ]
            ]
        ]);
    }
}
