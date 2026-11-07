<?php

namespace Database\Factories;

use App\Enums\FileTypes;
use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<File> */
class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        return [
            'original_name' => 'receipt.pdf',
            'label' => 'receipt.pdf',
            'file_type' => FileTypes::RECEIPT,
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'size' => 16,
            'path' => 'files/' . fake()->uuid() . '.pdf',
        ];
    }
}
