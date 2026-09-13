<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requested_quotes', function (Blueprint $table) {
            $table->string('status')->default('new')->after('id');
        });
    }
};
