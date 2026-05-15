<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskFilterPreset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskFilterPresetController extends Controller
{
    private const ALLOWED_KEYS = ['status', 'priority', 'assigned_to', 'date_from', 'date_to', 'team_id'];

    /**
     * List the authenticated user's saved filter presets.
     * GET /api/task-filter-presets
     */
    public function index(): JsonResponse
    {
        $presets = auth('api')->user()
            ->filterPresets()
            ->orderBy('name')
            ->get();

        return $this->success($presets);
    }

    /**
     * Save a new filter preset.
     * POST /api/task-filter-presets
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:100',
            'filters' => 'required|array',
        ]);

        // Only allow safe, known keys
        $filters = array_intersect_key($validated['filters'], array_flip(self::ALLOWED_KEYS));

        $preset = auth('api')->user()->filterPresets()->create([
            'name'    => $validated['name'],
            'filters' => $filters,
        ]);

        return $this->success($preset, 'Filter preset saved.', 201);
    }

    /**
     * Delete a preset owned by the authenticated user.
     * DELETE /api/task-filter-presets/{preset}
     */
    public function destroy(TaskFilterPreset $preset): JsonResponse
    {
        if ((int) $preset->user_id !== (int) auth('api')->id()) {
            return $this->error('Not your preset.', 403);
        }

        $preset->delete();

        return $this->success(null, 'Filter preset deleted.');
    }
}
