<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('quote_id')->nullable()->constrained();
            $table->string('order_id')->unique();
            $table->string('type_of_product')->nullable();
            $table->string('type_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->boolean('warranty_accepted')->default(false);
            $table->string('repair_in_warranty');
            $table->date('day_of_purchase')->nullable();
            $table->string('building_type')->nullable();

            $table->string('reported_problem')->nullable();
            $table->date('work_accepted_at')->nullable();
            $table->date('work_completed_at')->nullable();
            $table->text('work_performed_description')->nullable();

            $table->string('transport_from')->nullable();
            $table->string('transport_to')->nullable();
            $table->integer('transport_rate_in_czk')->nullable();
            $table->decimal('transport_distance_in_km')->nullable();

            $table->text('note')->nullable();
            $table->text('mechanic_name')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('works');
    }
};
