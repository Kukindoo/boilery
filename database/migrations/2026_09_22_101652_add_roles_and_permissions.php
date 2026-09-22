<?php

use App\Enums\Permissions;
use App\Enums\Roles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Model::unguard();

        collect(Roles::cases())->map(function ($role) {
            Role::create([
                'id' => $role->value,
                'name' => $role->label(),
            ]);
        });

        collect(Permissions::cases())->map(function ($permission) {
            Permission::create([
                'id' => $permission->value,
                'name' => $permission->label(),
            ]);
        });

        $stakeholderRole = Role::findById(Roles::STAKEHOLDER->value);
        $stakeholderRole->givePermissionTo([Permissions::QUOTE_VIEW_ALL,
            Permissions::QUOTE_EDIT_ALL,
            Permissions::QUOTE_VIEW_INDEX,
            Permissions::QUOTE_PRINT_ALL]);

        $adminRole = Role::findById(Roles::ADMIN->value);
        $adminRole->givePermissionTo([Permissions::QUOTE_VIEW_ALL,
            Permissions::QUOTE_EDIT_ALL,
            Permissions::QUOTE_VIEW_INDEX,
            Permissions::QUOTE_PRINT_ALL]);

        $customRole = Role::findById(Roles::CUSTOMER->value);
        $customRole->givePermissionTo([Permissions::QUOTE_VIEW_OWN,
            Permissions::QUOTE_EDIT_OWN,
        ]);

        Model::reguard();
    }
};
