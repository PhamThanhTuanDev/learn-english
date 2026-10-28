<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_the_settings_page(): void
    {
        $this->get('/settings')->assertRedirect('/login');
    }

    public function test_the_settings_page_shows_the_current_values(): void
    {
        $user = User::factory()->create()->refresh();

        $response = $this->actingAs($user)->get('/settings');

        $response->assertOk();
        $response->assertSee('Cài đặt học');
        $response->assertSee('value="120"', false);
        $response->assertSee('<option value="Asia/Ho_Chi_Minh" selected>', false);
    }

    public function test_settings_can_be_saved(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/settings', [
            'roadmap_start_date' => '2026-11-02',
            'daily_goal_minutes' => 90,
            'timezone' => 'Asia/Bangkok',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/settings');
        $response->assertSessionHas('status', 'settings-updated');

        $user->refresh();
        $this->assertSame('2026-11-02', $user->roadmap_start_date?->toDateString());
        $this->assertSame(90, $user->daily_goal_minutes);
        $this->assertSame('Asia/Bangkok', $user->timezone);
    }

    public function test_the_start_date_can_be_cleared(): void
    {
        $user = User::factory()->create(['roadmap_start_date' => '2026-11-02']);

        $this->actingAs($user)->patch('/settings', [
            'roadmap_start_date' => '',
            'daily_goal_minutes' => 120,
            'timezone' => 'Asia/Ho_Chi_Minh',
        ])->assertSessionHasNoErrors();

        $this->assertNull($user->refresh()->roadmap_start_date);
    }

    public function test_invalid_settings_are_rejected(): void
    {
        $user = User::factory()->create()->refresh();

        $response = $this->actingAs($user)->from('/settings')->patch('/settings', [
            'roadmap_start_date' => 'not-a-date',
            'daily_goal_minutes' => 5,
            'timezone' => 'Mars/Olympus',
        ]);

        $response->assertRedirect('/settings');
        $response->assertSessionHasErrors(['roadmap_start_date', 'daily_goal_minutes', 'timezone']);

        $user->refresh();
        $this->assertSame(120, $user->daily_goal_minutes);
        $this->assertSame('Asia/Ho_Chi_Minh', $user->timezone);
    }
}
