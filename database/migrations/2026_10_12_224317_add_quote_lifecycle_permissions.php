<?php

use App\Enums\Permissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            Permissions::QUOTE_DELETE_ALL,
            Permissions::QUOTE_DELETE_OWN,
            Permissions::QUOTE_CREATE_ALL,
            Permissions::QUOTE_RESTORE_ALL,
            Permissions::QUOTE_RESTORE_OWN,
            Permissions::QUOTE_FORCE_DELETE_ALL,
            Permissions::QUOTE_FORCE_DELETE_OWN,
        ] as $permission) {
            Permission::firstOrCreate([
                'id' => $permission->value,
            ], [
                'name' => $permission->label(),
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
