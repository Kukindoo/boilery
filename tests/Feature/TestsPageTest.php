<?php

namespace Tests\Feature;

use App\Enums\Roles;
use App\Models\User;
use App\Notifications\Tests\EmailColdTestNotification;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;

class TestsPageTest extends FeatureTestCase
{
    /** @return array<string, array{Roles|null}> */
    public static function nonAdminRoles(): array
    {
        return [
            'stakeholder' => [Roles::STAKEHOLDER],
            'customer' => [Roles::CUSTOMER],
            'no role' => [null],
        ];
    }

    public function test_admin_can_open_tests_from_navigation_and_send_the_email(): void
    {
        Notification::fake();
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('dashboard'))->assertSee(route('tests.index'));
        $this->get(route('tests.index'))->assertSee('Send Cold Email Test');
        $this->from(route('tests.index'))->post(route('cold-tests.email'))
            ->assertRedirect(route('tests.index'))
            ->assertSessionHas('status', 'Test email sent successfully.');

        Notification::assertSentOnDemand(EmailColdTestNotification::class);
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admins_cannot_see_or_use_tests(?Roles $role): void
    {
        Notification::fake();
        $user = User::factory()->create();
        if ($role !== null) {
            $user->assignRole($role);
        }
        $this->actingAs($user);

        $this->get(route('dashboard'))->assertDontSee(route('tests.index'));
        $this->get(route('tests.index'))->assertForbidden();
        $this->post(route('cold-tests.email'))->assertForbidden();

        Notification::assertNothingSent();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('tests.index'))->assertRedirect(route('login'));
        $this->post(route('cold-tests.email'))->assertRedirect(route('login'));
    }
}
