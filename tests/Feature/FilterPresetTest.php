<?php

namespace Tests\Feature;

use App\Models\TaskFilterPreset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterPresetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'manager', 'is_active' => true]);
    }

    public function test_user_can_save_filter_preset(): void
    {
        $token = auth('api')->login($this->user);

        $response = $this->withToken($token)->postJson('/api/task-filter-presets', [
            'name'    => 'High Priority Pending',
            'filters' => ['status' => 'pending', 'priority' => 'high'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'High Priority Pending');

        $this->assertDatabaseHas('task_filter_presets', [
            'user_id' => $this->user->id,
            'name'    => 'High Priority Pending',
        ]);
    }

    public function test_unknown_filter_keys_are_stripped(): void
    {
        $token = auth('api')->login($this->user);

        $response = $this->withToken($token)->postJson('/api/task-filter-presets', [
            'name'    => 'Safe Preset',
            'filters' => ['status' => 'pending', '__dangerous' => 'injection'],
        ]);

        $response->assertStatus(201);
        $preset = TaskFilterPreset::where('user_id', $this->user->id)->first();
        $this->assertArrayNotHasKey('__dangerous', $preset->filters);
    }

    public function test_user_can_list_own_presets(): void
    {
        TaskFilterPreset::factory()->count(3)->create(['user_id' => $this->user->id]);

        $other = User::factory()->create(['is_active' => true]);
        TaskFilterPreset::factory()->count(2)->create(['user_id' => $other->id]);

        $token = auth('api')->login($this->user);

        $response = $this->withToken($token)->getJson('/api/task-filter-presets');
        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_user_can_delete_own_preset(): void
    {
        $preset = TaskFilterPreset::factory()->create(['user_id' => $this->user->id]);

        $token = auth('api')->login($this->user);

        $this->withToken($token)
            ->deleteJson("/api/task-filter-presets/{$preset->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('task_filter_presets', ['id' => $preset->id]);
    }

    public function test_user_cannot_delete_others_preset(): void
    {
        $other  = User::factory()->create(['is_active' => true]);
        $preset = TaskFilterPreset::factory()->create(['user_id' => $other->id]);

        $token = auth('api')->login($this->user);

        $this->withToken($token)
            ->deleteJson("/api/task-filter-presets/{$preset->id}")
            ->assertStatus(403);
    }

    public function test_preset_name_is_required(): void
    {
        $token = auth('api')->login($this->user);

        $this->withToken($token)
            ->postJson('/api/task-filter-presets', ['filters' => ['status' => 'pending']])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name']]);
    }
}
