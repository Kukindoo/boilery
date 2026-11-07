<?php

namespace Tests\Feature\Quote;

use App\Actions\Users\CreateCustomer;
use App\Livewire\Quotes\CustomerQuoteRequestPage;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class CustomerQuoteContactDetailsTest extends FeatureTestCase
{
    /** @return array<string, array{bool, bool}> */
    public static function duplicateEmails(): array
    {
        return [
            'guest using existing email' => [false, false],
            'guest using staff email' => [false, true],
            'signed-in user using another email' => [true, false],
        ];
    }

    public function test_guest_submission_saves_contact_details_on_new_customer(): void
    {
        Notification::fake();

        Livewire::test(CustomerQuoteRequestPage::class)
            ->set('form.firstName', 'Jan')
            ->set('form.lastName', 'Novák')
            ->set('form.email', 'jan@example.test')
            ->set('form.phone', '+420 604 123 456')
            ->set('form.address', 'Dlouhá 1, Praha')
            ->set('form.message', 'Oprava ohřívače')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $user = User::where('email', 'jan@example.test')->sole();
        $this->assertSame('+420 604 123 456', $user->phone);
        $this->assertSame('Dlouhá 1, Praha', $user->address);
        $this->assertSame($user->phone, $user->quotes()->sole()->phone);
    }

    public function test_authenticated_submission_prefills_and_updates_own_contact_details(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'name' => 'Jan Novák', 'phone' => '+420 111 222 333', 'address' => 'Praha',
        ]);

        Livewire::actingAs($user)->test(CustomerQuoteRequestPage::class)
            ->assertSet('form.phone', $user->phone)
            ->assertSet('form.address', 'Praha')
            ->set('form.phone', '+420 604 123 456')
            ->set('form.address', null)
            ->set('form.message', 'Oprava ohřívače')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame('+420 604 123 456', $user->refresh()->phone);
        $this->assertNull($user->address);
    }

    #[DataProvider('duplicateEmails')]
    public function test_submission_rejects_an_existing_email_and_preserves_the_account(bool $authenticated, bool $staff): void
    {
        Notification::fake();
        $user = User::factory()->create(['phone' => '+420 111 222 333', 'address' => 'Praha']);
        if ($staff) {
            $user->assignRole(\App\Enums\Roles::ADMIN);
        }
        $roles = $user->roles()->pluck('roles.id')->all();
        $before = $user->refresh()->getRawOriginal();
        if ($authenticated) {
            $this->actingAs(User::factory()->create(['name' => 'Jana Nováková']));
        }
        $userCount = User::count();

        Livewire::test(CustomerQuoteRequestPage::class)
            ->set('form.firstName', 'Jan')
            ->set('form.lastName', 'Novák')
            ->set('form.email', $user->email)
            ->set('form.phone', '+420 604 123 456')
            ->set('form.address', 'Brno')
            ->set('form.message', 'Oprava ohřívače')
            ->call('submit')
            ->assertHasErrors(['form.email' => 'unique'])
            ->assertSee(CreateCustomer::EMAIL_ALREADY_EXISTS_MESSAGE)
            ->assertSet('submitted', false);

        $this->assertSame($before, $user->fresh()->getRawOriginal());
        $this->assertSame($roles, $user->roles()->pluck('roles.id')->all());
        $this->assertDatabaseCount('users', $userCount);
        $this->assertDatabaseCount('quotes', 0);
        $this->assertDatabaseCount('files', 0);
        Notification::assertNothingSent();
    }
}
