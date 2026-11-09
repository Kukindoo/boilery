<?php

namespace Tests\Feature\Migrations;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

class QuoteGeneratePdfPermissionTest extends FeatureTestCase
{
    public function test_registers_the_general_pdf_permission_on_existing_installations(): void
    {
        Permission::findById(Permissions::PDF_GENERATE_ALL->value, 'web')->delete();
        $migration = require database_path('migrations/2026_11_04_132005_add_quote_generate_pdf_permission.php');

        $migration->up();

        $this->assertDatabaseHas('permissions', [
            'id' => Permissions::PDF_GENERATE_ALL->value,
            'name' => 'Generate PDF',
            'guard_name' => 'web',
        ]);
        $this->assertSame(1, Permission::query()->where('name', 'Generate PDF')->where('guard_name', 'web')->count());
        $this->assertDatabaseHas('permissions', ['id' => Permissions::QUOTE_VIEW_ALL->value]);
    }

    public function test_grants_the_existing_permission_to_admin_and_stakeholder_and_can_run_again(): void
    {
        Role::findById(Roles::ADMIN->value, 'web')->revokePermissionTo(Permissions::PDF_GENERATE_ALL);
        Role::findById(Roles::STAKEHOLDER->value, 'web')->revokePermissionTo(Permissions::PDF_GENERATE_ALL);
        $admin = User::factory()->create()->assignRole(Roles::ADMIN);
        $stakeholder = User::factory()->create()->assignRole(Roles::STAKEHOLDER);
        $customer = User::factory()->create()->assignRole(Roles::CUSTOMER);
        $permission = Permission::findById(Permissions::PDF_GENERATE_ALL->value, 'web');
        $migration = require database_path('migrations/2026_11_04_132005_add_quote_generate_pdf_permission.php');

        $migration->up();
        $migration->up();

        $this->assertModelExists($permission);
        $this->assertSame(1, Permission::query()->where('name', 'Generate PDF')->where('guard_name', 'web')->count());
        $this->assertTrue($admin->can(Permissions::PDF_GENERATE_ALL->label()));
        $this->assertTrue($stakeholder->can(Permissions::PDF_GENERATE_ALL->label()));
        $this->assertFalse($customer->can(Permissions::PDF_GENERATE_ALL->label()));
    }
}
