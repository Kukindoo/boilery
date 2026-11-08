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
    public function test_quote_edit_ignores_contact_changes_and_preserves_customer_details(string $emailSource): void
    {
        $staff = User::factory()->create();
        $staff->givePermissionTo(Permissions::QUOTE_EDIT_ALL);
        $customer = User::factory()->customer()->create();
        $another = User::factory()->create();
        $customerBefore = $customer->refresh()->getRawOriginal();
        $anotherBefore = $another->refresh()->getRawOriginal();
        $quote = Quote::factory()->for($customer)->create(['email' => $customer->email]);
        $originalPhone = $quote->phone;
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
            'email' => $customer->email,
            'phone' => $originalPhone,
            'message' => 'Updated problem',
        ]);
        $this->assertSame($customerBefore, $customer->fresh()->getRawOriginal());
        $this->assertSame($anotherBefore, $another->fresh()->getRawOriginal());
        $this->assertDatabaseCount('users', $userCount);
    }

    public function test_quote_edit_does_not_validate_unused_contact_inputs(): void
    {
        $customer = User::factory()->customer()->create();
        $quote = Quote::factory()->for($customer)->create(['email' => $customer->email]);
        $originalPhone = $quote->phone;

        Livewire::actingAs($customer)->test(QuoteEdit::class, ['quote' => $quote])
            ->set('form.email', 'invalid-email')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame($customer->email, $quote->fresh()->email);
    }
}
