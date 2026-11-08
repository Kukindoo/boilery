<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spare_part_work', function (Blueprint $table): void {
            $table->unsignedSmallInteger('amount')->default(1);
        });
    }
};
