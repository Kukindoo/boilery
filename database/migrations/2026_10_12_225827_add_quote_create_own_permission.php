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

        Permission::firstOrCreate([
            'id' => Permissions::QUOTE_CREATE_OWN->value,
        ], [
            'name' => Permissions::QUOTE_CREATE_OWN->label(),
            'guard_name' => 'web',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
