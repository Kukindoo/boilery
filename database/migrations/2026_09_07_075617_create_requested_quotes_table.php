<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requested_quotes', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->text('address')->nullable();
            $table->text('message');
            $table->boolean('under_warranty')->default(false);
            $table->string('boiler_manufacturer')->nullable();
            $table->string('boiler_serial_number')->nullable();
            $table->string('boiler_type')->nullable();
            $table->string('label_file_path')->nullable();
            $table->string('warranty_file_path')->nullable();
            $table->string('receipt_file_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requested_quotes');
    }
};
