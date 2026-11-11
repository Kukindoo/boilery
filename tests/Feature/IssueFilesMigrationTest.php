<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Issue;
use App\Models\Quote;
use App\Models\Work;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IssueFilesMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh', ['--no-interaction' => true])->assertExitCode(0);
        $this->beforeApplicationDestroyed(function (): void {
            $this->artisan('db:wipe', ['--no-interaction' => true])->assertExitCode(0);
            RefreshDatabaseState::$migrated = false;
        });
    }

    public function test_moves_legacy_attachments_to_issues_without_duplicates_or_losing_archived_files(): void
    {
        Schema::table('works', function (Blueprint $table): void {
            $table->foreignId('quote_id')->nullable()->unique()->constrained();
        });
        $quote = Quote::factory()->trashed()->create();
        $work = Work::factory()->for($quote->issue)->trashed()->create();
        $otherWork = Work::factory()->create();
        $file = File::factory()->create();
        foreach ([$quote, $work, $otherWork] as $parent) {
            DB::table('fileables')->insert([
                'file_id' => $file->id,
                'fileable_type' => $parent->getMorphClass(),
                'fileable_id' => $parent->id,
                'created_at' => '2026-11-01 12:00:00',
                'updated_at' => '2026-11-02 12:00:00',
            ]);
        }
        $migration = require database_path('migrations/2026_11_11_125021_move_quote_and_work_files_to_issues_and_remove_work_quote_id.php');

        $migration->up();

        $this->assertFalse(Schema::hasColumn('works', 'quote_id'));
        $this->assertTrue($quote->issue->files()->sole()->is($file));
        $this->assertTrue($otherWork->issue->files()->sole()->is($file));
        $this->assertDatabaseCount('files', 1);
        $this->assertDatabaseCount('fileables', 2);
        $this->assertDatabaseHas('fileables', [
            'file_id' => $file->id,
            'fileable_type' => (new Issue)->getMorphClass(),
            'fileable_id' => $quote->issue_id,
            'created_at' => '2026-11-01 12:00:00',
            'updated_at' => '2026-11-02 12:00:00',
        ]);
    }
}
