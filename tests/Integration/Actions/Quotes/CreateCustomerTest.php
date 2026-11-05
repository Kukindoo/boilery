<?php

namespace Tests\Integration\Actions\Quotes;

use App\Actions\Users\CreateCustomer;
use App\Dtos\NamePlate;
use App\Enums\Roles;
use App\Models\User;
use Exception;
use Tests\Integration\IntegrationTestCase;

class CreateCustomerTest extends IntegrationTestCase
{
    /**
     * @throws Exception
     */
    public function test_creates_customer_with_full_name_email_contact_details_and_hashed_password(): void
    {
        $userCount = User::count();

        $customer = app(CreateCustomer::class)->handle($this->namePlateObject());

        $this->assertDatabaseCount('users', $userCount + 1);
        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'name' => 'Jan Novak',
            'email' => 'jan@example.com',
            'phone' => '+420 604 123 456',
            'address' => 'Dlouhá 1, Praha',
        ]);
        $this->assertSame('bcrypt', password_get_info($customer->fresh()->password)['algoName']);
        $this->assertSame([Roles::CUSTOMER->value], $customer->roles()->pluck('id')->all());
    }

    /**
     * @throws Exception
     */
    public function test_reuses_existing_user_without_changing_name_password_or_verification(): void
    {
        $existingUser = User::factory()->verified()->customer()->create([
            'name' => 'Existing Customer',
            'email' => 'jan@example.com',
            'phone' => '+420 111 222 333',
            'address' => 'Brno',
        ]);
        $password = $existingUser->password;
        $verifiedAt = $existingUser->getRawOriginal('email_verified_at');

        $userCount = User::count();

        $customer = app(CreateCustomer::class)->handle($this->namePlateObject());

        $this->assertSame($existingUser->id, $customer->id);
        $this->assertDatabaseCount('users', $userCount);
        $customer->refresh();
        $this->assertSame('Existing Customer', $customer->name);
        $this->assertSame($password, $customer->password);
        $this->assertSame($verifiedAt, $customer->getRawOriginal('email_verified_at'));
        $this->assertSame('+420 604 123 456', $customer->phone);
        $this->assertSame('Dlouhá 1, Praha', $customer->address);
        $this->assertSame([Roles::CUSTOMER->value], $customer->roles()->pluck('id')->all());
    }

    /**
     * @throws Exception
     */
    public function test_preserves_existing_staff_role_when_adding_customer_role(): void
    {
        $existingUser = User::factory()->admin()->create(['email' => 'jan@example.com']);

        $userCount = User::count();

        $customer = app(CreateCustomer::class)->handle($this->namePlateObject());

        $this->assertSame($existingUser->id, $customer->id);
        $this->assertDatabaseCount('users', $userCount);
        $this->assertSame(
            [Roles::ADMIN->value, Roles::CUSTOMER->value],
            $customer->roles()->orderBy('roles.id')->pluck('roles.id')->all(),
        );
    }

    /**
     * @throws Exception
     */
    public function test_reuses_existing_customer_without_duplicate_role_assignment(): void
    {
        $existingUser = User::factory()->customer()->create(['email' => 'jan@example.com']);
        $this->assertSame([Roles::CUSTOMER->value], $existingUser->roles()->pluck('id')->all());

        $userCount = User::count();

        $customer = app(CreateCustomer::class)->handle($this->namePlateObject());

        $this->assertSame($existingUser->id, $customer->id);
        $this->assertDatabaseCount('users', $userCount);
        $this->assertSame([Roles::CUSTOMER->value], $customer->roles()->pluck('id')->all());
    }

    public function test_creates_customer_when_optional_address_is_omitted(): void
    {
        $customer = app(CreateCustomer::class)->handle(
            new NamePlate('Jan', 'Novak', 'jan@example.com', '+420 604 123 456'),
        );

        $this->assertSame('+420 604 123 456', $customer->fresh()->phone);
        $this->assertNull($customer->address);
    }

    public function test_empty_address_clears_existing_customer_address(): void
    {
        $existingUser = User::factory()->customer()->create([
            'email' => 'jan@example.com',
            'address' => 'Praha',
        ]);

        $customer = app(CreateCustomer::class)->handle(
            new NamePlate('Jan', 'Novak', 'jan@example.com', '+420 604 123 456', ''),
        );

        $this->assertSame($existingUser->id, $customer->id);
        $this->assertNull($existingUser->refresh()->address);
        $this->assertSame('+420 604 123 456', $existingUser->phone);
    }

    private function namePlateObject(): NamePlate
    {
        return new NamePlate('Jan', 'Novak', 'jan@example.com', '+420 604 123 456', 'Dlouhá 1, Praha');
    }
}
