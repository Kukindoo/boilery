<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Quote;
use App\Models\Work;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Throwable;

class WorkIssueMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh', ['--no-interaction' => true]);
    }

    protected function tearDown(): void
    {
        $this->artisan('migrate:fresh', ['--no-interaction' => true]);
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    /** @throws Throwable */
    public function test_backfills_each_legacy_work_including_archived_works_and_links_its_quote(): void
    {
        Schema::table('works', function (Blueprint $table): void {
            $table->dropForeign(['issue_id']);
        });
        Schema::table('works', function (Blueprint $table): void {
            $table->unsignedBigInteger('issue_id')->nullable()->change();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('issue_id')->references('id')->on('issues')->nullOnDelete();
        });
        Schema::table('works', function (Blueprint $table): void {
            $table->foreignId('quote_id')->nullable()->constrained();
        });
        $quote = Quote::factory()->create();
        $work = Work::factory()->for($quote->issue)->create(['work_started_at' => '2026-11-01', 'work_completed_at' => '2026-11-02']);
        $archivedQuote = Quote::factory()->trashed()->create();
        $archived = Work::factory()->for($archivedQuote->issue)->trashed()->create();
        DB::table('works')->where('id', $work->id)->update(['quote_id' => $quote->id]);
        DB::table('works')->where('id', $archived->id)->update(['quote_id' => $archivedQuote->id]);
        $withoutQuote = Work::factory()->create();
        $owners = [];
        foreach ([$work, $archived, $withoutQuote] as $record) {
            $owners[$record->id] = $record->issue->user_id;
            DB::table('works')->where('id', $record->id)->update(['user_id' => $record->issue->user_id, 'issue_id' => null]);
        }
        $before = Issue::query()->count();
        $migration = require database_path('migrations/2026_11_09_220754_backfill_and_require_work_issue_id.php');

        $migration->up();

        $this->assertSame($before + 3, Issue::query()->count());
        $this->assertSame(3, DB::table('works')->distinct()->count('issue_id'));
        foreach ([$work, $archived, $withoutQuote] as $record) {
            $this->assertSame($owners[$record->id], $record->fresh()->issue->user_id);
        }
        $this->assertSame($work->fresh()->issue_id, $quote->fresh()->issue_id);
        $this->assertSame($archived->fresh()->issue_id, $archivedQuote->fresh()->issue_id);
        $this->assertSame('2026-11-01', $work->fresh()->issue->started_at->toDateString());
        $this->assertSame('2026-11-02', $work->fresh()->issue->finished_at->toDateString());
    }
}
