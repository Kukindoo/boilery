<?php

namespace Tests\Feature\Quote;

use App\Enums\Permissions;
use App\Livewire\Quotes\QuoteEdit;
use App\Models\Quote;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class QuoteEditTest extends FeatureTestCase
{
    public static function contactEmails(): array
    {
        return [
            'unchanged customer email' => ['customer'],
            'another existing account email' => ['another'],
            'unregistered contact email' => ['new'],
        ];
    }

    #[DataProvider('contactEmails')]
    public function test_staff_can_edit_quote_contact_details_without_account_email_uniqueness(string $emailSource): void
    {
        $staff = User::factory()->create();
        $staff->givePermissionTo(Permissions::QUOTE_EDIT_ALL);
        $customer = User::factory()->customer()->create();
        $another = User::factory()->create();
        $customerBefore = $customer->refresh()->getRawOriginal();
        $anotherBefore = $another->refresh()->getRawOriginal();
        $quote = Quote::factory()->for($customer)->create(['email' => $customer->email]);
        $email = match ($emailSource) {
            'customer' => $customer->email,
            'another' => $another->email,
            'new' => 'contact@example.test',
        };
        $userCount = User::count();

        Livewire::actingAs($staff)->test(QuoteEdit::class, ['quote' => $quote])
            ->set('form.email', $email)
            ->set('form.phone', '+420123456789')
            ->set('form.message', 'Updated problem')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('quotes.show', $quote));

        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'user_id' => $customer->id,
            'email' => $email,
            'phone' => '+420123456789',
            'message' => 'Updated problem',
        ]);
        $this->assertSame($customerBefore, $customer->fresh()->getRawOriginal());
        $this->assertSame($anotherBefore, $another->fresh()->getRawOriginal());
        $this->assertDatabaseCount('users', $userCount);
    }

    public function test_edit_still_rejects_invalid_email_addresses(): void
    {
        $customer = User::factory()->customer()->create();
        $quote = Quote::factory()->for($customer)->create(['email' => $customer->email]);

        Livewire::actingAs($customer)->test(QuoteEdit::class, ['quote' => $quote])
            ->set('form.email', 'invalid-email')
            ->call('submit')
            ->assertHasErrors(['form.email' => 'email']);

        $this->assertSame($customer->email, $quote->fresh()->email);
    }
}
