<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LinkGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LinkGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = LinkGroup::query();

        // Include link count if requested
        if ($request->boolean('include_counts', false)) {
            $query->withCount('links');
        }

        // Simple listing without pagination for dropdown use
        if ($request->boolean('simple', false)) {
            $groups = $query->orderBy('name')->get(['id', 'name', 'is_default']);

            return response()->json([
                'data' => $groups,
            ]);
        }

        // Full paginated listing
        $perPage = min($request->get('per_page', 15), 100);
        $groups = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'data' => $groups->items(),
            'meta' => [
                'current_page' => $groups->currentPage(),
                'last_page' => $groups->lastPage(),
                'per_page' => $groups->perPage(),
                'total' => $groups->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:link_groups,name',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Remove is_default from creation data
        $isDefault = isset($validated['is_default']) && $validated['is_default'];
        unset($validated['is_default']);

        $group = LinkGroup::create($validated);

        // Set as default if requested
        if ($isDefault) {
            $group->setAsDefault();
        }

        return response()->json([
            'message' => 'Link group created successfully',
            'data' => $group,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $group = LinkGroup::withCount('links')->findOrFail($id);

        return response()->json([
            'data' => $group,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $group = LinkGroup::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255|unique:link_groups,name,'.$id,
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Handle is_default separately
        $setAsDefault = false;
        if (isset($validated['is_default'])) {
            $setAsDefault = $validated['is_default'];
            unset($validated['is_default']);
        }

        $group->update($validated);

        // Handle default status change
        if ($setAsDefault) {
            $group->setAsDefault();
        }

        return response()->json([
            'message' => 'Link group updated successfully',
            'data' => $group,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $group = LinkGroup::findOrFail($id);

        // Check if group has links
        if ($group->links()->exists()) {
            return response()->json([
                'message' => 'Cannot delete group with existing links',
            ], 409);
        }

        $group->delete();

        return response()->json([
            'message' => 'Link group deleted successfully',
        ]);
    }
}
