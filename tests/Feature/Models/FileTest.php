<?php

namespace Tests\Feature\Models;

use App\Enums\FileTypes;
use App\Models\Quote;
use App\Models\Work;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class FileTest extends FeatureTestCase
{
    /** @return array<string, array{class-string<Quote>|class-string<Work>, string}> */
    public static function parents(): array
    {
        return [
            'quote' => [Quote::class, 'quote'],
            'work' => [Work::class, 'work'],
        ];
    }

    /** @param class-string<Quote>|class-string<Work> $model */
    #[DataProvider('parents')]
    public function test_files_can_belong_to_quotes_or_standalone_works(string $model, string $relationship): void
    {
        $parent = $model::factory()->create();
        $otherParent = $model::factory()->create();

        $file = $parent->files()->create([
            'original_name' => 'receipt.jpg',
            'file_type' => FileTypes::RECEIPT,
            'extension' => 'jpg',
            'size' => 16,
            'mime_type' => 'image/jpeg',
            'path' => 'files/receipt.jpg',
            'label' => 'Receipt',
        ])->fresh();

        $this->assertTrue($file->{$relationship}->is($parent));
        $this->assertTrue($parent->files()->sole()->is($file));
        $this->assertTrue($otherParent->files()->doesntExist());
        $this->assertNull($file->{$relationship === 'quote' ? 'work' : 'quote'});
        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            $relationship . '_id' => $parent->id,
        ]);
    }
}
