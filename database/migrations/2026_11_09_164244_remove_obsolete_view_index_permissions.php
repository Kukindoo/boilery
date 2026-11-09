<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->where('guard_name', 'web')->whereIn('name', [
            'View index quotes',
            'View index works',
            'View index assembly actions',
            'View index users',
            'View index spare parts',
            'Print all works',
            'Print all quotes',
        ])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        throw new LogicException('Deleted permission assignments cannot be restored.');
    }
};
