<?php

namespace Database\Seeders;

use App\Models\Quote;
use Illuminate\Database\Seeder;

class RequestedQuotesSeeder extends Seeder
{
    public function run(): void
    {
        Quote::factory()->count(5)->create();
    }
}
