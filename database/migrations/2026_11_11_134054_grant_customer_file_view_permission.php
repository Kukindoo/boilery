<?php

use App\Enums\Permissions;
use App\Enums\Roles;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::findOrCreate(Permissions::FILE_VIEW_OWN->label(), 'web');

        Role::findById(Roles::CUSTOMER->value, 'web')->givePermissionTo($permission);
    }
};
