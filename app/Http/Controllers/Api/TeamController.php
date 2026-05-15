<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\AddMemberRequest;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    /**
     * List all teams for internal/cron use (no auth filtering).
     * GET /api/internal/teams
     */
    public function indexInternal(): JsonResponse
    {
        $teams = Team::with(['creator:id,name'])
            ->withCount('members')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $teams]);
    }

    /**
     * List teams (paginated).
     * GET /api/teams
     */
    public function index(Request $request): JsonResponse
    {
        $authUser = auth('api')->user();

        $query = Team::with(['creator:id,name', 'members:id,name,email,role']);

        // Managers only see their own teams
        if ($authUser->isManager()) {
            $query->whereHas('members', fn ($q) => $q->where('user_id', $authUser->id));
        }

        // Members see only teams they belong to
        if ($authUser->isMember()) {
            $query->whereHas('members', fn ($q) => $q->where('user_id', $authUser->id));
        }

        $teams = $query->withCount('members')
            ->orderBy('name')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $teams->items(),
            'meta' => [
                'current_page' => $teams->currentPage(),
                'last_page' => $teams->lastPage(),
                'per_page' => $teams->perPage(),
                'total' => $teams->total(),
            ],
        ]);
    }

    /**
     * Create a new team.
     * POST /api/teams
     */
    public function store(StoreTeamRequest $request): JsonResponse
    {
        $authUser = auth('api')->user();

        $team = DB::transaction(function () use ($request, $authUser) {
            $team = Team::create([
                'name' => $request->name,
                'created_by' => $authUser->id,
            ]);

            // Auto-add creator as team lead
            TeamMember::create([
                'team_id' => $team->id,
                'user_id' => $authUser->id,
                'role' => TeamMember::ROLE_LEAD,
            ]);

            return $team;
        });

        $team->load(['creator:id,name', 'members:id,name,email']);

        Log::info('Team created', ['team_id' => $team->id, 'created_by' => $authUser->id]);

        return $this->success($team, 'Team created successfully.', 201);
    }

    /**
     * Get a team's details including members.
     * GET /api/teams/{team}
     */
    public function show(Team $team): JsonResponse
    {
        $authUser = auth('api')->user();

        // Non-admins must be a member of the team to view it
        if (! $authUser->isAdmin() && ! $team->hasMember($authUser)) {
            return $this->error('Access denied.', 403);
        }

        $team->load([
            'creator:id,name,email',
            'members' => fn ($q) => $q->select('users.id', 'users.name', 'users.email', 'users.role')
                ->withPivot('role'),
        ]);

        $team->loadCount(['members', 'tasks']);

        return $this->success($team);
    }

    /**
     * Add a member to a team.
     * POST /api/teams/{team}/members
     */
    public function addMember(AddMemberRequest $request, Team $team): JsonResponse
    {
        $authUser = auth('api')->user();

        // Only admin or team lead can add members
        if (! $team->isLead($authUser)) {
            return $this->error('Only team leads and admins can add members.', 403);
        }

        $user = User::findOrFail($request->user_id);

        if (! $user->is_active) {
            return $this->error('Cannot add an inactive user to a team.', 422);
        }

        if ($team->hasMember($user)) {
            return $this->error('User is already a member of this team.', 422);
        }

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => $request->get('role', TeamMember::ROLE_MEMBER),
        ]);

        Log::info('Team member added', [
            'team_id' => $team->id,
            'user_id' => $user->id,
            'added_by' => $authUser->id,
        ]);

        return $this->success(null, "User '{$user->name}' added to team '{$team->name}'.", 201);
    }

    /**
     * Remove a member from a team.
     * DELETE /api/teams/{team}/members/{user}
     */
    public function removeMember(Team $team, User $user): JsonResponse
    {
        $authUser = auth('api')->user();

        // Only admin or team lead can remove members
        if (! $team->isLead($authUser)) {
            return $this->error('Only team leads and admins can remove members.', 403);
        }

        // Cannot remove self if you're the only lead
        if ($user->id === $authUser->id) {
            $leadCount = TeamMember::where('team_id', $team->id)
                ->where('role', TeamMember::ROLE_LEAD)
                ->count();

            if ($leadCount <= 1) {
                return $this->error('You cannot remove yourself as the only team lead.', 422);
            }
        }

        $deleted = TeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->delete();

        if (! $deleted) {
            return $this->error('User is not a member of this team.', 404);
        }

        Log::info('Team member removed', [
            'team_id' => $team->id,
            'user_id' => $user->id,
            'removed_by' => $authUser->id,
        ]);

        return $this->success(null, "User '{$user->name}' removed from team.");
    }
}
