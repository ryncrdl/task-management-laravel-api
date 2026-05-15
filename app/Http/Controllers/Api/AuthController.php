<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Register a new user.
     * POST /api/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // auto-hashed by model cast
            'role' => 'member', // default role for public registration
            'is_active' => true,
        ]);

        $token = JWTAuth::fromUser($user);

        Log::info('New user registered', ['user_id' => $user->id, 'email' => $user->email]);

        return $this->success([
            'user' => $this->formatUser($user),
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ], 'Registration successful.', 201);
    }

    /**
     * Authenticate and return a JWT token.
     * POST /api/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        try {
            if (! $token = auth('api')->attempt($credentials)) {
                return $this->error('Invalid credentials.', 401);
            }
        } catch (JWTException $e) {
            Log::error('JWT token creation failed', ['error' => $e->getMessage()]);
            return $this->error('Could not create token. Please try again.', 500);
        }

        $user = auth('api')->user();

        if (! $user->is_active) {
            auth('api')->logout();
            return $this->error('Your account has been deactivated. Contact an administrator.', 403);
        }

        Log::info('User logged in', ['user_id' => $user->id]);

        return $this->success([
            'user' => $this->formatUser($user),
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ], 'Login successful.');
    }

    /**
     * Invalidate the current JWT token.
     * POST /api/auth/logout
     */
    public function logout(): JsonResponse
    {
        $userId = auth('api')->id();
        auth('api')->logout();

        Log::info('User logged out', ['user_id' => $userId]);

        return $this->success(null, 'Logged out successfully.');
    }

    /**
     * Refresh the JWT token.
     * POST /api/auth/refresh
     */
    public function refresh(): JsonResponse
    {
        try {
            $newToken = auth('api')->refresh();
        } catch (JWTException $e) {
            return $this->error('Token cannot be refreshed. Please login again.', 401);
        }

        return $this->success([
            'token' => $newToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ], 'Token refreshed.');
    }

    /**
     * Return the authenticated user's profile.
     * GET /api/auth/me
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user()->load('teams');

        return $this->success($this->formatUser($user, withTeams: true));
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function formatUser(\App\Models\User $user, bool $withTeams = false): array
    {
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'created_at' => $user->created_at,
        ];

        if ($withTeams) {
            $data['teams'] = $user->teams->map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
                'member_role' => $team->pivot->role,
            ]);
        }

        return $data;
    }
}
