<?php

namespace Tests\Feature\Quote;

use App\Enums\Permissions;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class QuoteControllerIndexTest extends FeatureTestCase
{
    /** @return array<string, array{?Permissions, bool}> */
    public static function previewPermissions(): array
    {
        return [
            'view all, own quote' => [Permissions::QUOTE_VIEW_ALL, true],
            'view own, own quote' => [Permissions::QUOTE_VIEW_OWN, false],
            'no permission, own quote' => [null, false],
            'view index, own quote' => [Permissions::QUOTE_VIEW_INDEX, false],
            'print all, own quote' => [Permissions::QUOTE_PRINT_ALL, false],
            'edit all, own quote' => [Permissions::QUOTE_EDIT_ALL, false],
            'edit own, own quote' => [Permissions::QUOTE_EDIT_OWN, false],
        ];
    }

    #[DataProvider('previewPermissions')]
    public function test_checks_permissions(?Permissions $permission, bool $allowed): void
    {
        $user = User::factory()->create();

        if ($permission !== null) {
            $user->givePermissionTo($permission);
        }

        $response = $this->actingAs($user)->get(route('quotes.index'));

        if ($allowed) {
            $response->assertOk();
        } else {
            $response->assertForbidden();
        }
    }
}
