<?php

use App\Enums\Permissions;
use App\Enums\Roles;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Role::findById(Roles::STAKEHOLDER->value, 'web')
            ->givePermissionTo(Permissions::ISSUE_VIEW_ALL->label());
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
