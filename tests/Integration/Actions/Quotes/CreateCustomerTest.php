<?php

namespace Tests\Integration\Actions\Quotes;

use App\Actions\Quotes\CreateCustomer;
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
    public function test_creates_customer_with_full_name_email_and_hashed_password(): void
    {
        $userCount = User::count();

        $customer = app(CreateCustomer::class)->handle($this->namePlateObject());

        $this->assertDatabaseCount('users', $userCount + 1);
        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'name' => 'Jan Novak',
            'email' => 'jan@example.com',
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

    /** @return NamePlate */
    private function namePlateObject(): NamePlate
    {
        return new NamePlate('Jan', 'Novak', 'jan@example.com');
    }
}
