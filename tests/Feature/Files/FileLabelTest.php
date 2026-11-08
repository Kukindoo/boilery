<?php

namespace Tests\Feature\Files;

use App\Models\File;
use Tests\Feature\FeatureTestCase;

class FileLabelTest extends FeatureTestCase
{
    public function test_file_can_be_saved_without_a_label(): void
    {
        $attributes = File::factory()->raw();
        unset($attributes['label']);

        $file = File::query()->create($attributes);

        $this->assertNull($file->refresh()->label);
        $this->assertDatabaseHas('files', ['id' => $file->id, 'label' => null]);
    }

    public function test_existing_label_can_be_cleared(): void
    {
        $file = File::factory()->create(['label' => 'receipt.pdf']);

        $file->update(['label' => null]);

        $this->assertNull($file->refresh()->label);
    }
}
