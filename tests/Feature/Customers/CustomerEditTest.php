<?php

namespace Tests\Feature\Customers;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Livewire\Customers\CustomerEdit;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

class CustomerEditTest extends FeatureTestCase
{
    public static function staffRoles(): array
    {
        return ['admin' => [Roles::ADMIN], 'stakeholder' => [Roles::STAKEHOLDER]];
    }

    #[DataProvider('staffRoles')]
    public function test_staff_can_update_customer_contact_details(Roles $role): void
    {
        $staff = User::factory()->create();
        $staff->assignRole($role->value);
        $customer = User::factory()->verified()->customer()->create(['address' => 'Praha']);
        $password = $customer->password;

        Livewire::actingAs($staff)->test(CustomerEdit::class, ['customer' => $customer])
            ->assertSet('form.name', $customer->name)
            ->assertSet('form.email', $customer->email)
            ->assertSet('form.phone', $customer->phone)
            ->assertSet('form.address', 'Praha')
            ->set('form.name', 'Jan Novák')
            ->set('form.email', 'jan@example.test')
            ->set('form.phone', '+420 604 123 456')
            ->set('form.address', 'Dlouhá 1, Brno')
            ->call('submit')->assertHasNoErrors()
            ->assertRedirect(route('customers.show', $customer));

        $customer->refresh();
        $this->assertSame('Jan Novák', $customer->name);
        $this->assertSame('jan@example.test', $customer->email);
        $this->assertSame('+420 604 123 456', $customer->phone);
        $this->assertSame('Dlouhá 1, Brno', $customer->address);
        $this->assertNull($customer->email_verified_at);
        $this->assertSame($password, $customer->password);
        $this->assertSame([Roles::CUSTOMER->label()], $customer->getRoleNames()->all());
        $this->get(route('customers.show', $customer))->assertOk()->assertSee('Dlouhá 1, Brno');
    }

    public function test_address_can_be_cleared_without_resetting_email_verification(): void
    {
        $customer = User::factory()->verified()->customer()->create(['address' => 'Praha']);
        $verifiedAt = $customer->getRawOriginal('email_verified_at');

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(CustomerEdit::class, ['customer' => $customer])
            ->set('form.address', '')->call('submit')->assertHasNoErrors();

        $this->assertNull($customer->refresh()->address);
        $this->assertSame($verifiedAt, $customer->getRawOriginal('email_verified_at'));
    }

    public function test_validation_prevents_duplicate_email_and_invalid_contact_details(): void
    {
        $customer = User::factory()->customer()->create();
        $other = User::factory()->create();
        $original = $customer->refresh()->getAttributes();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(CustomerEdit::class, ['customer' => $customer])
            ->set('form.name', '')
            ->set('form.email', $other->email)
            ->set('form.phone', '')
            ->set('form.address', str_repeat('a', 501))
            ->call('submit')
            ->assertHasErrors([
                'form.name' => 'required', 'form.email' => 'unique',
                'form.phone' => 'required', 'form.address' => 'max',
            ]);

        $this->assertSame($original, $customer->refresh()->getAttributes());
    }

    public function test_save_rechecks_edit_permission(): void
    {
        $staff = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create(['name' => 'Original Customer']);
        $component = Livewire::actingAs($staff)->test(CustomerEdit::class, ['customer' => $customer]);
        Role::findById(Roles::ADMIN->value, 'web')->revokePermissionTo(Permissions::USER_EDIT_ALL->label());

        $component->set('form.name', 'Unauthorized change')->call('submit')->assertForbidden();
        $this->assertSame('Original Customer', $customer->refresh()->name);
    }

    public function test_customer_can_edit_own_details_and_cannot_edit_another_customer(): void
    {
        $customer = User::factory()->customer()->create(['name' => 'Original Customer']);
        Livewire::actingAs($customer)->test(CustomerEdit::class, ['customer' => $customer])
            ->set('form.name', 'Updated Customer')->call('submit')->assertHasNoErrors();
        $this->assertSame('Updated Customer', $customer->refresh()->name);

        $other = User::factory()->customer()->create();
        Livewire::test(CustomerEdit::class, ['customer' => $other])->assertForbidden();
    }
}
