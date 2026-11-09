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
            DB::table('works')->whereNull('issue_id')->orderBy('id')->chunkById(100, function ($works): void {
                foreach ($works as $work) {
                    $issueId = DB::table('issues')->insertGetId([
                        'user_id' => $work->user_id,
                        'started_at' => $work->work_started_at,
                        'finished_at' => $work->work_completed_at,
                        'created_at' => $work->created_at ?? now(),
                        'updated_at' => $work->updated_at ?? now(),
                    ]);
                    DB::table('works')->where('id', $work->id)->update(['issue_id' => $issueId]);
                    if ($work->quote_id !== null) {
                        DB::table('quotes')->where('id', $work->quote_id)->update(['issue_id' => $issueId]);
                    }
                }
            });
        });

        Schema::table('works', function (Blueprint $table): void {
            $table->dropForeign(['issue_id']);
        });
        Schema::table('works', function (Blueprint $table): void {
            $table->unsignedBigInteger('issue_id')->nullable(false)->change();
            $table->foreign('issue_id')->references('id')->on('issues')->restrictOnDelete();
        });
    }
};
