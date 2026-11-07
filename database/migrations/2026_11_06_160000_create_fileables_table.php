<?php

use App\Models\Quote;
use App\Models\Work;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fileables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('file_id')->constrained()->cascadeOnDelete();
            $table->morphs('fileable');
            $table->timestamps();
            $table->unique(['file_id', 'fileable_type', 'fileable_id']);
        });

        $quoteType = (new Quote)->getMorphClass();
        $workType = (new Work)->getMorphClass();
        DB::table('files')->orderBy('id')->chunkById(500, function ($files) use ($quoteType, $workType): void {
            $links = [];
            foreach ($files as $file) {
                foreach (['quote_id' => $quoteType, 'work_id' => $workType] as $column => $type) {
                    if ($file->{$column} !== null) {
                        $links[] = [
                            'file_id' => $file->id,
                            'fileable_type' => $type,
                            'fileable_id' => $file->{$column},
                            'created_at' => $file->created_at,
                            'updated_at' => $file->updated_at,
                        ];
                    }
                }
            }
            if ($links !== []) {
                DB::table('fileables')->insert($links);
            }
        });

        Schema::table('files', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('quote_id');
            $table->dropConstrainedForeignId('work_id');
        });
    }
};
