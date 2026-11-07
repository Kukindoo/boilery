<?php

namespace Tests\Integration\Actions\Files;

use App\Actions\Files\DetachFile;
use App\Enums\Permissions;
use App\Jobs\Files\DeleteStoredFile;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DetachFileConcurrencyTest extends TestCase
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

    public function test_a_link_committed_after_the_transaction_snapshot_prevents_orphan_deletion(): void
    {
        Storage::fake();
        Queue::fake([DeleteStoredFile::class]);
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::QUOTE_VIEW_OWN, Permissions::FILE_DELETE_OWN]);
        $this->actingAs($user);
        $quote = Quote::factory()->for($user)->create();
        $other = Quote::factory()->for($user)->create();
        $file = $quote->files()->save(File::factory()->make());
        Storage::put($file->path, 'shared content');
        $writer = DB::build([...DB::connection()->getConfig(), 'name' => 'detach-concurrent-writer']);
        $linked = false;
        Gate::before(function (User $actor, string $ability, array $arguments) use ($quote, $other, $file, $writer, &$linked): void {
            if ($linked || $ability !== 'view' || ! (($arguments[0] ?? null) instanceof Quote) || $arguments[0]->id !== $quote->id) {
                return;
            }
            DB::table('fileables')->where('file_id', $file->id)->get();
            $writer->table('fileables')->insert([
                'file_id' => $file->id,
                'fileable_type' => $other->getMorphClass(),
                'fileable_id' => $other->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $linked = true;
        });

        try {
            app(DetachFile::class)->handle($quote, $file);

            $this->assertTrue($linked);
            $this->assertModelExists($file);
            $this->assertFalse($file->isAttachedTo($quote));
            $this->assertTrue($file->isAttachedTo($other));
            Storage::disk()->assertExists($file->path);
            Queue::assertNothingPushed();
        } finally {
            $writer->disconnect();
        }
    }
}
