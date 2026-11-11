<?php

namespace Tests\Integration\Actions\Files;

use App\Actions\Files\ChangeFileType;
use App\Enums\FileTypes;
use App\Enums\Permissions;
use App\Livewire\Files\FilePreviewCard;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ChangeFileTypeLockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Committed fixtures are necessary to distinguish action locks from
        // locks held by RefreshDatabase's wrapping transaction.
        $this->artisan('migrate:fresh')->assertExitCode(0);
        $this->beforeApplicationDestroyed(function (): void {
            $this->artisan('db:wipe')->assertExitCode(0);
            RefreshDatabaseState::$migrated = false;
        });
    }

    public static function contexts(): array
    {
        return ['quote component' => ['quote'], 'work component' => ['work'], 'generic action' => ['none']];
    }

    #[DataProvider('contexts')]
    public function test_resources_remain_locked_during_authorization_and_are_released_after_commit(string $context): void
    {
        $actor = User::factory()->admin()->create();
        $actor->givePermissionTo([
            Permissions::QUOTE_VIEW_ALL, Permissions::QUOTE_EDIT_ALL,
            Permissions::WORK_VIEW_ALL, Permissions::WORK_EDIT_ALL, Permissions::FILE_EDIT_ALL,
        ]);
        $this->actingAs($actor);
        $parent = match ($context) {
            'quote' => Quote::factory()->forCustomer($actor)->create(),
            'work' => Work::factory()->forCustomer($actor)->create(),
            default => null,
        };
        $file = File::create([
            'path' => 'receipt.pdf', 'label' => 'receipt.pdf', 'original_name' => 'receipt.pdf',
            'extension' => 'pdf', 'mime_type' => 'application/pdf', 'size' => 13,
            'file_type' => FileTypes::RECEIPT,
        ]);
        $parent?->files()->attach($file->id);
        $component = match ($context) {
            'quote' => Livewire::test(FilePreviewCard::class, ['model' => $parent, 'file' => $file]),
            'work' => Livewire::test(FilePreviewCard::class, ['model' => $parent, 'file' => $file]),
            default => null,
        };
        $resources = [['files', $file->id, ['file_type' => FileTypes::BOILER_LABEL->value]]];
        if ($parent !== null) {
            array_unshift($resources, [$parent->getTable(), $parent->id, ($parent instanceof Quote ? ['message' => 'Concurrent edit'] : ['notes' => 'Concurrent edit'])]);
        }
        $contender = DB::build([...DB::connection()->getConfig(), 'name' => 'file-type-lock-contender']);
        $this->assertNotSame(DB::connection()->getPdo(), $contender->getPdo());
        $contender->statement('SET SESSION innodb_lock_wait_timeout = 1');
        $checked = false;
        Gate::before(function (User $user, string $ability, array $arguments) use ($resources, $contender, &$checked): void {
            if ($checked || $ability !== 'changeType' || ! (($arguments[0] ?? null) instanceof File)) {
                return;
            }

            $checked = true;
            foreach ($resources as [$table, $id, $changes]) {
                try {
                    $contender->table($table)->where('id', $id)->update($changes);
                    $this->fail("The {$table} row must be locked before file authorization.");
                } catch (QueryException $exception) {
                    $this->assertSame(1205, $exception->errorInfo[1]);
                }
            }
        });

        try {
            if ($component !== null) {
                $component->call('changeFileType', FileTypes::WARRANTY->value)->assertHasNoErrors();
            } else {
                app(ChangeFileType::class)->handle($file, FileTypes::WARRANTY);
            }

            $this->assertTrue($checked);
            $this->assertSame(FileTypes::WARRANTY, $file->fresh()->file_type);
            foreach ($resources as [$table, $id, $changes]) {
                $this->assertSame(1, $contender->table($table)->where('id', $id)->update($changes));
            }
        } finally {
            $contender->disconnect();
        }
    }
}
