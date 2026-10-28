<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function pages(): array
    {
        return [
            'today' => ['/dashboard'],
            'roadmap' => ['/roadmap'],
            'flashcards' => ['/flashcards'],
            'mistakes' => ['/mistakes'],
            'scores' => ['/scores'],
        ];
    }

    #[DataProvider('pages')]
    public function test_guests_are_sent_to_the_login_page(string $url): void
    {
        $this->get($url)->assertRedirect('/login');
    }

    #[DataProvider('pages')]
    public function test_each_page_shows_the_vietnamese_menu(string $url): void
    {
        $response = $this->actingAs(User::factory()->create())->get($url);

        $response->assertOk();
        $response->assertSeeInOrder(['Hôm nay', 'Lộ trình', 'Flashcard', 'Sổ lỗi', 'Điểm số']);
    }

    public function test_today_page_shows_the_date_in_the_users_timezone(): void
    {
        $user = User::factory()->create(['name' => 'Tuấn'])->refresh();

        // 18:30 UTC on 28/10 is already Thursday 29/10 in Vietnam.
        $this->travelTo(now('UTC')->setDateTime(2026, 10, 28, 18, 30));

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Chào Tuấn!');
        $response->assertSee('Thứ năm, 29/10/2026');
    }
}
