<?php

use App\Enums\Permissions;
use App\Enums\Roles;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin = Role::findById(Roles::ADMIN->value, 'web');
        foreach ($this->permissions() as $permission) {
            $record = Permission::findOrCreate($permission->label(), 'web');
            $admin->givePermissionTo($record);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach ($this->permissions() as $permission) {
            Permission::query()->where('guard_name', 'web')->where('name', $permission->label())->delete();
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @return list<Permissions> */
    private function permissions(): array
    {
        return [
            Permissions::ROLE_VIEW_ALL,
            Permissions::ROLE_EDIT_ALL,
            Permissions::PERMISSION_VIEW_ALL,
        ];
    }
};
