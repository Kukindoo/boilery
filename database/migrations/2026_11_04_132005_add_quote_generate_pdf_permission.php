<?php

use App\Enums\Permissions;
use App\Enums\Roles;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permissions::GENERATE_PDF;

        $record = Permission::unguarded(fn () => Permission::firstOrCreate([
            'id' => $permission->value,
        ], [
            'name' => $permission->label(),
            'guard_name' => 'web',
        ]));

        Role::findById(Roles::ADMIN->value, 'web')->givePermissionTo($record);
        Role::findById(Roles::STAKEHOLDER->value, 'web')->givePermissionTo($record);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
