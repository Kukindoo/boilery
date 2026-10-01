<?php

namespace Tests\Feature\Quote;

use App\Enums\Permissions;
use App\Models\Quote;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuotePrintControllerTest extends TestCase
{
    /** @return array<string, array{?Permissions, bool, bool}> */
    public static function previewPermissions(): array
    {
        return [
            'view all, own quote' => [Permissions::QUOTE_VIEW_ALL, true, true],
            'view all, another user quote' => [Permissions::QUOTE_VIEW_ALL, false, true],
            'view own, own quote' => [Permissions::QUOTE_VIEW_OWN, true, true],
            'view own, another user quote' => [Permissions::QUOTE_VIEW_OWN, false, false],
            'no permission, own quote' => [null, true, false],
            'no permission, another user quote' => [null, false, false],
            'view index, own quote' => [Permissions::QUOTE_VIEW_INDEX, true, false],
            'view index, another user quote' => [Permissions::QUOTE_VIEW_INDEX, false, false],
            'print all, own quote' => [Permissions::QUOTE_PRINT_ALL, true, false],
            'print all, another user quote' => [Permissions::QUOTE_PRINT_ALL, false, false],
            'edit all, own quote' => [Permissions::QUOTE_EDIT_ALL, true, false],
            'edit all, another user quote' => [Permissions::QUOTE_EDIT_ALL, false, false],
            'edit own, own quote' => [Permissions::QUOTE_EDIT_OWN, true, false],
            'edit own, another user quote' => [Permissions::QUOTE_EDIT_OWN, false, false],
        ];
    }

    #[DataProvider('previewPermissions')]
    public function test_preview_checks_permissions_and_quote_ownership(?Permissions $permission, bool $ownsQuote, bool $allowed): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->for($ownsQuote ? $user : User::factory()->create())->create();

        if ($permission !== null) {
            $user->givePermissionTo($permission);
        }

        $response = $this->actingAs($user)->get(route('quotes.print', $quote));

        if ($allowed) {
            $response->assertOk();
        } else {
            $response->assertForbidden();
        }
    }
}
