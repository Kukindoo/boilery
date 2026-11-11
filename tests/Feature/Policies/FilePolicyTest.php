<?php

namespace Tests\Feature\Policies;

use App\Enums\Permissions;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class FilePolicyTest extends FeatureTestCase
{
    public static function permissions(): array
    {
        $cases = ['none' => [null]];
        foreach (Permissions::cases() as $permission) {
            if (str_starts_with($permission->name, 'FILE_')) {
                $cases[mb_strtolower($permission->name)] = [$permission];
            }
        }

        return $cases;
    }

    #[DataProvider('permissions')]
    public function test_file_permissions_respect_all_supported_parent_types(?Permissions $permission): void
    {
        $user = User::factory()->create();
        if ($permission !== null) {
            $user->givePermissionTo($permission);
        }
        $this->assertSame($permission === Permissions::FILE_VIEW_ALL, $user->can('viewAny', File::class));
        $this->assertSame(in_array($permission, [Permissions::FILE_CREATE_ALL, Permissions::FILE_CREATE_OWN], true), $user->can('create', File::class));

        foreach ([Quote::class, Work::class, User::class] as $type) {
            foreach ([true, false, null] as $owned) {
                $file = File::factory()->create();
                if ($owned !== null) {
                    $owner = $owned ? $user : User::factory()->create();
                    $parent = match ($type) {
                        User::class => $owner,
                        Quote::class => Quote::factory()->forCustomer($owner)->create(),
                        Work::class => Work::factory()->forCustomer($owner)->create(),
                    };
                    $parent->files()->attach($file->id);
                }
                foreach ([
                    'view' => [Permissions::FILE_VIEW_ALL, Permissions::FILE_VIEW_OWN],
                    'update' => [Permissions::FILE_EDIT_ALL, Permissions::FILE_EDIT_OWN],
                    'delete' => [Permissions::FILE_DELETE_ALL, Permissions::FILE_DELETE_OWN],
                    'forceDelete' => [Permissions::FILE_FORCE_DELETE_ALL, Permissions::FILE_FORCE_DELETE_OWN],
                ] as $ability => [$all, $own]) {
                    $this->assertSame($permission === $all || ($permission === $own && $owned === true), $user->can($ability, $file));
                }
            }
        }
    }

    public function test_shared_file_can_be_read_but_not_globally_changed_by_one_of_several_owners(): void
    {
        $owner = User::factory()->create();
        $owner->givePermissionTo([Permissions::FILE_VIEW_OWN, Permissions::FILE_EDIT_OWN, Permissions::FILE_DELETE_OWN, Permissions::FILE_FORCE_DELETE_OWN]);
        $quote = Quote::factory()->forCustomer($owner)->create();
        $otherQuote = Quote::factory()->forCustomer(User::factory())->create();
        $file = $quote->files()->save(File::factory()->make());
        $otherQuote->files()->attach($file->id);

        $this->assertTrue($owner->can('view', $file));
        foreach (['update', 'delete', 'forceDelete'] as $ability) {
            $this->assertFalse($owner->can($ability, $file));
        }
        $this->assertTrue($owner->can('detach', [$file, $quote]));
        $this->assertFalse($owner->can('detach', [$file, $otherQuote]));
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
