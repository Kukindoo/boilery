<?php

namespace Tests\Feature\Migrations;

use App\Models\File;
use App\Models\Quote;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PolymorphicFilesMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh')->assertExitCode(0);
        $this->beforeApplicationDestroyed(function (): void {
            $this->artisan('db:wipe')->assertExitCode(0);
            RefreshDatabaseState::$migrated = false;
        });
    }

    public function test_migration_preserves_legacy_links_metadata_and_soft_deleted_parents(): void
    {
        $migration = require database_path('migrations/2026_11_06_160000_create_fileables_table.php');
        Schema::drop('fileables');
        Schema::table('files', function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->foreignId('quote_id')->nullable()->constrained('quotes');
            $table->foreignId('work_id')->nullable()->constrained('works');
        });
        $quote = Quote::factory()->create();
        $work = Work::factory()->create();
        $quote->delete();
        $attributes = File::factory()->make()->getAttributes();
        $id = DB::table('files')->insertGetId([
            ...$attributes, 'quote_id' => $quote->id, 'work_id' => $work->id,
            'created_at' => '2026-11-01 12:00:00', 'updated_at' => '2026-11-02 12:00:00',
        ]);
        $unattached = DB::table('files')->insertGetId([
            ...$attributes, 'quote_id' => null, 'work_id' => null,
            'created_at' => '2026-11-01 12:00:00', 'updated_at' => '2026-11-02 12:00:00',
        ]);

        $migration->up();

        $this->assertFalse(Schema::hasColumn('files', 'quote_id'));
        $this->assertFalse(Schema::hasColumn('files', 'work_id'));
        $this->assertDatabaseHas('files', ['id' => $id, ...$attributes]);
        $file = File::findOrFail($id);
        $this->assertDatabaseHas('fileables', ['file_id' => $file->id, 'fileable_type' => $quote->getMorphClass(), 'fileable_id' => $quote->id]);
        $this->assertDatabaseHas('fileables', ['file_id' => $file->id, 'fileable_type' => $work->getMorphClass(), 'fileable_id' => $work->id]);
        $this->assertDatabaseHas('fileables', [
            'file_id' => $id, 'fileable_type' => $quote->getMorphClass(), 'fileable_id' => $quote->id,
            'created_at' => '2026-11-01 12:00:00', 'updated_at' => '2026-11-02 12:00:00',
        ]);
        $this->assertSame(0, File::findOrFail($unattached)->attachments()->count());
        $this->assertDatabaseCount('files', 2);
    }

    public function test_database_rejects_duplicate_attachments(): void
    {
        $file = File::factory()->create();
        $quote = Quote::factory()->create();
        $quote->files()->attach($file->id);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        $quote->files()->attach($file->id);
    }
}
