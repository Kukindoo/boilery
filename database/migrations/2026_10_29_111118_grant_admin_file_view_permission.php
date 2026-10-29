<?php

use App\Enums\Permissions;
use App\Enums\Roles;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permission = Permission::findOrCreate(Permissions::FILE_VIEW_ALL->label(), 'web');

        Role::findById(Roles::ADMIN->value)->givePermissionTo($permission);
        Role::findById(Roles::STAKEHOLDER->value)->givePermissionTo($permission);
    }
};
