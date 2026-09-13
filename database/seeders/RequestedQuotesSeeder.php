<?php

namespace Database\Seeders;

use App\Models\RequestedQuotes;
use Illuminate\Database\Seeder;

class RequestedQuotesSeeder extends Seeder
{
    public function run(): void
    {
        RequestedQuotes::factory()->count(5)->create();
    }
}
