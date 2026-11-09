<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assembly_actions', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->default('');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('assembly_action_work', function (Blueprint $table): void {
            $table->foreignId('work_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assembly_action_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price_in_cents')->default(0);
            $table->timestamps();
            $table->primary(['work_id', 'assembly_action_id']);
        });
    }
};
