<?php

namespace Tests\Feature\Files;

use App\Actions\Files\ChangeFileType;
use App\Actions\Files\UploadFile;
use App\Enums\FileTypes;
use App\Enums\Permissions;
use App\Enums\Roles;
use App\Livewire\Files\FilePreviewCard;
use App\Livewire\Files\SingleFileUpload;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

class FileTypeAuthorizationTest extends FeatureTestCase
{
    /** @return array<string, array{Roles, FileTypes, string}> */
    public static function uploads(): array
    {
        $cases = [];
        foreach (Roles::cases() as $role) {
            foreach ([FileTypes::RECEIPT, FileTypes::QUOTE_SUMMARY, FileTypes::SERVICE_PROTOCOL] as $type) {
                foreach (['quote', 'work'] as $parent) {
                    $cases[$role->name . ' ' . $type->value . ' ' . $parent] = [$role, $type, $parent];
                }
            }
        }

        return $cases;
    }

    /** @return array<string, array{FileTypes}> */
    public static function reservedTypes(): array
    {
        return [
            'quote summary' => [FileTypes::QUOTE_SUMMARY],
            'service protocol' => [FileTypes::SERVICE_PROTOCOL],
        ];
    }

    /** @return array<string, array{?Roles, FileTypes}> */
    public static function unprivilegedChanges(): array
    {
        $cases = [];
        foreach ([null, Roles::CUSTOMER] as $role) {
            foreach ([FileTypes::RECEIPT, FileTypes::QUOTE_SUMMARY, FileTypes::SERVICE_PROTOCOL] as $type) {
                $cases[($role?->name ?? 'no role') . ' ' . $type->value] = [$role, $type];
            }
        }

        return $cases;
    }

    #[DataProvider('uploads')]
    public function test_upload_options_and_server_authorization_follow_the_role(Roles $role, FileTypes $type, string $parentType): void
    {
        Storage::fake();
        $actor = User::factory()->create()->assignRole($role);
        $actor->givePermissionTo([
            Permissions::QUOTE_VIEW_OWN, Permissions::QUOTE_EDIT_OWN,
            Permissions::WORK_VIEW_OWN, Permissions::WORK_EDIT_OWN, Permissions::FILE_CREATE_OWN,
        ]);
        $parent = $parentType === 'quote'
            ? Quote::factory()->for($actor)->create()
            : Work::factory()->for($actor)->create();
        $component = Livewire::actingAs($actor)->test(SingleFileUpload::class, ['model' => $parent]);
        $denied = $role === Roles::CUSTOMER && $type !== FileTypes::RECEIPT;

        if ($denied) {
            $component->assertDontSee($type->label());
        } else {
            $component->assertSee($type->label());
        }

        $component->set('form.file', UploadedFile::fake()->image('document.jpg'))
            ->set('form.fileType', $type->value)->call('submit');

        if ($denied) {
            $component->assertForbidden();
            $this->assertDatabaseCount('files', 0);
            $this->assertDatabaseCount('fileables', 0);
            Storage::disk()->assertDirectoryEmpty(config('app.files.attachments_save_directory'));
        } else {
            $component->assertHasNoErrors()->assertRedirect(route($parentType === 'quote' ? 'quotes.show' : 'works.show', $parent));
            $file = $parent->files()->sole();
            $this->assertSame($type, $file->file_type);
            Storage::disk()->assertExists($file->path);
        }
    }

    #[DataProvider('reservedTypes')]
    public function test_public_quote_upload_cannot_bypass_reserved_type_authorization(FileTypes $type): void
    {
        Storage::fake();
        $quote = Quote::factory()->for(User::factory())->create();
        $this->expectException(AuthorizationException::class);

        try {
            app(UploadFile::class)->forPublicQuote(UploadedFile::fake()->image('document.jpg'), $quote, $type);
        } finally {
            $this->assertDatabaseCount('files', 0);
            $this->assertDatabaseCount('fileables', 0);
            Storage::disk()->assertEmpty();
        }
    }

    #[DataProvider('unprivilegedChanges')]
    public function test_file_edit_all_permission_does_not_allow_unprivileged_type_changes(?Roles $role, FileTypes $type): void
    {
        $actor = User::factory()->create();
        if ($role !== null) {
            $actor->assignRole($role);
        }
        $actor->givePermissionTo([Permissions::FILE_EDIT_ALL, Permissions::QUOTE_VIEW_OWN, Permissions::QUOTE_EDIT_OWN]);
        $quote = Quote::factory()->for($actor)->create();
        $file = $quote->files()->save(File::factory()->make(['file_type' => FileTypes::UNKNOWN]));

        Livewire::actingAs($actor)->test(FilePreviewCard::class, ['model' => $quote, 'file' => $file])
            ->assertDontSee('Změnit typ')
            ->call('changeFileType', $type->value)->assertForbidden();

        $this->assertSame(FileTypes::UNKNOWN, $file->fresh()->file_type);
        $this->assertDatabaseMissing('activity_log', ['event' => 'action_completed']);
        $this->expectException(AuthorizationException::class);
        app(ChangeFileType::class)->handle($file, $type);
    }

    public function test_type_change_rechecks_the_privileged_role_after_mount(): void
    {
        $actor = User::factory()->admin()->create();
        $actor->givePermissionTo([Permissions::QUOTE_VIEW_OWN, Permissions::QUOTE_EDIT_OWN, Permissions::FILE_EDIT_ALL]);
        $quote = Quote::factory()->for($actor)->create();
        $file = $quote->files()->save(File::factory()->make());
        $originalType = $file->file_type;
        $component = Livewire::actingAs($actor)->test(FilePreviewCard::class, ['model' => $quote, 'file' => $file]);
        $actor->removeRole(Roles::ADMIN);

        $component->call('changeFileType', FileTypes::WARRANTY->value)->assertForbidden();

        $this->assertSame($originalType, $file->fresh()->file_type);
    }

    public function test_privileged_role_still_requires_file_edit_permission(): void
    {
        $role = Role::findById(Roles::ADMIN->value, 'web');
        $role->syncPermissions([]);
        $actor = User::factory()->create()->assignRole($role);
        $file = File::factory()->create(['file_type' => FileTypes::UNKNOWN]);
        $this->actingAs($actor);
        $this->expectException(AuthorizationException::class);

        try {
            app(ChangeFileType::class)->handle($file, FileTypes::RECEIPT);
        } finally {
            $this->assertSame(FileTypes::UNKNOWN, $file->fresh()->file_type);
        }
    }
}
