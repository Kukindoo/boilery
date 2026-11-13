<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\TestWith;

class ForbiddenResponseTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/test-forbidden', function (): never {
            abort(403);
        });
    }

    public function test_browser_returns_to_previous_page_with_modal(): void
    {
        $this->from('/')->get('/test-forbidden')
            ->assertRedirect(route('home'))
            ->assertSessionHas('access_denied', true);

        $this->get('/')->assertSee('access-denied-title', false)->assertSee('Dismiss');
        $this->get('/')->assertDontSee('x-init="$nextTick(() => show())"', false);
    }

    #[TestWith([null])]
    #[TestWith(['http://localhost/test-forbidden'])]
    #[TestWith(['http://localhost/test-forbidden?filter=1'])]
    #[TestWith(['https://example.com/'])]
    public function test_missing_unsafe_or_same_page_previous_url_falls_back_to_home(?string $previous): void
    {
        if ($previous !== null) {
            $this->from($previous);
        }

        $this->get('/test-forbidden')->assertRedirect(route('home'))
            ->assertSessionHas('access_denied', true);
    }

    public function test_browser_returns_to_a_different_previous_page(): void
    {
        $this->from('/settings/profile')->get('/test-forbidden')
            ->assertRedirect(url('/settings/profile'));
    }

    public function test_json_keeps_forbidden_status(): void
    {
        $this->getJson('/test-forbidden')->assertForbidden()
            ->assertSessionMissing('access_denied');
    }

    public function test_livewire_keeps_forbidden_status_for_client_modal(): void
    {
        $this->withHeaders(['X-Livewire' => ''])->get('/test-forbidden')
            ->assertForbidden()->assertSessionMissing('access_denied');
    }
}
