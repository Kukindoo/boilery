<?php

namespace Tests\Feature\Quote;

use App\Enums\Permissions;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class QuoteAcceptControllerTest extends FeatureTestCase
{
    /** @return array<string, array{?Permissions, string}> */
    public static function destinations(): array
    {
        return [
            'can view work' => [Permissions::WORK_VIEW_ALL, 'works.show'],
            'can only view quote' => [Permissions::QUOTE_VIEW_ALL, 'quotes.show'],
            'cannot view either record' => [null, 'home'],
        ];
    }

    #[DataProvider('destinations')]
    public function test_acceptance_redirects_to_an_accessible_page(?Permissions $viewPermission, string $destination): void
    {
        $user = User::factory()->verified()->create();
        $user->givePermissionTo(Permissions::QUOTE_EDIT_ALL);

        if ($viewPermission !== null) {
            $user->givePermissionTo($viewPermission);
        }

        $quote = Quote::factory()->for(User::factory()->customer())->create();

        $response = $this->actingAs($user)->post(route('quotes.accept', $quote));

        $quote->refresh();
        $response->assertRedirectToRoute($destination, match ($destination) {
            'works.show' => $quote->work,
            'quotes.show' => $quote,
            default => [],
        });
        $this->assertSame(QuoteStatus::ACCEPTED, $quote->status);
        $this->assertDatabaseHas('works', ['quote_id' => $quote->id, 'user_id' => $quote->user_id]);
        $this->get($response->headers->get('Location'))->assertOk();
    }

    public function test_get_cannot_accept_a_quote(): void
    {
        $quote = Quote::factory()->for(User::factory()->customer())->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('quotes.accept', $quote))
            ->assertMethodNotAllowed();

        $this->assertSame(QuoteStatus::NEW, $quote->fresh()->status);
        $this->assertDatabaseCount('works', 0);
    }

    public function test_guests_cannot_accept_a_quote(): void
    {
        $quote = Quote::factory()->for(User::factory()->customer())->create();

        $this->post(route('quotes.accept', $quote))->assertRedirectToRoute('login');

        $this->assertSame(QuoteStatus::NEW, $quote->fresh()->status);
        $this->assertDatabaseCount('works', 0);
    }

    public function test_users_without_permission_cannot_accept_a_quote(): void
    {
        $quote = Quote::factory()->for(User::factory()->customer())->create();

        $this->actingAs(User::factory()->customer()->create())
            ->post(route('quotes.accept', $quote))
            ->assertForbidden();

        $this->assertSame(QuoteStatus::NEW, $quote->fresh()->status);
        $this->assertDatabaseCount('works', 0);
    }

    public function test_quote_without_customer_returns_a_validation_error(): void
    {
        $quote = Quote::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('quotes.accept', $quote))
            ->assertInvalid(['quote' => 'A quote must have a customer before it can be accepted.']);

        $this->assertSame(QuoteStatus::NEW, $quote->fresh()->status);
        $this->assertDatabaseCount('works', 0);
    }
}
