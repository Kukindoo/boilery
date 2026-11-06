<?php

namespace Tests\Integration\Actions\Quotes;

use App\Actions\Users\CreateCustomer;
use App\Dtos\NamePlate;
use App\Enums\Roles;
use App\Models\User;
use Exception;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Integration\IntegrationTestCase;

class CreateCustomerTest extends IntegrationTestCase
{
    /** @return array<string, array{bool, bool}> */
    public static function existingAccounts(): array
    {
        return [
            'guest using customer email' => [false, false],
            'guest using staff email' => [false, true],
            'signed-in user using another customer email' => [true, false],
            'signed-in user using staff email' => [true, true],
        ];
    }

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

        $this->actingAs($existingUser);

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

        $this->actingAs($existingUser);

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

        $this->actingAs($existingUser);

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

        $this->actingAs($existingUser);

        $customer = app(CreateCustomer::class)->handle(
            new NamePlate('Jan', 'Novak', 'jan@example.com', '+420 604 123 456', ''),
        );

        $this->assertSame($existingUser->id, $customer->id);
        $this->assertNull($existingUser->refresh()->address);
        $this->assertSame('+420 604 123 456', $existingUser->phone);
    }

    #[DataProvider('existingAccounts')]
    public function test_rejects_an_existing_email_without_changing_the_account(bool $authenticated, bool $staff): void
    {
        $existingUser = User::factory()->create([
            'email' => 'jan@example.com',
            'phone' => '+420 111 222 333',
            'address' => 'Praha',
        ]);
        if ($staff) {
            $existingUser->assignRole(Roles::ADMIN);
        }
        if ($authenticated) {
            $this->actingAs(User::factory()->create());
        }
        $before = $existingUser->refresh()->getRawOriginal();
        $roles = $existingUser->roles()->pluck('roles.id')->all();
        $userCount = User::count();

        try {
            app(CreateCustomer::class)->handle($this->namePlateObject());
            $this->fail('An existing email must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame([
                'email' => [CreateCustomer::EMAIL_ALREADY_EXISTS_MESSAGE],
            ], $exception->errors());
        }

        $this->assertSame($before, $existingUser->fresh()->getRawOriginal());
        $this->assertSame($roles, $existingUser->roles()->pluck('roles.id')->all());
        $this->assertDatabaseCount('users', $userCount);
    }

    private function namePlateObject(): NamePlate
    {
        return new NamePlate('Jan', 'Novak', 'jan@example.com', '+420 604 123 456', 'Dlouhá 1, Praha');
    }
}
