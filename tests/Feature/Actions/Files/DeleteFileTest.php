<?php

namespace Tests\Feature\Actions\Files;

use App\Actions\Files\DeleteFile;
use App\Actions\Files\UploadQuoteFile;
use App\Enums\FileTypes;
use App\Enums\Permissions;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

class DeleteFileTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.default' => 'file-documents']);
    }

    /** @return array<string, array{Permissions, bool}> */
    public static function allowedDeletions(): array
    {
        return [
            'all permission on another customer file' => [Permissions::FILE_DELETE_ALL, false],
            'own permission on own file' => [Permissions::FILE_DELETE_OWN, true],
        ];
    }

    /** @return array<string, array{bool, ?Permissions, bool}> */
    public static function deniedDeletions(): array
    {
        return [
            'guest' => [false, null, false],
            'owner without permission' => [true, null, true],
            'own permission on another customer file' => [true, Permissions::FILE_DELETE_OWN, false],
        ];
    }

    #[DataProvider('allowedDeletions')]
    public function test_removes_only_the_authorized_file_and_its_database_record(Permissions $permission, bool $ownsQuote): void
    {
        Storage::fake('file-documents');
        $user = $this->operator($permission);
        $quote = Quote::factory()->for($ownsQuote ? $user : User::factory())->create();
        $file = $this->upload($quote);
        $otherFile = $this->upload($quote);
        $this->actingAs($user);

        app(DeleteFile::class)->handle($file);

        $this->assertModelMissing($file);
        Storage::disk()->assertMissing($file->path);
        $this->assertModelExists($otherFile);
        Storage::disk()->assertExists($otherFile->path);
    }

    #[DataProvider('deniedDeletions')]
    public function test_forbidden_deletion_preserves_the_stored_file_and_record(bool $authenticated, ?Permissions $permission, bool $ownsQuote): void
    {
        Storage::fake('file-documents');
        $user = $this->operator($permission);
        $quote = Quote::factory()->for($ownsQuote ? $user : User::factory())->create();
        $file = $this->upload($quote);

        if ($authenticated) {
            $this->actingAs($user);
        }

        $this->expectException(AuthorizationException::class);

        try {
            app(DeleteFile::class)->handle($file);
        } finally {
            $this->assertModelExists($file);
            Storage::disk()->assertExists($file->path);
        }
    }

    public function test_storage_failure_preserves_the_database_record(): void
    {
        Storage::fake('file-documents');
        $file = $this->upload(Quote::factory()->create());
        $this->actingAs($this->operator(Permissions::FILE_DELETE_ALL));
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('delete')->once()->with($file->path)->andReturnFalse();
        Storage::shouldReceive('disk')->once()->withNoArgs()->andReturn($disk);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not delete the stored file.');

        try {
            app(DeleteFile::class)->handle($file);
        } finally {
            $this->assertModelExists($file);
        }
    }

    public function test_removes_the_record_when_the_stored_file_is_already_missing(): void
    {
        Storage::fake('file-documents');
        $file = $this->upload(Quote::factory()->create());
        Storage::disk()->delete($file->path);
        $this->actingAs($this->operator(Permissions::FILE_DELETE_ALL));

        app(DeleteFile::class)->handle($file);

        $this->assertModelMissing($file);
        Storage::disk()->assertMissing($file->path);
    }

    private function upload(Quote $quote): File
    {
        return app(UploadQuoteFile::class)->handle(
            UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'),
            $quote,
            FileTypes::RECEIPT,
        );
    }

    private function operator(?Permissions $permission): User
    {
        $user = User::factory()->create();

        if ($permission !== null) {
            $role = Role::create(['name' => 'delete-file-test', 'guard_name' => 'web']);
            $role->givePermissionTo($permission);
            $user->assignRole($role);
        }

        return $user;
    }
}
