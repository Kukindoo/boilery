<?php

namespace Database\Seeders;

use App\Models\SparePart;
use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;

class SparePartSeeder extends Seeder
{
    /**
     * @throws QueryException
     */
    public function run(): void
    {
        SparePart::factory()->count(10)->create();
    }
}
