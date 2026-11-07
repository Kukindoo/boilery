<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\FeatureTestCase;

class ProfileUpdateTest extends FeatureTestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->get('/settings/profile')->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test(Profile::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('phone', '+420 604 123 456')
            ->set('address', 'Dlouhá 1, Praha')
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $user->refresh();

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertSame('+420 604 123 456', $user->phone);
        $this->assertSame('Dlouhá 1, Praha', $user->address);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_email_address_is_unchanged(): void
    {
        $user = User::factory()->verified()->create();

        $this->actingAs($user);

        $response = Livewire::test(Profile::class)
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_contact_details_are_loaded_and_address_can_be_cleared(): void
    {
        $user = User::factory()->create(['phone' => '+420 604 123 456', 'address' => 'Praha']);

        Livewire::actingAs($user)->test(Profile::class)
            ->assertSet('phone', $user->phone)
            ->assertSet('address', 'Praha')
            ->set('address', '')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertNull($user->refresh()->address);
        $this->assertSame('+420 604 123 456', $user->phone);
    }

    public function test_contact_details_are_validated_before_saving(): void
    {
        $user = User::factory()->create(['address' => 'Praha']);
        $phone = $user->phone;

        Livewire::actingAs($user)->test(Profile::class)
            ->set('phone', '')
            ->set('address', str_repeat('a', 501))
            ->call('updateProfileInformation')
            ->assertHasErrors(['phone' => 'required', 'address' => 'max']);

        $this->assertSame($phone, $user->refresh()->phone);
        $this->assertSame('Praha', $user->address);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('settings.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser');

        $response
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($user->fresh());
        $this->assertFalse(auth()->check());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('settings.delete-user-form')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $response->assertHasErrors(['password']);

        $this->assertNotNull($user->fresh());
    }
}
