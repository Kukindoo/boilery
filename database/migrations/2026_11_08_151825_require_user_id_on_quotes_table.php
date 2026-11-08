<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('quotes')->whereNull('user_id')->exists()) {
            throw new RuntimeException('Assign a user to every quote before requiring quotes.user_id.');
        }

        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
