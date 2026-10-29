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
        $all = Permission::findOrCreate(Permissions::FILE_CREATE_ALL->label(), 'web');
        $own = Permission::findOrCreate(Permissions::FILE_CREATE_OWN->label(), 'web');

        Role::findById(Roles::ADMIN->value, 'web')->givePermissionTo($all);
        Role::findById(Roles::STAKEHOLDER->value, 'web')->givePermissionTo($all);
        Role::findById(Roles::CUSTOMER->value, 'web')->givePermissionTo($own);
    }
};
