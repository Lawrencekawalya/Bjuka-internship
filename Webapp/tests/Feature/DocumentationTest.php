<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_documentation(): void
    {
        $user = User::factory()->create(['role' => UserRole::SUPERVISOR]);

        $this->actingAs($user)
            ->get(route('documentation'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('documentation/Index'));
    }

    public function test_guest_is_redirected_from_documentation(): void
    {
        $this->get(route('documentation'))
            ->assertRedirect(route('login'));
    }
}
