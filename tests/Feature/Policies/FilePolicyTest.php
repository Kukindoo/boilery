<?php

namespace Tests\Feature\Policies;

use App\Enums\Permissions;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

class FilePolicyTest extends FeatureTestCase
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

    #[DataProvider('permissions')]
    public function test_file_permissions_respect_quote_ownership(?Permissions $permission): void
    {
        $user = User::factory()->create();

        if ($permission !== null) {
            $role = Role::create(['name' => 'file-policy-test', 'guard_name' => 'web']);
            $role->givePermissionTo($permission);
            $user->assignRole($role);
        }

        $this->assertSame($permission === Permissions::FILE_VIEW_ALL, $user->can('viewAny', File::class));
        $this->assertSame(in_array($permission, [Permissions::FILE_CREATE_ALL, Permissions::FILE_CREATE_OWN], true), $user->can('create', File::class));

        foreach (['view' => [Permissions::FILE_VIEW_ALL, Permissions::FILE_VIEW_OWN], 'update' => [Permissions::FILE_EDIT_ALL, Permissions::FILE_EDIT_OWN], 'delete' => [Permissions::FILE_DELETE_ALL, Permissions::FILE_DELETE_OWN], 'forceDelete' => [Permissions::FILE_FORCE_DELETE_ALL, Permissions::FILE_FORCE_DELETE_OWN]] as $ability => [$all, $own]) {
            foreach ([$user->id, $user->id + 1, null] as $ownerId) {
                $file = new File;
                $file->setRelation('quote', $ownerId === null ? null : Quote::factory()->make(['user_id' => $ownerId]));

                $this->assertSame($permission === $all || ($permission === $own && $ownerId === $user->id), $user->can($ability, $file));
            }
        }
    }

    public function test_guests_cannot_access_files(): void
    {
        foreach (['viewAny', 'create'] as $ability) {
            $this->assertFalse(Gate::allows($ability, File::class));
        }

        foreach (['view', 'update', 'delete', 'forceDelete'] as $ability) {
            $this->assertFalse(Gate::allows($ability, new File));
        }
    }
}
