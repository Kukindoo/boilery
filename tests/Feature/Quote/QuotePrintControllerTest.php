<?php

namespace Tests\Feature\Quote;

use App\Enums\Permissions;
use App\Models\Quote;
use App\Models\User;
use Tests\TestCase;

class QuotePrintControllerTest extends TestCase
{
    public function test_admin_has_access_to_print_own_quote()
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->for($user)->create();

        $user->givePermissionTo(Permissions::QUOTE_VIEW_ALL);

        $response = $this->actingAs($user)->get(route('quotes.print', $quote));

        $response->assertStatus(200);
    }

    public function test_admin_has_access_to_print_others_quote()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $quote = Quote::factory()->for($otherUser)->create();

        $user->givePermissionTo(Permissions::QUOTE_VIEW_ALL);

        $response = $this->actingAs($user)->get(route('quotes.print', $quote));

        $response->assertStatus(200);
    }

    public function test_user_has_access_to_print_own_quote()
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->for($user)->create();

        $user->givePermissionTo(Permissions::QUOTE_VIEW_OWN);

        $response = $this->actingAs($user)->get(route('quotes.print', $quote));

        $response->assertStatus(200);
    }

    public function test_user_has_no_access_to_print_others_quote()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $quote = Quote::factory()->for($otherUser)->create();

        $user->givePermissionTo(Permissions::QUOTE_VIEW_OWN);

        $response = $this->actingAs($user)->get(route('quotes.print', $quote));

        $response->assertStatus(403);
    }

    public function test_user_without_permission_has_no_access_to_print()
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->for($user)->create();

        // Do not give the user any permissions

        $response = $this->actingAs($user)->get(route('quotes.print', $quote));

        $response->assertStatus(403);
    }
}
