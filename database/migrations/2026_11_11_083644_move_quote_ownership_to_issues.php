<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

return new class extends Migration
{
    /** @throws Throwable */
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('quotes')->whereNull('issue_id')->orderBy('id')->chunkById(100, function ($quotes): void {
                foreach ($quotes as $quote) {
                    $issueId = DB::table('issues')->insertGetId([
                        'user_id' => $quote->user_id,
                        'created_at' => $quote->created_at ?? now(),
                        'updated_at' => $quote->updated_at ?? now(),
                    ]);
                    DB::table('quotes')->where('id', $quote->id)->update(['issue_id' => $issueId]);
                }
            });
        });

        foreach (Schema::getForeignKeys('quotes') as $foreignKey) {
            if (in_array($foreignKey['columns'], [['user_id'], ['issue_id']], true)) {
                Schema::table('quotes', function (Blueprint $table) use ($foreignKey): void {
                    $table->dropForeign($foreignKey['columns'])->index($foreignKey['name']);
                });
            }
        }
        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropColumn('user_id');
            $table->unsignedBigInteger('issue_id')->nullable(false)->change();
            $table->foreign('issue_id')->references('id')->on('issues')->restrictOnDelete();
        });
    }
};
