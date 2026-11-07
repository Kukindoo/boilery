<?php

namespace Tests\Feature\Models;

use App\Contracts\OwnsFiles;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class FileTest extends FeatureTestCase
{
    public static function parents(): array
    {
        return ['quote' => [Quote::class, 'quotes'], 'work' => [Work::class, 'works'], 'user' => [User::class, 'users']];
    }

    #[DataProvider('parents')]
    public function test_models_can_share_files_and_have_multiple_files(string $model, string $relationship): void
    {
        $parent = $model::factory()->create();
        $other = $model::factory()->create();
        $file = File::factory()->make();
        $parent->files()->save($file);
        $parent->files()->attach(File::factory()->create()->id);
        $other->files()->syncWithoutDetaching([$file->id]);
        $other->files()->syncWithoutDetaching([$file->id]);

        $this->assertCount(2, $parent->files);
        $this->assertCount(2, $file->{$relationship});
        $this->assertTrue($file->isAttachedTo($parent));
        $this->assertTrue($file->isAttachedTo($other));
        $this->assertSame(2, $file->attachments()->count());
        $this->assertDatabaseCount('files', 2);
    }

    public function test_one_file_can_belong_to_several_different_model_types(): void
    {
        $file = File::factory()->create();
        $parents = [Quote::factory()->create(), Work::factory()->create(), User::factory()->create()];
        foreach ($parents as $parent) {
            $parent->files()->attach($file->id);
            $this->assertTrue($file->isAttachedTo($parent));
        }

        $this->assertSame(3, $file->attachments()->with('fileable')->get()->count());
        $this->assertDatabaseCount('files', 1);
    }

    public function test_an_additional_model_can_use_files_without_registering_an_inverse_relationship(): void
    {
        $user = User::factory()->create();
        $parent = AdditionalFileOwner::findOrFail($user->id);
        $file = $parent->files()->save(File::factory()->make());

        $this->assertTrue($file->isAttachedTo($parent));
        $this->assertTrue($file->attachments()->with('fileable')->sole()->fileable->is($parent));
        $this->assertTrue($file->isOwnedBy($user));
        $this->assertTrue($file->isExclusivelyOwnedBy($user));
    }

    public function test_archiving_preserves_links_but_force_deleting_a_parent_only_detaches_it(): void
    {
        $quote = Quote::factory()->create();
        $work = Work::factory()->create();
        $file = $quote->files()->save(File::factory()->make());
        $work->files()->attach($file->id);
        $quote->delete();
        $this->assertTrue($file->isAttachedTo($quote));
        $quote->restore();
        $this->assertTrue($quote->files()->sole()->is($file));
        $quote->forceDelete();

        $this->assertFalse($file->isAttachedTo($quote));
        $this->assertTrue($file->isAttachedTo($work));
        $this->assertModelExists($file);
        $file->delete();
        $this->assertDatabaseMissing('fileables', ['file_id' => $file->id]);
    }
}

class AdditionalFileOwner extends Model implements OwnsFiles
{
    use HasFiles;

    protected $table = 'users';

    public function isFileOwner(User $user): bool
    {
        return $this->getKey() === $user->id;
    }
}
