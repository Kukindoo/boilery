<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');
            $table->foreignId('quote_id')->constrained('requested_quotes', 'id');
            $table->string('file_type');
            $table->string('extension');
            $table->integer('size');
            $table->string('mime_type');
            $table->string('path');
            $table->string('label');
            $table->timestamps();
        });

        Schema::table('requested_quotes', function (Blueprint $table) {
            $table->dropColumn('label_file_path');
            $table->dropColumn('warranty_file_path');
            $table->dropColumn('receipt_file_path');
        });
    }
};
