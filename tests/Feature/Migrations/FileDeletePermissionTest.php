<?php

namespace Tests\Feature\Migrations;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

class FileDeletePermissionTest extends FeatureTestCase
{
    public function test_grants_file_delete_all_to_admin_and_stakeholder_without_granting_it_to_customer(): void
    {
        Role::findById(Roles::ADMIN->value, 'web')->revokePermissionTo(Permissions::FILE_DELETE_ALL);
        Role::findById(Roles::STAKEHOLDER->value, 'web')->revokePermissionTo(Permissions::FILE_DELETE_ALL);
        $admin = User::factory()->create()->assignRole(Roles::ADMIN);
        $stakeholder = User::factory()->create()->assignRole(Roles::STAKEHOLDER);
        $customer = User::factory()->create()->assignRole(Roles::CUSTOMER);
        $migration = require database_path('migrations/2026_11_04_135001_grant_admin_and_stakeholder_file_delete_permission.php');

        $migration->up();
        $migration->up();

        $this->assertTrue($admin->can(Permissions::FILE_DELETE_ALL->label()));
        $this->assertTrue($stakeholder->can(Permissions::FILE_DELETE_ALL->label()));
        $this->assertFalse($customer->can(Permissions::FILE_DELETE_ALL->label()));
    }
}
