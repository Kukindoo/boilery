<?php

namespace Tests\Feature\Works;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Livewire\Files\SingleFileUpload;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class WorkFileUploadTest extends FeatureTestCase
{
    public static function uploadContexts(): array
    {
        $cases = [];
        foreach (['quote', 'work'] as $origin) {
            foreach ([Roles::ADMIN, Roles::STAKEHOLDER, Roles::CUSTOMER] as $role) {
                $cases[$origin . ' ' . $role->name] = [$origin, $role];
            }
        }

        return $cases;
    }

    public static function deniedUploads(): array
    {
        return [
            'no work edit permission' => [[Permissions::WORK_VIEW_OWN, Permissions::FILE_CREATE_OWN], false],
            'no file create permission' => [[Permissions::WORK_VIEW_OWN, Permissions::WORK_EDIT_OWN], false],
            'own-file permission on another customer work' => [[Permissions::WORK_VIEW_ALL, Permissions::WORK_EDIT_ALL, Permissions::FILE_CREATE_OWN], true],
        ];
    }

    #[DataProvider('uploadContexts')]
    public function test_uploads_are_stored_once_and_visible_on_both_related_pages(string $origin, Roles $role): void
    {
        Storage::fake();
        $customer = User::factory()->customer()->create();
        $actor = $role === Roles::CUSTOMER ? $customer : User::factory()->create();
        $actor->assignRole($role);
        if ($role === Roles::STAKEHOLDER) {
            $actor->givePermissionTo([Permissions::WORK_VIEW_ALL, Permissions::WORK_EDIT_ALL]);
        }
        if ($role === Roles::CUSTOMER) {
            $actor->givePermissionTo([
                Permissions::QUOTE_VIEW_OWN, Permissions::QUOTE_EDIT_OWN,
                Permissions::WORK_VIEW_OWN, Permissions::WORK_EDIT_OWN,
                Permissions::FILE_CREATE_OWN, Permissions::FILE_VIEW_OWN,
            ]);
        }
        $quote = Quote::factory()->accepted()->for($customer)->create();
        $work = Work::factory()->for($customer)->for($quote)->create();
        $parent = $origin === 'quote' ? $quote : $work;

        Livewire::actingAs($actor)->test(SingleFileUpload::class, ['model' => $parent])
            ->set('form.file', UploadedFile::fake()->image('receipt.jpg'))
            ->call('submit')->assertHasNoErrors()
            ->assertRedirect(route($origin === 'quote' ? 'quotes.show' : 'works.show', $parent));

        $file = File::query()->sole();
        $this->assertTrue($quote->files()->sole()->is($file));
        $this->assertTrue($work->files()->sole()->is($file));
        $this->assertSame(2, $file->attachments()->count());
        $this->assertCount(1, Storage::allFiles(config('app.files.attachments_save_directory')));
        Storage::disk()->assertExists($file->path);
        $this->get(route('quotes.show', $quote))->assertOk()
            ->assertSee(route('files.preview', ['quote', $quote, $file]), false);
        $this->get(route('works.show', $work))->assertOk()
            ->assertSee(route('files.preview', ['work', $work, $file]), false);
    }

    public function test_standalone_work_has_an_upload_control_before_any_attachment_exists(): void
    {
        Storage::fake();
        $actor = User::factory()->admin()->create();
        $work = Work::factory()->create();
        $this->actingAs($actor)->get(route('works.show', $work))->assertOk()
            ->assertSeeLivewire(SingleFileUpload::class)->assertSee('Nahrát soubor');

        Livewire::actingAs($actor)->test(SingleFileUpload::class, ['model' => $work])
            ->set('form.file', UploadedFile::fake()->image('receipt.jpg'))
            ->call('submit')->assertHasNoErrors();

        $file = File::query()->sole();
        $this->assertTrue($file->isAttachedTo($work));
        $this->assertSame(1, $file->attachments()->count());
    }

    #[DataProvider('deniedUploads')]
    public function test_upload_requires_work_and_file_permissions(array $permissions, bool $anotherOwner): void
    {
        Storage::fake();
        $actor = User::factory()->create();
        $actor->givePermissionTo($permissions);
        $work = Work::factory()->for($anotherOwner ? User::factory() : $actor)->create();

        Livewire::actingAs($actor)->test(SingleFileUpload::class, ['model' => $work])
            ->set('form.file', UploadedFile::fake()->image('receipt.jpg'))
            ->call('submit')->assertForbidden();

        $this->assertDatabaseCount('files', 0);
        $this->assertDatabaseCount('fileables', 0);
        Storage::disk()->assertDirectoryEmpty('files/attachments');
    }

    public function test_work_upload_rechecks_permissions_after_mount(): void
    {
        Storage::fake();
        $actor = User::factory()->create();
        $actor->givePermissionTo([Permissions::WORK_VIEW_OWN, Permissions::WORK_EDIT_OWN, Permissions::FILE_CREATE_OWN]);
        $work = Work::factory()->for($actor)->create();
        $component = Livewire::actingAs($actor)->test(SingleFileUpload::class, ['model' => $work]);
        $actor->revokePermissionTo(Permissions::FILE_CREATE_OWN);

        $component->set('form.file', UploadedFile::fake()->image('receipt.jpg'))->call('submit')->assertForbidden();

        $this->assertDatabaseCount('files', 0);
    }

    public function test_work_upload_validates_the_required_file(): void
    {
        $actor = User::factory()->admin()->create();
        $work = Work::factory()->create();

        Livewire::actingAs($actor)->test(SingleFileUpload::class, ['model' => $work])
            ->call('submit')->assertHasErrors(['form.file' => 'required']);

        $this->assertDatabaseCount('files', 0);
    }
}
