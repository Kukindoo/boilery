<?php

namespace Tests\Feature\Quote;

use App\Enums\Roles;
use App\Models\Quote;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class QuoteShowIssueTest extends FeatureTestCase
{
    /** @return array<string, array{Roles}> */
    public static function staffRoles(): array
    {
        return [
            'admin' => [Roles::ADMIN],
            'stakeholder' => [Roles::STAKEHOLDER],
        ];
    }

    #[DataProvider('staffRoles')]
    public function test_staff_can_follow_issue_link_from_quote(Roles $role): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $user->assignRole($role);
        $quote = Quote::factory()->create();

        $this->actingAs($user)->get(route('quotes.show', $quote))
            ->assertSee('Případ #' . $quote->issue_id)
            ->assertSee('href="' . route('issues.show', $quote->issue) . '"', false);
    }

    public function test_customer_sees_issue_id_without_link(): void
    {
        $this->withoutVite();
        $customer = User::factory()->customer()->create();
        $quote = Quote::factory()->forCustomer($customer)->create();

        $this->actingAs($customer)->get(route('quotes.show', $quote))
            ->assertSee('Případ #' . $quote->issue_id)
            ->assertDontSee('href="' . route('issues.show', $quote->issue) . '"', false);
    }
}
