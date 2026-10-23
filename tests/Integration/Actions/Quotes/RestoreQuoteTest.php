<?php

namespace Tests\Integration\Actions\Quotes;

use App\Actions\Quotes\RestoreQuote;
use App\Enums\Permissions;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\Integration\IntegrationTestCase;

class RestoreQuoteTest extends IntegrationTestCase
{
    public static function deniedOperations(): array
    {
        return [
            'guest' => [false, false, QuoteStatus::NEW, true],
            'no permission' => [true, false, QuoteStatus::NEW, true],
            'invalid archive state' => [true, true, QuoteStatus::NEW, false],
        ];
    }

    public function test_performs_authorized_operation(): void
    {
        $this->actingAs($this->operator(true));
        $quote = Quote::factory()->create(['deleted_at' => now()]);

        app(RestoreQuote::class)->handle($quote);

        $this->assertNotSoftDeleted($quote);
    }

    #[DataProvider('deniedOperations')]
    public function test_denies_operation_without_changing_record(bool $authenticated, bool $permitted, QuoteStatus $status, bool $trashed): void
    {
        if ($authenticated) {
            $this->actingAs($this->operator($permitted));
        }
        $quote = Quote::factory()->create(['status' => $status, 'deleted_at' => $trashed ? now() : null]);
        $quote->refresh();
        $before = $quote->getRawOriginal();

        try {
            app(RestoreQuote::class)->handle($quote);
            $this->fail('Operation should be forbidden.');
        } catch (AuthorizationException) {
            $this->assertSame($before, Quote::withTrashed()->findOrFail($quote->id)->getRawOriginal());
        }
    }

    private function operator(bool $permitted): User
    {
        $user = User::factory()->create();
        if ($permitted) {
            $role = Role::create(['name' => 'quote-action-test', 'guard_name' => 'web']);
            $role->givePermissionTo(Permissions::QUOTE_RESTORE_ALL);
            $user->assignRole($role);
        }

        return $user;
    }
}
