<?php

namespace Tests\Feature\Policies;

use App\Enums\Permissions;
use App\Models\User;
use App\Models\Work;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

class WorkPolicyTest extends FeatureTestCase
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

    /** @return array<string, array{string, ?Permissions, string, bool, bool, bool}> */
    public static function recordPermissions(): array
    {
        $cases = [];
        $abilities = [
            'view' => [Permissions::WORK_VIEW_ALL, Permissions::WORK_VIEW_OWN],
            'update' => [Permissions::WORK_EDIT_ALL, Permissions::WORK_EDIT_OWN],
            'delete' => [Permissions::WORK_DELETE_ALL, Permissions::WORK_DELETE_OWN],
            'restore' => [Permissions::WORK_RESTORE_ALL, Permissions::WORK_RESTORE_OWN],
            'forceDelete' => [Permissions::WORK_FORCE_DELETE_ALL, Permissions::WORK_FORCE_DELETE_OWN],
        ];

        foreach ($abilities as $ability => [$all, $own]) {
            foreach (self::permissions() as $permissionName => [$permission]) {
                foreach (['own', 'another'] as $ownership) {
                    foreach ([false, true] as $trashed) {
                        foreach ([false, true] as $completed) {
                            $allowed = $permission === $all
                                || (! in_array($ability, ['delete', 'forceDelete'], true)
                                    && $permission === $own
                                    && $ownership === 'own');

                            if ($ability === 'delete' && $trashed) {
                                $allowed = false;
                            }

                            if (in_array($ability, ['restore', 'forceDelete'], true) && ! $trashed) {
                                $allowed = false;
                            }

                            $key = implode(', ', [$ability, $permissionName, $ownership, $trashed ? 'trashed' : 'active', $completed ? 'completed' : 'in progress']);
                            $cases[$key] = [$ability, $permission, $ownership, $trashed, $completed, $allowed];
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
            in_array($permission, [Permissions::WORK_CREATE_ALL, Permissions::WORK_CREATE_OWN], true),
            $user->can('create', Work::class),
        );
    }

    #[DataProvider('permissions')]
    public function test_index_requires_view_all_permission(?Permissions $permission): void
    {
        $user = $this->userWithPermission($permission);

        $this->assertSame($permission === Permissions::WORK_VIEW_ALL, $user->can('viewAny', Work::class));
    }

    #[DataProvider('recordPermissions')]
    public function test_checks_record_permissions_ownership_and_state(string $ability, ?Permissions $permission, string $ownership, bool $trashed, bool $completed, bool $allowed): void
    {
        $user = $this->userWithPermission($permission);
        $ownerId = match ($ownership) {
            'own' => $user->id,
            'another' => $user->id + 1,
            'none' => null,
        };
        $work = Work::factory()->make([
            'user_id' => $ownerId,
            'work_completed_at' => $completed ? '2026-10-01' : null,
            'deleted_at' => $trashed ? '2026-10-01 12:00:00' : null,
        ]);

        $this->assertSame($allowed, $user->can($ability, $work));
    }

    public function test_guests_cannot_use_work_policy_abilities(): void
    {
        $work = Work::factory()->make();

        foreach (['viewAny', 'create'] as $ability) {
            $this->assertFalse(Gate::allows($ability, Work::class));
        }

        foreach (['view', 'update', 'delete', 'restore', 'forceDelete'] as $ability) {
            $this->assertFalse(Gate::allows($ability, $work));
        }
    }

    public function test_gate_permission_override_is_respected_inside_the_policy(): void
    {
        $user = User::factory()->create();
        Gate::before(fn (User $user, string $ability): ?bool => $ability === Permissions::WORK_CREATE_ALL->label() ? true : null);

        $this->assertTrue($user->can('create', Work::class));
    }

    private function userWithPermission(?Permissions $permission): User
    {
        $user = User::factory()->create();

        if ($permission !== null) {
            $role = Role::create(['name' => 'work-policy-test', 'guard_name' => 'web']);
            $role->givePermissionTo($permission);
            $user->assignRole($role);
        }

        return $user;
    }
}
