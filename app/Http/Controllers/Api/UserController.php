<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * List users with optional filters (paginated).
     * GET /api/users
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        // Filter by role
        if ($request->filled('role') && in_array($request->role, User::ROLES, true)) {
            $query->where('role', $request->role);
        }

        // Filter by active status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Filter by search term
        if ($request->filled('search')) {
            $search = '%' . addcslashes($request->search, '%_') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        // Managers can only see members of their teams
        $authUser = auth('api')->user();
        if ($authUser->isManager()) {
            $teamIds = $authUser->teams->pluck('id');
            $query->whereHas('teams', fn ($q) => $q->whereIn('team_id', $teamIds));
        }

        $users = $query->orderBy('name')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Create a new user.
     * POST /api/users
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $authUser = auth('api')->user();

        // Managers can only create Team Members
        if ($authUser->isManager() && $request->role !== User::ROLE_MEMBER) {
            return $this->error('Managers can only create Team Members.', 403);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => $request->role ?? User::ROLE_MEMBER,
            'is_active' => true,
        ]);

        Log::info('User created', [
            'created_by' => $authUser->id,
            'new_user_id' => $user->id,
            'role' => $user->role,
        ]);

        return $this->success($user, 'User created successfully.', 201);
    }

    /**
     * Get a single user's details.
     * GET /api/users/{id}
     */
    public function show(User $user): JsonResponse
    {
        $user->load('teams');

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'created_at' => $user->created_at,
            'teams' => $user->teams->map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
                'member_role' => $team->pivot->role,
            ]),
        ]);
    }

    /**
     * Update user name, email, or role.
     * PATCH /api/users/{id}
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $authUser = auth('api')->user();

        // Managers cannot change roles or modify admins/managers
        if ($authUser->isManager()) {
            if ($request->filled('role')) {
                return $this->error('Managers cannot change user roles.', 403);
            }
            if (! $user->isMember()) {
                return $this->error('Managers can only edit Team Members.', 403);
            }
        }

        $user->update($request->only(['name', 'email', 'role']));

        Log::info('User updated', ['updated_by' => $authUser->id, 'user_id' => $user->id]);

        return $this->success($user, 'User updated successfully.');
    }

    /**
     * Toggle a user's active/inactive status.
     * PATCH /api/users/{id}/status
     */
    public function toggleStatus(User $user): JsonResponse
    {
        $authUser = auth('api')->user();

        // Prevent self-deactivation
        if ($user->id === $authUser->id) {
            return $this->error('You cannot deactivate your own account.', 422);
        }

        // Managers cannot deactivate admins/managers
        if ($authUser->isManager() && ! $user->isMember()) {
            return $this->error('Managers can only manage Team Members.', 403);
        }

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        Log::info("User {$status}", [
            'changed_by' => $authUser->id,
            'user_id' => $user->id,
        ]);

        return $this->success(
            ['id' => $user->id, 'is_active' => $user->is_active],
            "User {$status} successfully."
        );
    }
}
