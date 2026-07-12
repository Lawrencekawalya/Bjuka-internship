<?php

namespace Tests\Feature;

use App\Enums\BatchStatus;
use App\Enums\UserRole;
use App\Models\InternshipBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_redirects_to_current_active_batch()
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $olderBatch = InternshipBatch::factory()->create([
            'status' => BatchStatus::ACTIVE,
            'start_date' => '2026-06-01',
        ]);
        $currentBatch = InternshipBatch::factory()->create([
            'status' => BatchStatus::ACTIVE,
            'start_date' => '2026-07-01',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('batches.show', $currentBatch));

        $this->assertNotSame($olderBatch->id, $currentBatch->id);
    }

    public function test_admin_dashboard_redirects_to_batches_when_no_active_batch()
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);

        InternshipBatch::factory()->create([
            'status' => BatchStatus::CLOSED,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('batches.index'));
    }

    public function test_non_batch_dashboard_users_keep_the_default_dashboard()
    {
        $user = User::factory()->create(['role' => UserRole::INTERN]);

        InternshipBatch::factory()->create([
            'status' => BatchStatus::ACTIVE,
            'start_date' => '2026-07-01',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
