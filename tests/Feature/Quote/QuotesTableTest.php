<?php

namespace Tests\Feature\Quote;

use App\Enums\Permissions;
use App\Livewire\Quotes\QuotesTable;
use App\Models\User;
use Livewire\Livewire;
use Tests\Feature\FeatureTestCase;

class QuotesTableTest extends FeatureTestCase
{
    public function test_cannot_list_quotes_without_permission(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(QuotesTable::class)
            ->assertForbidden();
    }

    public function test_rechecks_listing_permission_on_livewire_requests(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::QUOTE_VIEW_ALL);
        $component = Livewire::actingAs($user)->test(QuotesTable::class)
            ->assertOk();

        $user->revokePermissionTo(Permissions::QUOTE_VIEW_ALL);

        $component->call('sort', 'status')->assertForbidden();
    }
}
