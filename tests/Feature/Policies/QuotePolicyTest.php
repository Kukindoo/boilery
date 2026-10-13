<?php

namespace Tests\Feature\Policies;

use App\Enums\Permissions;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

class QuotePolicyTest extends FeatureTestCase
{
    /** @return array<string, array{?Permissions}> */
    public static function permissions(): array
    {
        $cases = ['none' => [null]];

        foreach (Permissions::cases() as $permission) {
            $cases[$permission->name] = [$permission];
        }

        return $cases;
    }

    /** @return array<string, array{string, ?Permissions, string, bool, QuoteStatus, bool}> */
    public static function recordPermissions(): array
    {
        $cases = [];
        $abilities = [
            'view' => [Permissions::QUOTE_VIEW_ALL, Permissions::QUOTE_VIEW_OWN],
            'update' => [Permissions::QUOTE_EDIT_ALL, Permissions::QUOTE_EDIT_OWN],
            'delete' => [Permissions::QUOTE_DELETE_ALL, Permissions::QUOTE_DELETE_OWN],
            'restore' => [Permissions::QUOTE_RESTORE_ALL, Permissions::QUOTE_RESTORE_OWN],
            'forceDelete' => [Permissions::QUOTE_FORCE_DELETE_ALL, Permissions::QUOTE_FORCE_DELETE_OWN],
        ];

        foreach ($abilities as $ability => [$all, $own]) {
            foreach (self::permissions() as $permissionName => [$permission]) {
                foreach (['own', 'another', 'none'] as $ownership) {
                    foreach ([false, true] as $trashed) {
                        foreach ([QuoteStatus::NEW, QuoteStatus::ACCEPTED, QuoteStatus::REJECTED] as $status) {
                            $allowed = $permission === $all
                                || (! in_array($ability, ['delete', 'forceDelete'], true)
                                    && $permission === $own
                                    && $ownership === 'own');

                            if ($ability === 'update' && $permission === $own && $status !== QuoteStatus::NEW) {
                                $allowed = false;
                            }

                            if ($ability === 'delete' && $trashed) {
                                $allowed = false;
                            }

                            if (in_array($ability, ['restore', 'forceDelete'], true) && ! $trashed) {
                                $allowed = false;
                            }

                            $key = implode(', ', [$ability, $permissionName, $ownership, $trashed ? 'trashed' : 'active', $status->value]);
                            $cases[$key] = [$ability, $permission, $ownership, $trashed, $status, $allowed];
                        }
                    }
                }
            }
        }

        return $cases;
    }

    #[DataProvider('permissions')]
    public function test_create_requires_its_own_permission(?Permissions $permission): void
    {
        $user = $this->userWithPermission($permission);

        $this->assertSame(
            in_array($permission, [Permissions::QUOTE_CREATE_ALL, Permissions::QUOTE_CREATE_OWN], true),
            $user->can('create', Quote::class),
        );
    }

    #[DataProvider('permissions')]
    public function test_index_requires_view_all_permission(?Permissions $permission): void
    {
        $user = $this->userWithPermission($permission);

        $this->assertSame($permission === Permissions::QUOTE_VIEW_ALL, $user->can('viewAny', Quote::class));
    }

    #[DataProvider('recordPermissions')]
    public function test_checks_record_permissions_ownership_and_state(string $ability, ?Permissions $permission, string $ownership, bool $trashed, QuoteStatus $status, bool $allowed): void
    {
        $user = $this->userWithPermission($permission);
        $ownerId = match ($ownership) {
            'own' => $user->id,
            'another' => $user->id + 1,
            'none' => null,
        };
        $quote = Quote::factory()->make([
            'user_id' => $ownerId,
            'status' => $status,
            'deleted_at' => $trashed ? '2026-10-01 12:00:00' : null,
        ]);

        $this->assertSame($allowed, $user->can($ability, $quote));
    }

    public function test_guests_cannot_use_quote_policy_abilities(): void
    {
        $quote = Quote::factory()->make();

        foreach (['viewAny', 'create'] as $ability) {
            $this->assertFalse(Gate::allows($ability, Quote::class));
        }

        foreach (['view', 'update', 'delete', 'restore', 'forceDelete'] as $ability) {
            $this->assertFalse(Gate::allows($ability, $quote));
        }
    }

    public function test_gate_permission_override_is_respected_inside_the_policy(): void
    {
        $user = User::factory()->create();
        Gate::before(fn (User $user, string $ability): ?bool => $ability === Permissions::QUOTE_CREATE_ALL->label() ? true : null);

        $this->assertTrue($user->can('create', Quote::class));
    }

    private function userWithPermission(?Permissions $permission): User
    {
        $user = User::factory()->create();

        if ($permission !== null) {
            $role = Role::create(['name' => 'quote-policy-test', 'guard_name' => 'web']);
            $role->givePermissionTo($permission);
            $user->assignRole($role);
        }

        return $user;
    }
}
