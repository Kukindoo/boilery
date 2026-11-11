<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Throwable;

class QuoteOwnershipMigrationTest extends TestCase
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
    public function test_backfills_missing_issues_preserves_existing_links_and_removes_direct_ownership(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropForeign(['issue_id']);
        });
        Schema::table('quotes', function (Blueprint $table): void {
            $table->unsignedBigInteger('issue_id')->nullable()->change();
            $table->foreign('issue_id')->references('id')->on('issues')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users');
        });
        $customer = User::factory()->create();
        $existing = Quote::factory()->forCustomer($customer)->create();
        $legacy = Quote::factory()->forCustomer($customer)->create();
        $archived = Quote::factory()->forCustomer($customer)->trashed()->create();
        DB::table('quotes')->update(['user_id' => $customer->id]);
        DB::table('quotes')->whereIn('id', [$legacy->id, $archived->id])->update(['issue_id' => null]);
        $issuesBefore = Issue::count();
        $migration = require database_path('migrations/2026_11_11_083644_move_quote_ownership_to_issues.php');

        $migration->up();

        $this->assertSame($issuesBefore + 2, Issue::count());
        $this->assertSame($existing->issue_id, $existing->fresh()->issue_id);
        $this->assertSame($customer->id, $legacy->fresh()->issue->user_id);
        $this->assertSame($customer->id, $archived->fresh()->issue->user_id);
        $this->assertFalse(Schema::hasColumn('quotes', 'user_id'));
        $this->assertSame(0, DB::table('quotes')->whereNull('issue_id')->count());
    }
}
