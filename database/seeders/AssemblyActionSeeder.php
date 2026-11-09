<?php

namespace Database\Seeders;

use App\Models\AssemblyAction;
use Illuminate\Database\Seeder;

class AssemblyActionSeeder extends Seeder
{
    public function run(): void
    {
        AssemblyAction::factory()->count(10)->create();
    }
}
